<?php

namespace App\Services\Ai;

/**
 * Single source of truth for what the AI may produce.
 *
 * Every block below mirrors the props actually read by the Vue blocks
 * (resources/js/Components/builder/blocks) and the export templates
 * (resources/views/export/blocks). From this one definition we derive:
 *   - the strict JSON schema sent to OpenAI (structured outputs),
 *   - the block reference text inside the prompts,
 *   - the normalisation rules applied to every AI response.
 *
 * When a block gains or renames a prop, update it here.
 */
final class BlockCatalog
{
    /** Fonts offered by the editor's Theme panel (and loadable from Google Fonts). */
    public const FONTS = [
        'Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
        'Playfair Display', 'Merriweather', 'Raleway', 'Nunito',
        'Source Sans 3', 'PT Sans', 'Ubuntu', 'Oswald', 'DM Sans',
    ];

    public const RADII = ['none', 'sm', 'md', 'lg', 'xl', 'full'];

    private const IMAGE_STYLE = ['imageRadius', 'imageShadow', 'imageBorder'];

    /** Entrance animations a section can use (see resources/css/site-animations.css). */
    public const ANIMATIONS = ['none', 'fade', 'fade-up', 'fade-down', 'from-left', 'from-right', 'zoom', 'blur', 'flip', 'custom'];

    /** AI-designed motion stored next to the catalog props (see MotionSpec); kept across AI edits. */
    public const MOTION_PROPS = ['bgEffect', 'customAnimation', 'typingPhrases'];

    /** Older / natural names → current value ("slide-left" moved left, i.e. came FROM the right). */
    public const ANIMATION_ALIASES = [
        'slide-left' => 'from-right', 'slide-right' => 'from-left',
        'slide-from-left' => 'from-left', 'slide-from-right' => 'from-right', 'left' => 'from-left', 'right' => 'from-right',
        'fade-in' => 'fade', 'fadein' => 'fade', 'fade-in-up' => 'fade-up', 'slide-up' => 'fade-up', 'up' => 'fade-up',
        'fade-in-down' => 'fade-down', 'slide-down' => 'fade-down', 'down' => 'fade-down',
        'zoom-in' => 'zoom', 'scale' => 'zoom', 'pop' => 'zoom', 'blur-in' => 'blur', 'flip-up' => 'flip', 'off' => 'none',
    ];

    public const HOVER_EFFECTS = ['none', 'lift', 'zoom', 'glow'];

    /** Attention effects for the section's buttons (see resources/css/site-animations.css). */
    public const BUTTON_EFFECTS = ['none', 'pulse', 'shine', 'glow', 'bounce', 'wiggle', 'pop'];

    /** Effects for the section's main heading (see resources/js/site/animRuntime.js). */
    public const TEXT_EFFECTS = ['none', 'typing', 'typing-loop', 'words', 'letters', 'gradient', 'highlight'];

    /** Sections whose cards can open their own details page, and the list holding the cards. */
    public const ITEM_PAGE_LISTS = ['services' => 'items', 'features' => 'items', 'team' => 'members'];

    /** Card fields used only on the details page (the editor's card view doesn't read them). */
    public const DETAIL_FIELDS = ['details', 'highlights', 'photos', 'ctaText', 'ctaHref'];

    /** Props applied by BlockRenderer / the export wrapper rather than by the block itself. */
    public const WRAPPER_PROPS = ['sectionBg', 'sectionText', 'animation', 'animationSpeed', 'animationStagger', 'hoverEffect', 'buttonEffect', 'textEffect', 'textSpeed'];

    private const ANIMATION_PROPS = ['animation', 'animationSpeed', 'animationStagger', 'hoverEffect', 'buttonEffect', 'textEffect', 'textSpeed'];

    /** Map any accepted spelling to a current animation value (null when unknown). */
    public static function canonicalAnimation(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $v = strtolower(trim(str_replace([' ', '_'], '-', $value)));
        $v = self::ANIMATION_ALIASES[$v] ?? $v;

        return in_array($v, self::ANIMATIONS, true) ? $v : null;
    }

    private static ?array $blocks = null;

