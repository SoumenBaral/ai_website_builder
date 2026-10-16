<?php

use App\Models\Order;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Services\ExportService;
use App\Services\Shop\Price;
use Illuminate\Support\Facades\Mail;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

function store_schema(bool $cart = true): array
{
    $schema = shop_schema(); // from ItemPagesTest
    $schema['sections'][1]['props']['cart'] = $cart;
    $schema['sections'][3]['props']['phone'] = '+880 1712-345678';

    return $schema;
}

function published_store(?User $owner = null, bool $cart = true): Project
{
    $owner ??= User::factory()->create();
    $project = Project::create(['user_id' => $owner->id, 'name' => 'Forma', 'slug' => 'forma-shop', 'status' => 'published']);
    Page::create(['project_id' => $project->id, 'title' => 'Home', 'path' => '/', 'schema' => store_schema($cart), 'is_home' => true]);

    return $project;
}

it('understands real-world price formats', function () {
    expect(Price::parse('$45'))->toMatchArray(['amount' => 45.0, 'symbol' => '$', 'prefix' => true])
        ->and(Price::parse('৳1,200')['amount'])->toBe(1200.0)
        ->and(Price::parse('1.200 €'))->toMatchArray(['amount' => 1200.0, 'symbol' => '€', 'prefix' => false, 'space' => true])
        ->and(Price::parse('€9,50')['amount'])->toBe(9.5)
        ->and(Price::parse('From $1,299.99')['amount'])->toBe(1299.99)
        ->and(Price::parse('Rs 499'))->toMatchArray(['symbol' => 'Rs', 'space' => true])
        ->and(Price::parse('Price on request'))->toBeNull()
        ->and(Price::format(2599.98, '$'))->toBe('$2,599.98')
        ->and(Price::format(2400, '€', false, true))->toBe('2,400 €');
});

it('only adds the cart to sites that sell', function () {
    $ex = app(ExportService::class);
    $url = fn ($a, $s) => "/s/x/{$a}/{$s}";
    $live = ['mode' => 'live', 'orderUrl' => '/s/x/orders', 'key' => 'x'];

    $page = $ex->itemHtml(store_schema(), 'services', 'oxford-shirt', '/s/x', $url, null, null, null, $live);
    expect($page)->toContain('data-add-to-cart data-id="services/oxford-shirt"')
        ->toContain('data-buy-now')->toContain('data-qty-for="services/oxford-shirt"')
        ->toContain('window.__SHOP__')->toContain('"orderUrl":"/s/x/orders"')
        ->toContain('"whatsapp":"8801712345678"')
        ->toContain('class="shop-fallback'); // no-JS visitors still get a working button

    // Team pages and cart-off shops have no cart
    expect($ex->itemHtml(store_schema(), 'team', 'ana-silva', '/s/x', $url, null, null, null, $live))->not->toContain('data-add-to-cart data-id')
        ->and($ex->itemHtml(store_schema(false), 'services', 'oxford-shirt', '/s/x', $url, null, null, null, $live))->not->toContain('__SHOP__');

    // Preview never takes real orders
    expect($ex->itemHtml(store_schema(), 'services', 'oxford-shirt', '/p', $url, null, null, null, ['mode' => 'preview', 'orderUrl' => '/s/x/orders']))
        ->toContain('"mode":"preview"')->toContain('"orderUrl":null');
});

it('takes an order with prices from the site, not the browser', function () {
    Mail::fake();
    $project = published_store();

    $res = $this->postJson('/s/forma-shop/orders', [
        'name' => 'Rahim Uddin',
        'phone' => '+880 1711-000000',
        'address' => 'Dhanmondi, Dhaka',
        'note' => 'Size M please',
        'items' => [
            ['id' => 'services/oxford-shirt', 'quantity' => 2, 'price' => 0.01], // tampered price is ignored
            ['id' => 'services/oxford-shirt-2', 'quantity' => 1],
            ['id' => 'services/oxford-shirt', 'quantity' => 1],                    // duplicate line merges
            ['id' => 'services/' . \App\Services\ItemPages::map(store_schema())[1]['slugs'][2], 'quantity' => 1], // no price
        ],
    ])->assertCreated();

    expect($res->json())->toMatchArray(['number' => 1001, 'total' => '$184', 'hasUnpriced' => true]);
    $order = Order::first();
    expect($order->items[0])->toMatchArray(['title' => 'Oxford Shirt', 'quantity' => 3, 'lineTotal' => 135])
        ->and($order->subtotal)->toBe(184.0)
        ->and($order->status)->toBe('new');

    // Next order gets the next number
    $this->postJson('/s/forma-shop/orders', ['name' => 'Karim', 'email' => 'k@example.com', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 1]]])
        ->assertCreated()->assertJsonPath('number', 1002);
});

