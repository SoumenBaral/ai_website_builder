<?php

namespace App\Services;

/**
 * The public demo at /demo: a complete, hand-crafted site that shows off what a published
 * site can do — motion, heading effects, background effects, product pages and a working cart.
 * Rendered by the same ExportService as real published sites.
 */
class DemoSite
{
    private static function photo(string $id, int $w = 1400): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$w}&q=80&auto=format&fit=crop";
    }

    public static function schema(): array
    {
        $p = fn (string $id, int $w = 1400) => self::photo($id, $w);

        return [
            'version' => 1,
            'meta' => [
                'title' => 'Maison Atelier — Considered menswear, made in Dhaka',
                'description' => 'Shirts, outerwear and leather goods cut from natural fabrics and made to be worn for years. Free delivery across Dhaka.',
                'language' => 'en',
            ],
            'theme' => [
                'primaryColor' => '#16130f',
                'secondaryColor' => '#16130f',
                'backgroundColor' => '#f6f2ea',
                'textColor' => '#1c1915',
                'fontHeading' => 'Playfair Display',
                'fontBody' => 'Inter',
                'radius' => 'sm',
                'mode' => 'light',
            ],
            'sections' => [
                [
                    'id' => 'demo_nav', 'type' => 'navbar',
                    'props' => [
                        'brand' => 'Maison Atelier',
                        'logo' => '/demo/maison-atelier-logo.svg', // designed with the built-in Logo Studio engine
                        'hideBrandText' => true,
                        'logoSize' => 'lg',
                        'links' => [
                            ['label' => 'Collection', 'href' => '#services'],
                            ['label' => 'Our story', 'href' => '#about'],
                            ['label' => 'Lookbook', 'href' => '#gallery'],
                            ['label' => 'Contact', 'href' => '#contact'],
                        ],
                        'ctaText' => 'Shop the collection', 'ctaHref' => '#services',
                        'animation' => 'fade-down', 'buttonEffect' => 'shine',
                    ],
                ],
                [
                    'id' => 'demo_hero', 'type' => 'hero',
                    'props' => [
                        'heading' => 'Clothes made to be lived in.',
                        'subheading' => 'Natural fabrics, honest tailoring and leather that ages beautifully — designed and made in Dhaka, delivered to your door in 48 hours.',
                        'ctaText' => 'Shop the collection', 'ctaHref' => '#services',
                        'image' => $p('1617137968427-85924c800a22', 1600),
                        'layout' => 'split-right', 'align' => 'left', 'minHeight' => 'full',
                        'animation' => 'fade-up', 'animationStagger' => true, 'animationSpeed' => 'slow',
                        'textEffect' => 'typing-loop', 'textSpeed' => 'normal',
                        'typingPhrases' => ['Tailored to last a decade.', 'Cut from Egyptian cotton.', 'Made in small batches.'],
                        'buttonEffect' => 'shine',
                        'bgEffect' => ['kind' => 'blobs', 'name' => 'Warm glow', 'shape' => 'dot', 'colors' => ['#e7c9a0', '#d9b88f', '#f3dfc1'], 'count' => 3, 'sizeMin' => 4, 'sizeMax' => 10, 'motion' => 'drift', 'speed' => 'slow', 'opacity' => 55, 'glow' => false],
                    ],
                ],
                [
                    'id' => 'demo_stats', 'type' => 'stats',
                    'props' => [
                        'items' => [
                            ['value' => '12,000+', 'label' => 'Happy customers'],
                            ['value' => '4.9★', 'label' => 'Average review'],
                            ['value' => '48 h', 'label' => 'Dhaka delivery'],
                            ['value' => '100%', 'label' => 'Natural fabrics'],
                        ],
                        'columns' => 4,
                        'sectionBg' => '#16130f', 'sectionText' => '#f6f2ea',
                        'animation' => 'flip', 'animationStagger' => true,
                    ],
                ],
                [
                    'id' => 'demo_shop', 'type' => 'services',
                    'props' => [
                        'heading' => 'The Autumn Collection',
                        'columns' => 3, 'align' => 'left',
                        'itemPages' => true, 'cart' => true,
                        'animation' => 'fade-up', 'animationStagger' => true, 'hoverEffect' => 'zoom',
                        'textEffect' => 'words',
                        'items' => [
                            [
                                'title' => 'The Oxford Shirt', 'price' => '৳2,450',
                                'description' => 'Crisp Egyptian cotton with a soft button-down collar.',
                                'image' => $p('1620012253295-c15cc3e65df4'),
                                'photos' => [$p('1602810318383-e386cc2a3ccf'), $p('1598033129183-c4f50c736f10')],
                                'details' => "Our best-seller, cut from 120s two-ply Egyptian cotton that feels cool on Dhaka afternoons and gets softer with every wash.\n\nA slim-but-not-skinny fit, a collar that keeps its shape under a blazer or open at the neck, and mother-of-pearl buttons sewn to stay.",
                                'highlights' => ['120s two-ply Egyptian cotton', 'Mother-of-pearl buttons', 'Pre-washed — no shrinking', 'Sizes S to XXL'],
                                'ctaText' => 'Order now',
                            ],
                            [
                                'title' => 'Lightweight Bomber', 'price' => '৳5,900',
                                'description' => 'Garment-dyed cotton with a soft rib collar and cuffs.',
                                'image' => $p('1591047139829-d91aecb6caea'),
                                'details' => "A light, unlined bomber in garment-dyed cotton that layers over a tee in October and under a coat in January.\n\nRibbed collar, cuffs and hem, a two-way brass zip and two deep welt pockets — it gets softer and more yours with every wear.",
                                'highlights' => ['Garment-dyed cotton twill', 'Two-way brass zip', 'Deep welt pockets', 'Unlined — ideal for Dhaka winters'],
                            ],
                            [
                                'title' => 'Tailored Chinos', 'price' => '৳3,200',
                                'description' => 'Stretch cotton twill with a clean, tapered leg.',
                                'image' => $p('1473966968600-fa801b869a1a'),
                                'details' => "Cut from cotton twill with a touch of stretch, so they move with you from the office to dinner.\n\nA mid-rise, a tapered leg and a hem left unfinished, so our tailors can shorten them to your length for free.",
                                'highlights' => ['Cotton twill with 2% stretch', 'Free hemming', 'Tapered, mid-rise fit', 'Four colours'],
                            ],
                            [
                                'title' => 'Leather Sneakers', 'price' => '৳5,500',
                                'description' => 'Hand-stitched full-grain leather on a natural rubber sole.',
                                'image' => $p('1549298916-b41d501d3772'),
                                'photos' => [$p('1595950653106-6c9ebd614d3a')],
                                'details' => "Minimal sneakers in full-grain leather, hand-stitched and set on a natural rubber cup sole that can be resoled.\n\nLeather-lined, cushioned and broken in after a week — they only look better with time.",
                                'highlights' => ['Full-grain leather', 'Resoleable rubber cup sole', 'Leather lining', 'True to size'],
                            ],
                            [
                                'title' => 'Heritage Watch', 'price' => '৳8,900',
                                'description' => 'A 38 mm steel case with a sapphire crystal.',
                                'image' => $p('1524592094714-0f0654e20314'),
                                'photos' => [$p('1523275335684-37898b6baf30')],
                                'details' => "A quiet, everyday watch: a 38 mm brushed-steel case, sapphire crystal and a Japanese movement you never have to think about.\n\nIt comes on a vegetable-tanned leather strap that swaps in seconds.",
                                'highlights' => ['38 mm brushed steel', 'Sapphire crystal', '50 m water resistant', '2-year warranty'],
                            ],
                            [
                                'title' => 'Canvas Backpack', 'price' => '৳4,200',
                                'description' => 'Heavy canvas with leather trims and a padded laptop sleeve.',
                                'image' => $p('1553062407-98eeb64c6a62'),
                                'details' => "Made from 18 oz waxed canvas and trimmed in leather, sized for a 15-inch laptop, a change of clothes and the day's essentials.\n\nThe padded back, magnetic closures and a hidden passport pocket make it as good for the commute as for a weekend in Sylhet.",
                                'highlights' => ['18 oz waxed canvas', 'Fits a 15" laptop', 'Hidden passport pocket', 'Lifetime repairs'],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'demo_why', 'type' => 'features',
                    'props' => [
                        'heading' => 'Why Maison Atelier',
                        'columns' => 3, 'align' => 'left',
                        'animation' => 'fade-up', 'animationStagger' => true, 'hoverEffect' => 'lift',
                        'items' => [
                            ['icon' => '🧵', 'title' => 'Made in small batches', 'description' => 'Every piece is cut and sewn in our own Dhaka workshop — no overstock, no compromises.'],
                            ['icon' => '🌿', 'title' => 'Natural fabrics only', 'description' => 'Cotton, linen, wool and leather that breathe in our climate and last for years.'],
                            ['icon' => '🚚', 'title' => 'Free 48-hour delivery', 'description' => 'Across Dhaka, with free returns and exchanges within 30 days.'],
                        ],
                    ],
                ],
                [
                    'id' => 'demo_about', 'type' => 'about',
                    'props' => [
                        'heading' => 'A small workshop with big standards',
                        'body' => 'Maison Atelier began in 2019 with twelve tailors and one rule: make clothes we would wear for a decade. Today we still cut every pattern by hand, choose every fabric ourselves and repair anything we make — for free, for life.',
                        'image' => $p('1490114538077-0a7f8cb49891'),
                        'imagePosition' => 'left', 'imageRadius' => 'sm', 'imageShadow' => 'lg',
                        'animation' => 'from-left', 'animationStagger' => true,
                    ],
                ],
                [
                    'id' => 'demo_lookbook', 'type' => 'gallery',
                    'props' => [
                        'heading' => 'The Lookbook',
                        'columns' => 3,
                        'animation' => 'blur', 'animationStagger' => true, 'hoverEffect' => 'zoom',
                        'images' => [
                            ['src' => $p('1516826957135-700dedea698c', 900), 'caption' => 'Weekend in Old Dhaka'],
                            ['src' => $p('1507679799987-c73779587ccf', 900), 'caption' => 'The navy suit'],
                            ['src' => $p('1551028719-00167b16eac5', 900), 'caption' => 'Leather, aged well'],
                            ['src' => $p('1598033129183-c4f50c736f10', 900), 'caption' => 'Office, but softer'],
                            ['src' => $p('1511499767150-a48a237f0083', 900), 'caption' => 'Summer details'],
                            ['src' => $p('1596755094514-f87e34085b2c', 900), 'caption' => 'Packed for Cox\'s Bazar'],
                        ],
                    ],
                ],
                [
                    'id' => 'demo_reviews', 'type' => 'testimonials',
                    'props' => [
                        'heading' => 'Worn and loved',
                        'columns' => 3,
                        'animation' => 'fade-up', 'animationStagger' => true, 'hoverEffect' => 'lift',
                        'items' => [
                            ['quote' => 'The Oxford is the first shirt that survived a Dhaka summer and still looked sharp at the end of the day.', 'author' => 'Tanvir A.', 'role' => 'Architect, Gulshan', 'avatar' => self::photo('1500648767791-00dcc994a43e', 200)],
                            ['quote' => 'Ordered on Tuesday, wore the jacket on Thursday. They even shortened my chinos for free.', 'author' => 'Nadia R.', 'role' => 'Bought a gift', 'avatar' => self::photo('1494790108377-be9c29b29330', 200)],
                            ['quote' => 'Two years in and my sneakers look better than the day they arrived. Worth every taka.', 'author' => 'Imran H.', 'role' => 'Product designer', 'avatar' => self::photo('1507003211169-0a1dd7228f2d', 200)],
                        ],
                    ],
                ],
                [
                    'id' => 'demo_faq', 'type' => 'faq',
                    'props' => [
                        'heading' => 'Good to know',
                        'maxWidth' => 'md',
                        'animation' => 'fade-up', 'animationStagger' => true,
                        'items' => [
                            ['question' => 'How long does delivery take?', 'answer' => 'Within 48 hours anywhere in Dhaka, and 3–5 days across Bangladesh. Delivery is free on every order.'],
                            ['question' => 'Can I return or exchange?', 'answer' => 'Yes — within 30 days, unworn, with tags. We pick it up from your door at no cost.'],
                            ['question' => 'How do I pay?', 'answer' => 'Cash on delivery, bKash or card. We confirm every order by phone before it ships.'],
                            ['question' => 'Do you alter clothes?', 'answer' => 'Hemming and sleeve adjustments are free on everything we make. Just add a note at checkout.'],
                        ],
                    ],
                ],
                [
                    'id' => 'demo_cta', 'type' => 'cta',
                    'props' => [
                        'heading' => 'Your next favourite shirt is waiting.',
                        'subheading' => 'Free delivery, free alterations and free repairs — for as long as you wear it.',
                        'ctaText' => 'Shop the collection', 'ctaHref' => '#services',
                        'align' => 'center',
                        'sectionBg' => '#16130f', 'sectionText' => '#f6f2ea',
                        'animation' => 'custom',
                        'customAnimation' => ['name' => 'Soft rise', 'opacity' => 0, 'x' => 0, 'y' => 60, 'scale' => 0.94, 'rotate' => 0, 'rotateX' => 12, 'rotateY' => 0, 'skewX' => 0, 'blur' => 6, 'easing' => 'spring'],
                        'animationStagger' => true,
                        'buttonEffect' => 'pulse',
                        'textEffect' => 'gradient',
                        'bgEffect' => ['kind' => 'particles', 'name' => 'Golden sparkles', 'shape' => 'sparkle', 'colors' => ['#e7c9a0', '#f6f2ea'], 'count' => 26, 'sizeMin' => 5, 'sizeMax' => 13, 'motion' => 'twinkle', 'speed' => 'slow', 'opacity' => 85, 'glow' => true],
                    ],
                ],
                [
                    'id' => 'demo_contact', 'type' => 'contact',
                    'props' => [
                        'heading' => 'Visit the atelier',
                        'email' => 'hello@maisonatelier.com',
                        'phone' => '+880 1711-000000',
                        'address' => 'House 12, Road 7, Gulshan 1, Dhaka',
                        'fields' => ['Name', 'Email', 'Message'],
                        'layout' => 'split',
                        'animation' => 'fade-up', 'animationStagger' => true,
                    ],
                ],
                [
                    'id' => 'demo_footer', 'type' => 'footer',
                    'props' => [
                        'text' => '© 2026 Maison Atelier. Made with care in Dhaka.',
                        'animation' => 'fade',
                    ],
                ],
            ],
        ];
    }
}