    /* ------------------------------------------------------------------
     | Prop spec helpers
     * ------------------------------------------------------------------ */

    private static function text(string $desc, bool $required = false): array
    {
        return ['kind' => 'string', 'desc' => $desc, 'required' => $required];
    }

    private static function image(string $desc, bool $required = false): array
    {
        return ['kind' => 'image', 'desc' => $desc, 'required' => $required];
    }

    /** An image the user must upload themselves (e.g. a logo) — the AI never invents one. */
    private static function upload(string $desc): array
    {
        return ['kind' => 'image', 'generated' => false, 'desc' => $desc, 'required' => false];
    }

    private static function int(int $min, int $max, int $default, string $desc): array
    {
        return ['kind' => 'int', 'min' => $min, 'max' => $max, 'default' => $default, 'desc' => $desc, 'required' => false];
    }

    private static function choice(array $values, string $desc, bool $required = false): array
    {
        return ['kind' => 'enum', 'values' => $values, 'desc' => $desc, 'required' => $required];
    }

    private static function flag(string $desc): array
    {
        return ['kind' => 'bool', 'desc' => $desc, 'required' => false];
    }

    private static function items(array $fields, int $min, int $max, string $desc): array
    {
        return ['kind' => 'list', 'fields' => $fields, 'min' => $min, 'max' => $max, 'desc' => $desc, 'required' => true];
    }

    private static function strings(int $min, int $max, string $desc): array
    {
        return ['kind' => 'strings', 'min' => $min, 'max' => $max, 'desc' => $desc, 'required' => true];
    }

    private static function color(string $desc): array
    {
        return ['kind' => 'color', 'desc' => $desc, 'required' => false];
    }

    /* ------------------------------------------------------------------
     | Block definitions
     * ------------------------------------------------------------------ */