it('rejects bad orders politely', function () {
    published_store();
    $item = [['id' => 'services/oxford-shirt', 'quantity' => 1]];

    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'items' => $item])->assertStatus(422)->assertJsonValidationErrors(['email', 'phone']);
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'phone' => 'call me', 'items' => $item])->assertStatus(422)->assertJsonValidationErrors('phone');
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'email' => 'a@b.co', 'items' => [['id' => 'services/ghost', 'quantity' => 1]]])->assertStatus(422)->assertJsonValidationErrors('items');
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'email' => 'a@b.co', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 500]]])->assertStatus(422);
    $this->postJson('/s/forma-shop/orders', ['name' => 'Bot', 'email' => 'a@b.co', 'website' => 'http://spam', 'items' => $item])->assertStatus(422);
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'email' => 'a@b.co', 'items' => [['id' => 'team/ana-silva', 'quantity' => 1]]])->assertStatus(422); // not for sale

    expect(Order::count())->toBe(0);
});

it('takes no orders for drafts or shops that are switched off', function () {
    $project = published_store(cart: false);
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'email' => 'a@b.co', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 1]]])->assertStatus(422);

    $project->update(['status' => 'draft']);
    $this->postJson('/s/forma-shop/orders', ['name' => 'A', 'email' => 'a@b.co', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 1]]])->assertNotFound();
});

it('lets only the owner see and manage orders', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = published_store($owner);
    $this->postJson('/s/forma-shop/orders', ['name' => 'Rahim', 'email' => 'r@example.com', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 2]]])->assertCreated();
    $order = Order::first();

    $this->actingAs($owner)->get(route('orders.index', $project))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Orders')->where('orders.0.number', 1001)->where('orders.0.items.0.lineTotalText', '$90')->where('shopOn', true));
    $this->actingAs($owner)->patch(route('orders.update', [$project, $order]), ['status' => 'confirmed'])->assertRedirect();
    expect($order->fresh()->status)->toBe('confirmed');

    $this->actingAs($owner)->patch(route('orders.update', [$project, $order]), ['status' => 'shipped-to-mars'])->assertSessionHasErrors('status');
    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get(route('orders.index', $project))->assertForbidden();
    $this->actingAs($stranger)->patch(route('orders.update', [$project, $order]), ['status' => 'cancelled'])->assertForbidden();
});

it('keeps photo cards free of AI-added icons', function () {
    OpenAI::fake([CreateResponse::fake([
        'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => json_encode(['summary' => 'Wrote details.', 'props' => [
            'heading' => 'Shop', 'itemPages' => true, 'items' => [['icon' => '👔', 'title' => 'Oxford Shirt', 'description' => 'Crisp.', 'image' => 'https://images.unsplash.com/photo-a?w=900', 'details' => 'Nice.']],
        ]])], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
    ])]);

    $res = $this->actingAs(User::factory()->create())->postJson(route('ai.editSection'), [
        'section' => ['id' => 's', 'type' => 'services', 'props' => ['heading' => 'Shop', 'items' => [['title' => 'Oxford Shirt', 'description' => 'Crisp.', 'image' => 'https://images.unsplash.com/photo-a?w=900']]]],
        'theme' => store_schema()['theme'],
        'instruction' => 'Write details for every card',
    ])->assertOk();

    expect($res->json('section.props.items.0'))->not->toHaveKey('icon')
        ->and($res->json('section.props.items.0.details'))->toBe('Nice.');
});

it('shows every order in the sidebar Orders page and on the dashboard', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = published_store($owner);
    $this->postJson('/s/forma-shop/orders', ['name' => 'Rahim', 'email' => 'r@example.com', 'items' => [['id' => 'services/oxford-shirt', 'quantity' => 1]]])->assertCreated();

    $this->actingAs($owner)->get(route('orders.all'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Orders')
            ->where('project', null)
            ->where('orders.0.projectName', 'Forma')
            ->where('shops.0.id', $project->id)
            ->where('newOrders', 1));

    $this->actingAs($owner)->get(route('dashboard'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('projects.0.shop', true)->where('projects.0.newOrders', 1)->where('projects.0.orders', 1));

    // Someone else sees none of it
    $this->actingAs(User::factory()->create())->get(route('orders.all'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('orders', [])->where('newOrders', 0));
});
