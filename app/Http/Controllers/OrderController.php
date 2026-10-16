<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Project;
use App\Services\Shop\Price;
use App\Services\Shop\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    /** A visitor sends their cart from a published site. Prices always come from the site, never the browser. */
    public function store(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $schema = $project->homePage?->schema;
        abort_if(!$schema, 404);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:40|regex:/^[\d\s()+\-.]{6,}$/|required_without:email',
            'address' => 'nullable|string|max:500',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1|max:50',
            'items.*.id' => 'required|string|max:200',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'website' => 'nullable|max:0', // honeypot: people never see this field
        ], [
            'email.required_without' => 'Add an email or a phone number so the shop can reach you.',
            'phone.required_without' => 'Add a phone number or an email so the shop can reach you.',
            'phone.regex' => 'That phone number doesn\'t look right.',
            'website.max' => 'Something went wrong. Please try again.',
        ]);

        $products = Shop::products($schema);
        $lines = [];
        $missing = [];
        foreach ($data['items'] as $row) {
            $product = $products[$row['id']] ?? null;
            if (!$product) {
                $missing[] = $row['id'];
                continue;
            }
            $key = $product['id'];
            $qty = min(99, (int) $row['quantity'] + ($lines[$key]['quantity'] ?? 0)); // merge duplicate lines
            $lines[$key] = [
                'id' => $key,
                'title' => $product['title'],
                'price' => $product['price'],
                'amount' => $product['amount'],
                'quantity' => $qty,
                'lineTotal' => $product['amount'] !== null ? round($product['amount'] * $qty, 2) : null,
            ];
        }
        if ($missing) {
            throw ValidationException::withMessages(['items' => 'Some items in your cart are no longer available. We\'ve removed them — please check your cart and try again.'])
                ->status(422)->errorBag('default');
        }

        $priced = array_filter($lines, fn ($l) => $l['lineTotal'] !== null);
        $subtotal = $priced ? round(array_sum(array_column($priced, 'lineTotal')), 2) : null;

        $order = DB::transaction(function () use ($project, $data, $lines, $subtotal, $products, $request, $priced) {
            $number = (int) Order::where('project_id', $project->id)->lockForUpdate()->max('number');

            return Order::create([
                'project_id' => $project->id,
                'number' => max(1000, $number) + 1,
                'customer_name' => trim($data['name']),
                'customer_email' => $data['email'] ?? null,
                'customer_phone' => isset($data['phone']) ? trim($data['phone']) : null,
                'customer_address' => $data['address'] ?? null,
                'note' => $data['note'] ?? null,
                'items' => array_values($lines),
                'subtotal' => $subtotal,
                'currency' => Shop::currency($products),
                'has_unpriced' => count($priced) < count($lines),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
            ]);
        });

        $this->notifyOwner($project, $order);

        return response()->json([
            'number' => $order->number,
            'total' => $order->formattedTotal(),
            'hasUnpriced' => $order->has_unpriced,
        ], 201);
    }

    /** The owner's order inbox for one website. */
    public function index(Request $request, Project $project)
    {
        abort_if($project->user_id !== $request->user()->id, 403);

        $orders = $project->orders()->latest()->limit(500)->get();

        return Inertia::render('Orders', [
            'project' => $project->only('id', 'name', 'slug', 'status'),
            'orders' => $orders->map(fn (Order $o) => $this->present($o, $project)),
            'shopOn' => Shop::enabled($project->homePage?->schema ?? []),
            'revenue' => $this->revenue($orders),
        ]);
    }

    /** Every order from every website the user owns (the sidebar's "Orders"). */
    public function all(Request $request)
    {
        $projects = $request->user()->projects()->with('homePage:id,project_id,schema')->get()->keyBy('id');
        $orders = Order::whereIn('project_id', $projects->keys())->latest()->limit(500)->get();

        return Inertia::render('Orders', [
            'project' => null,
            'orders' => $orders->map(fn (Order $o) => $this->present($o, $projects[$o->project_id])),
            'shops' => $projects->filter(fn ($p) => Shop::enabled($p->homePage?->schema ?? []) || $orders->contains('project_id', $p->id))
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'status' => $p->status])->values(),
            'shopOn' => $projects->contains(fn ($p) => Shop::enabled($p->homePage?->schema ?? [])),
            'revenue' => $this->revenue($orders),
        ]);
    }

    private function present(Order $o, Project $project): array
    {
        return [
            'id' => $o->id,
            'projectId' => $project->id,
            'projectName' => $project->name,
            'number' => $o->number,
            'status' => $o->status,
            'name' => $o->customer_name,
            'email' => $o->customer_email,
            'phone' => $o->customer_phone,
            'address' => $o->customer_address,
            'note' => $o->note,
            'items' => array_map(fn ($l) => $l + [
                'lineTotalText' => $l['lineTotal'] !== null
                    ? Price::format((float) $l['lineTotal'], $o->currency['symbol'] ?? '', $o->currency['prefix'] ?? true, $o->currency['space'] ?? false)
                    : null,
            ], $o->items),
            'total' => $o->formattedTotal(),
            'subtotal' => $o->subtotal,
            'hasUnpriced' => $o->has_unpriced,
            'createdAt' => $o->created_at->toIso8601String(),
        ];
    }

    /** Confirmed + completed sales, in the shop's currency (mixed currencies → plain number). */
    private function revenue($orders): string
    {
        $paid = $orders->whereIn('status', ['confirmed', 'completed']);
        $currencies = $paid->map(fn ($o) => json_encode($o->currency))->unique();
        $c = $currencies->count() === 1 ? ($paid->first()->currency ?? []) : [];

        return Price::format((float) $paid->sum('subtotal'), $c['symbol'] ?? '', $c['prefix'] ?? true, $c['space'] ?? false);
    }

    public function update(Request $request, Project $project, Order $order)
    {
        abort_if($project->user_id !== $request->user()->id || $order->project_id !== $project->id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]);
        $order->update($data);

        return back();
    }

    private function notifyOwner(Project $project, Order $order): void
    {
        $email = $project->user?->email;
        if (!$email) {
            return;
        }
        $lines = collect($order->items)->map(fn ($l) => "• {$l['quantity']} × {$l['title']}" . ($l['price'] ? " ({$l['price']})" : ''))->implode("\n");
        $body = "New order #{$order->number} on {$project->name}\n\n{$lines}\n\n"
            . ($order->formattedTotal() ? 'Total: ' . $order->formattedTotal() . ($order->has_unpriced ? ' + items priced on request' : '') . "\n\n" : '')
            . "From: {$order->customer_name}\n"
            . ($order->customer_phone ? "Phone: {$order->customer_phone}\n" : '')
            . ($order->customer_email ? "Email: {$order->customer_email}\n" : '')
            . ($order->customer_address ? "Address: {$order->customer_address}\n" : '')
            . ($order->note ? "\nNote: {$order->note}\n" : '')
            . "\nManage your orders: " . route('orders.index', $project);

        try {
            Mail::raw($body, function ($m) use ($email, $project, $order) {
                $m->to($email)->subject("New order #{$order->number} — {$project->name}");
                if ($order->customer_email) {
                    $m->replyTo($order->customer_email, $order->customer_name);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Order email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