    public static function blocks(): array
    {
        if (self::$blocks !== null) {
            return self::$blocks;
        }

        $imageStyle = [
            'imageRadius' => self::choice(['none', 'sm', 'md', 'lg', 'xl', 'full'], 'Image corner radius'),
            'imageShadow' => self::choice(['none', 'sm', 'md', 'lg', 'xl'], 'Image shadow'),
            'imageBorder' => self::choice(['none', 'thin', 'thick'], 'Image border'),
        ];
        $icon = self::text('One relevant emoji, e.g. "📸"', true);
        $title = self::text('2–5 words', true);
        $desc = self::text('1–2 sentences, max 25 words', true);
        $link = ['label' => self::text('1–2 words', true), 'href' => self::text('"#<section-type>" anchor or a URL', true)];

        $blocks = [
            'navbar' => [
                'purpose' => 'Top navigation with brand name, 3–5 links to sections on the page and one call-to-action button. Always the first section.',
                'props' => [
                    'logo' => self::upload('Logo image URL uploaded by the user. Keep the current value exactly; never invent, describe or generate a logo — use null if there is none.'),
                    'brand' => self::text('Brand / business name as plain text (shown next to the logo)', true),
                    'hideBrandText' => self::flag('true when the uploaded logo already contains the name; keep the current value'),
                    'logoSize' => self::choice(['sm', 'md', 'lg', 'xl'], 'Logo height in the header (lg/xl for logos that include the name)'),
                    'links' => self::items($link, 3, 6, '3–5 links to sections on this page'),
                    'ctaText' => self::text('Short action, e.g. "Book a call"'),
                    'ctaHref' => self::text('Usually "#contact" or "#pricing"'),
                ],
            ],
            'hero' => [
                'purpose' => 'The first impression: headline, supporting line, primary call to action and a strong image.',
                'props' => [
                    'heading' => self::text('Benefit-led headline, max 8 words', true),
                    'subheading' => self::text('1–2 sentences, max 25 words', true),
                    'ctaText' => self::text('Primary action, 2–4 words', true),
                    'ctaHref' => self::text('"#contact", "#pricing" or another section anchor', true),
                    'image' => self::image('Main photo', true),
                    'layout' => self::choice(['center', 'left', 'right', 'split-left', 'split-right'], 'center/left/right = full-bleed background photo with dark overlay; split-left/split-right = photo beside the text (cleaner, good for products, portfolios, professionals)', true),
                    'align' => self::choice(['center', 'left', 'right'], 'Text alignment'),
                    'overlayOpacity' => self::int(0, 90, 50, 'Darkness of the photo overlay for background layouts (40–65 keeps text readable)'),
                    'minHeight' => self::choice(['sm', 'md', 'lg', 'full'], '"full" = full-screen hero'),
                ],
            ],
            'features' => [
                'purpose' => 'Key benefits or reasons to choose the business, in a grid of cards.',
                'props' => [
                    'heading' => self::text('Section heading, max 7 words', true),
                    'items' => self::items(['icon' => $icon, 'title' => $title, 'description' => $desc, 'image' => self::image('Optional card photo')], 3, 6, '3–6 benefits'),
                    'columns' => self::int(1, 4, 3, 'Grid columns'),
                    'align' => self::choice(['center', 'left'], 'Card text alignment'),
                    'image' => self::image('Optional banner image above the grid'),
                ] + $imageStyle,
            ],
            'services' => [
                'purpose' => 'What the business offers (services, menu highlights, products, classes) — can show prices.',
                'props' => [
                    'heading' => self::text('Section heading, max 7 words', true),
                    'items' => self::items(['icon' => $icon, 'title' => $title, 'description' => $desc, 'price' => self::text('Optional price, e.g. "$49" or "From $120"'), 'image' => self::image('Optional photo of this service/product')], 3, 8, '3–8 offerings'),
                    'columns' => self::int(1, 4, 3, 'Grid columns'),
                    'align' => self::choice(['center', 'left'], 'Card text alignment'),
                    'image' => self::image('Optional banner image above the grid'),
                ] + $imageStyle,
            ],
            'gallery' => [
                'purpose' => 'Photo grid — portfolios, venues, food, products, past events.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'images' => self::items(['src' => self::image('Photo', true), 'caption' => self::text('Short caption, max 6 words')], 3, 9, '6 photos is ideal'),
                    'columns' => self::int(2, 4, 3, 'Grid columns'),
                ],
            ],
            'about' => [
                'purpose' => 'The story: who is behind the business, why it exists, what makes it different.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'body' => self::text('2–4 sentences, warm and specific, max 70 words', true),
                    'image' => self::image('Photo of the founder, team, workspace or product', true),
                    'imagePosition' => self::choice(['left', 'right'], 'Side of the photo'),
                ] + $imageStyle,
            ],
            'testimonials' => [
                'purpose' => 'Social proof from customers.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'items' => self::items([
                        'quote' => self::text('1–2 specific sentences, max 30 words', true),
                        'author' => self::text('First name + last initial, e.g. "Maya R."', true),
                        'role' => self::text('Short context, e.g. "Bride, 2025" or "Marketing lead"', true),
                        'avatar' => self::image('Optional portrait'),
                    ], 2, 6, '3 testimonials is ideal'),
                    'columns' => self::int(1, 3, 3, 'Grid columns'),
                ],
            ],
            'pricing' => [
                'purpose' => 'Plans or packages with prices.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'plans' => self::items([
                        'name' => self::text('Plan name, 1–2 words', true),
                        'price' => self::text('e.g. "$29" or "Free"', true),
                        'period' => self::text('e.g. "/month", "/session" or ""', true),
                        'features' => self::strings(3, 7, '3–6 short inclusions'),
                        'ctaText' => self::text('Button label, 2–3 words', true),
                        'highlighted' => self::flag('true for exactly one recommended plan — it gets a "Most Popular" badge and accent styling'),
                    ], 1, 4, '3 plans with the middle one highlighted is ideal'),
                    'columns' => self::int(1, 3, 3, 'Grid columns'),
                    'image' => self::image('Optional banner image'),
                ] + $imageStyle,
            ],
            'stats' => [
                'purpose' => 'A band of impressive numbers.',
                'props' => [
                    'heading' => self::text('Optional heading, max 6 words'),
                    'items' => self::items(['value' => self::text('e.g. "12+", "4.9★", "2,500"', true), 'label' => self::text('2–4 words', true)], 3, 6, '3–4 numbers'),
                    'columns' => self::int(2, 6, 4, 'Grid columns'),
                    'image' => self::image('Optional background photo (gets a dark overlay)'),
                ],
            ],
            'faq' => [
                'purpose' => 'Answers to real objections and practical questions.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'items' => self::items(['question' => self::text('A question a real customer would ask', true), 'answer' => self::text('1–3 sentences, max 45 words', true)], 3, 8, '4–6 questions'),
                    'maxWidth' => self::choice(['sm', 'md', 'lg', 'full'], 'Content width'),
                    'image' => self::image('Optional side photo (creates a two-column layout)'),
                ] + $imageStyle,
            ],
            'team' => [
                'purpose' => 'The people behind the business.',
                'props' => [
                    'heading' => self::text('Section heading, max 6 words', true),
                    'members' => self::items([
                        'name' => self::text('Full name', true),
                        'role' => self::text('Job title', true),
                        'image' => self::image('Portrait', true),
                        'bio' => self::text('One sentence, max 20 words'),
                    ], 2, 8, '3–4 members'),
                    'columns' => self::int(1, 4, 4, 'Grid columns'),
                ],
            ],
            'cta' => [
                'purpose' => 'A closing call to action band before the footer.',
                'props' => [
                    'heading' => self::text('Action-oriented heading, max 8 words', true),
                    'subheading' => self::text('One sentence, max 20 words', true),
                    'ctaText' => self::text('Button label, 2–4 words', true),
                    'ctaHref' => self::text('Usually "#contact"', true),
                    'align' => self::choice(['center', 'left'], 'Text alignment'),
                    'image' => self::image('Optional background photo'),
                ],
            ],
            'contact' => [
                'purpose' => 'Contact form plus email, phone and address.',
                'props' => [
                    'heading' => self::text('e.g. "Let\'s plan your day"', true),
                    'email' => self::text('Email address'),
                    'phone' => self::text('Phone number'),
                    'address' => self::text('Street address or service area'),
                    'fields' => self::strings(2, 6, 'Form fields, e.g. ["Name","Email","Message"]'),
                    'layout' => self::choice(['split', 'stacked'], 'split = details beside the form'),
                    'image' => self::image('Optional photo above the form'),
                ] + $imageStyle,
            ],
            'custom' => [
                'purpose' => 'Free-form layout from elements — only when no other block fits.',
                'props' => [
                    'columns' => self::int(1, 4, 1, 'Grid columns'),
                    'gap' => self::choice(['sm', 'md', 'lg', 'xl'], 'Space between elements'),
                    'padding' => self::choice(['sm', 'md', 'lg', 'xl'], 'Section padding'),
                    'align' => self::choice(['left', 'center', 'right'], 'Alignment'),
                    'elements' => self::items([
                        'type' => self::choice(['heading', 'text', 'image', 'icon', 'button', 'divider', 'spacer'], 'Element type', true),
                        'text' => self::text('heading / text / button label'),
                        'size' => self::choice(['sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl'], 'heading: xl–4xl, text: sm–xl, icon: 2xl–5xl'),
                        'align' => self::choice(['left', 'center', 'right'], 'heading / text alignment'),
                        'src' => self::image('image element photo'),
                        'alt' => self::text('image alt text'),
                        'width' => self::choice(['full', 'half', 'third'], 'image width'),
                        'radius' => self::choice(['none', 'sm', 'md', 'lg', 'xl', 'full'], 'image radius'),
                        'shadow' => self::choice(['none', 'sm', 'md', 'lg', 'xl'], 'image shadow'),
                        'border' => self::choice(['none', 'thin', 'thick'], 'image border'),
                        'emoji' => self::text('icon element emoji'),
                        'href' => self::text('button link'),
                        'variant' => self::choice(['primary', 'secondary', 'outline'], 'button style'),
                        'style' => self::choice(['solid', 'dashed', 'dotted'], 'divider style'),
                        'height' => self::choice(['sm', 'md', 'lg', 'xl'], 'spacer height'),
                    ], 1, 12, 'Elements in reading order'),
                ],
            ],
            'footer' => [
                'purpose' => 'Closing line with copyright. Always the last section.',
                'props' => [
                    'text' => self::text('e.g. "© 2026 Brand. Crafted with care in Lisbon."', true),
                ],
            ],
        ];

        // Cards that open a details page: the long-form fields live on each card.
        $detailFields = [
            'details' => self::text('Details page only: 2–3 short paragraphs separated by a blank line ("\\n\\n"), concrete and specific, max 110 words. Leave null unless itemPages is true'),
            'highlights' => self::strings(0, 6, 'Details page only: 3–5 short selling points (max 8 words each), or [] unless itemPages is true'),
            'photos' => self::strings(0, 6, 'Extra photo URLs uploaded by the user — always copy as-is; never invent ([] if none)'),
            'ctaText' => self::text('The ACTION button on the item\'s own page, 2–3 words: "Order now", "Buy now", "Book this session", "Reserve a table", "Enquire now". Never "View…/See…/Explore…/Discover…" (the visitor is already on the page). null = site default'),
            'ctaHref' => self::text('Details page button link, usually "#contact"; null = site default'),
        ];
        foreach (self::ITEM_PAGE_LISTS as $type => $list) {
            $blocks[$type]['props'][$list]['fields'] += $detailFields;
            $blocks[$type]['props']['itemPages'] = self::flag('true = each card links to its own details page (products, services, menu items, team profiles)');
        }
        $blocks['services']['props']['cart'] = self::flag('Online shop: true = details pages get "Add to cart" and visitors can send an order (needs itemPages true). Only for physical products/menu items people buy.');

        // Every section can override its own colours.
        foreach ($blocks as &$block) {
            $block['props']['sectionBg'] = self::color('Optional section background colour (hex). Use sparingly for rhythm; always pair with sectionText.');
            $block['props']['sectionText'] = self::color('Optional section text colour (hex) that contrasts with sectionBg.');
            // …and animate in when scrolled into view.
            $block['props']['animation'] = self::choice(self::ANIMATIONS, 'Entrance animation of the section content when it scrolls into view (null = none). "custom" = an AI-designed move: keep it only if the section already uses it');
            $block['props']['animationSpeed'] = self::choice(['fast', 'normal', 'slow'], 'Animation speed');
            $block['props']['animationStagger'] = self::flag('Content (heading, text, each card) appears one after another');
            $block['props']['hoverEffect'] = self::choice(self::HOVER_EFFECTS, 'Hover effect on cards and photos');
            $block['props']['buttonEffect'] = self::choice(self::BUTTON_EFFECTS, 'Animated effect on this section\'s buttons');
            $block['props']['textEffect'] = self::choice(self::TEXT_EFFECTS, 'Effect on the section\'s main heading (typing = typewriter)');
            $block['props']['textSpeed'] = self::choice(['fast', 'normal', 'slow'], 'Heading effect speed');
        }

        return self::$blocks = $blocks;
    }

    /** @return string[] */
    public static function types(): array
    {
        return array_keys(self::blocks());
    }

    public static function has(string $type): bool
    {
        return isset(self::blocks()[$type]);
    }

    public static function props(string $type): array
    {
        return self::blocks()[$type]['props'] ?? [];
    }

    public static function hasImageStyle(string $type): bool
    {
        return isset(self::props($type)['imageRadius']);
    }

    /* ------------------------------------------------------------------
     | Anchors: the same ids are used by the normaliser (to point links at
     | sections) and by the export renderer (to put ids on sections).
     * ------------------------------------------------------------------ */

    /** @return array<int, string|null> section index => anchor id (null for navbar/footer) */
    public static function anchors(array $sections): array
    {
        $seen = [];
        $anchors = [];
        foreach (array_values($sections) as $i => $section) {
            $type = $section['type'] ?? '';
            if (in_array($type, ['navbar', 'footer'], true) || $type === '') {
                $anchors[$i] = null;
                continue;
            }
            $base = $type === 'hero' ? 'home' : $type;
            $seen[$base] = ($seen[$base] ?? 0) + 1;
            $anchors[$i] = $seen[$base] === 1 ? $base : "{$base}-{$seen[$base]}";
        }

        return $anchors;
    }

    /* ------------------------------------------------------------------
     | Strict JSON schema (OpenAI structured outputs)
     | Rules: every object lists all keys in `required`, sets
     | additionalProperties=false, and optional values are nullable.
     * ------------------------------------------------------------------ */

    private static function propSchema(array $spec): array
    {
        $nullable = !($spec['required'] ?? false);
        $schema = match ($spec['kind']) {
            'string', 'image', 'color' => ['type' => $nullable ? ['string', 'null'] : 'string'],
            'int' => ['type' => ['integer', 'null']],
            'bool' => ['type' => ['boolean', 'null']],
            'enum' => $nullable
                ? ['type' => ['string', 'null'], 'enum' => [...$spec['values'], null]]
                : ['type' => 'string', 'enum' => $spec['values']],
            'strings' => ['type' => 'array', 'items' => ['type' => 'string']],
            'list' => ['type' => 'array', 'items' => self::objectSchema($spec['fields'])],
        };

        $desc = $spec['desc'] ?? '';
        if ($spec['kind'] === 'image' && ($spec['generated'] ?? true)) {
            $desc = trim("{$desc}. Value: \"PLACEHOLDER:<3–6 word English photo search query>\" for new images, or keep an existing https URL.", '. ');
        }
        if ($desc !== '') {
            $schema['description'] = $desc;
        }

        return $schema;
    }

    private static function objectSchema(array $props): array
    {
        $properties = [];
        foreach ($props as $key => $spec) {
            $properties[$key] = self::propSchema($spec);
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => array_keys($props),
            'properties' => $properties,
        ];
    }

    public static function sectionSchema(string $type): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['id', 'type', 'props'],
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'Unique id like "blk_hero_1". Keep existing ids unchanged.'],
                'type' => ['type' => 'string', 'enum' => [$type]],
                'props' => self::objectSchema(self::props($type)),
            ],
        ];
    }

    private static function themeSchema(): array
    {
        $hex = ['type' => 'string', 'description' => 'Hex colour like "#1f6feb"'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['primaryColor', 'secondaryColor', 'backgroundColor', 'textColor', 'fontHeading', 'fontBody', 'radius', 'mode'],
            'properties' => [
                'primaryColor' => ['description' => 'Brand accent used for buttons and highlights; must keep white button text readable'] + $hex,
                'secondaryColor' => ['description' => 'Deep supporting colour (footer, dark bands)'] + $hex,
                'backgroundColor' => ['description' => 'Page background'] + $hex,
                'textColor' => ['description' => 'Body text; strong contrast with backgroundColor'] + $hex,
                'fontHeading' => ['type' => 'string', 'enum' => self::FONTS],
                'fontBody' => ['type' => 'string', 'enum' => self::FONTS],
                'radius' => ['type' => 'string', 'enum' => self::RADII],
                'mode' => ['type' => 'string', 'enum' => ['light', 'dark']],
            ],
        ];
    }

    private static function sectionsSchema(): array
    {
        return [
            'type' => 'array',
            'items' => ['anyOf' => array_map(fn ($t) => self::sectionSchema($t), self::types())],
        ];
    }

    /** Full-site schema. `plan` (generation) or `summary` (edits) is written first to steer the model. */
    public static function siteSchema(string $lead = 'plan'): array
    {
        $leadSchema = $lead === 'plan'
            ? [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['brand', 'industry', 'audience', 'goal', 'tone', 'visualDirection'],
                'properties' => [
                    'brand' => ['type' => 'string', 'description' => 'Brand name (use the one given, otherwise invent a fitting one)'],
                    'industry' => ['type' => 'string'],
                    'audience' => ['type' => 'string', 'description' => 'Who the site is for'],
                    'goal' => ['type' => 'string', 'description' => 'The one action visitors should take'],
                    'tone' => ['type' => 'string', 'description' => 'Voice of the copy in 3 adjectives'],
                    'visualDirection' => ['type' => 'string', 'description' => 'Palette, typography and imagery direction in one sentence'],
                ],
            ]
            : ['type' => 'string', 'description' => 'One short sentence describing what you changed, written to the user'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [$lead, 'meta', 'theme', 'sections'],
            'properties' => [
                $lead => $leadSchema,
                'meta' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['title', 'description', 'language'],
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'SEO title, max 60 characters'],
                        'description' => ['type' => 'string', 'description' => 'SEO description, 120–160 characters'],
                        'language' => ['type' => 'string', 'description' => 'ISO 639-1 code of the site copy, e.g. "en", "bn"'],
                    ],
                ],
                'theme' => self::themeSchema(),
                'sections' => self::sectionsSchema(),
            ],
        ];
    }

    /** Single-section edit: the props schema is specific to the section's type. */
    public static function sectionEditSchema(string $type): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'props'],
            'properties' => [
                'summary' => ['type' => 'string', 'description' => 'One short sentence describing what you changed, written to the user'],
                'props' => self::objectSchema(self::props($type)),
            ],
        ];
    }

    /* ------------------------------------------------------------------
     | Prompt reference (generated, so it can never drift from the schema)
     * ------------------------------------------------------------------ */

    /** @param array<int, string>|null $only limit to these types (a one-section edit needs just its own) */
    public static function promptReference(?array $only = null): string
    {
        $lines = [$only ? 'SECTION REFERENCE (props in brackets; * = required):' : 'SECTION TYPES (props in brackets; * = required):'];
        foreach (self::blocks() as $type => $block) {
            if ($only !== null && !in_array($type, $only, true)) {
                continue;
            }
            $props = [];
            foreach ($block['props'] as $key => $spec) {
                if (in_array($key, ['sectionBg', 'sectionText', ...self::IMAGE_STYLE, ...self::ANIMATION_PROPS], true)) {
                    continue;
                }
                $props[] = self::describeProp($key, $spec);
            }
            $styling = self::hasImageStyle($type) ? ' (+ image styling)' : '';
            $lines[] = "- {$type}: {$block['purpose']}\n    [" . implode('; ', $props) . "]{$styling}";
        }
        $lines[] = '- Every section also accepts sectionBg + sectionText (hex) to override its colours.';
        $lines[] = '- Details pages (services, features, team): itemPages true makes every card open its own page; then give each card details (2–3 short paragraphs), 3–5 highlights and optionally ctaText/ctaHref. photos are user uploads: copy them, never invent.';
        $lines[] = '- Image styling: imageRadius (none|sm|md|lg|xl|full), imageShadow (none|sm|md|lg|xl), imageBorder (none|thin|thick).';
        $lines[] = '- Animation (every section): animation (' . implode('|', self::ANIMATIONS) . '), animationSpeed (fast|normal|slow), animationStagger (true = content appears one by one), hoverEffect (' . implode('|', self::HOVER_EFFECTS) . '), buttonEffect (' . implode('|', self::BUTTON_EFFECTS) . '), textEffect (' . implode('|', self::TEXT_EFFECTS) . ') + textSpeed on the main heading.';

        return implode("\n", $lines);
    }

    private static function describeProp(string $key, array $spec): string
    {
        $star = ($spec['required'] ?? false) && !in_array($spec['kind'], ['list', 'strings'], true) ? '*' : '';

        return match ($spec['kind']) {
            'enum' => "{$key}{$star}: " . implode('|', $spec['values']),
            'int' => "{$key}: {$spec['min']}–{$spec['max']}",
            'bool' => "{$key}: true|false",
            'image' => ($spec['generated'] ?? true) ? "{$key}{$star}: image" : "{$key}: user-uploaded image URL (never invent)",
            'strings' => "{$key}: string[] ({$spec['desc']})",
            'list' => "{$key}: [{" . implode(', ', array_map(
                fn ($k, $s) => self::describeProp($k, $s),
                array_keys($spec['fields']),
                $spec['fields'],
            )) . "}] ({$spec['desc']})",
            default => "{$key}{$star}" . (($spec['desc'] ?? '') !== '' ? " ({$spec['desc']})" : ''),
        };
    }
}
