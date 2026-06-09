<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
            'plan' => 'pro',
            'role' => 'user',
        ]);

        // Project 1: Photography Portfolio (published)
        $project1 = Project::create([
            'user_id' => $user->id,
            'name' => 'Aperture Photography',
            'slug' => 'aperture-photography',
            'description' => 'A photography portfolio website',
            'status' => 'published',
            'published_url' => url('/s/aperture-photography'),
        ]);

        Page::create([
            'project_id' => $project1->id,
            'title' => 'Home',
            'path' => '/',
            'is_home' => true,
            'schema' => [
                'version' => 1,
                'meta' => [
                    'title' => 'Aperture — Photography Portfolio',
                    'description' => 'Portfolio of a Dhaka-based photographer',
                    'language' => 'en',
                ],
                'theme' => [
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#0f172a',
                    'backgroundColor' => '#ffffff',
                    'textColor' => '#0f172a',
                    'fontHeading' => 'Poppins',
                    'fontBody' => 'Inter',
                    'radius' => 'lg',
                    'mode' => 'light',
                ],
                'sections' => [
                    [
                        'id' => 'blk_navbar_01',
                        'type' => 'navbar',
                        'props' => [
                            'brand' => 'Aperture',
                            'links' => [
                                ['label' => 'Gallery', 'href' => '#gallery'],
                                ['label' => 'About', 'href' => '#about'],
                                ['label' => 'Contact', 'href' => '#contact'],
                            ],
                            'ctaText' => 'Book a Shoot',
                            'ctaHref' => '#contact',
                        ],
                    ],
                    [
                        'id' => 'blk_hero_01',
                        'type' => 'hero',
                        'props' => [
                            'heading' => 'Capturing moments that last forever',
                            'subheading' => 'Wedding, portrait and street photography in Dhaka.',
                            'ctaText' => 'View my work',
                            'ctaHref' => '#gallery',
                            'image' => 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=1200&q=80',
                            'align' => 'center',
                        ],
                    ],
                    [
                        'id' => 'blk_gallery_01',
                        'type' => 'gallery',
                        'props' => [
                            'heading' => 'Selected Work',
                            'columns' => 3,
                            'images' => [
                                ['src' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', 'caption' => 'Wedding ceremony'],
                                ['src' => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=600&q=80', 'caption' => 'Golden hour portrait'],
                                ['src' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&q=80', 'caption' => 'Street photography'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'blk_about_01',
                        'type' => 'about',
                        'props' => [
                            'heading' => 'About Me',
                            'body' => "I'm a photographer with 6 years of experience specializing in wedding, portrait, and street photography. Based in Dhaka, I've covered over 200 events.",
                            'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80',
                        ],
                    ],
                    [
                        'id' => 'blk_contact_01',
                        'type' => 'contact',
                        'props' => [
                            'heading' => 'Get in Touch',
                            'email' => 'hello@aperture.com',
                            'phone' => '+8801XXXXXXXXX',
                            'fields' => ['name', 'email', 'message'],
                        ],
                    ],
                    [
                        'id' => 'blk_footer_01',
                        'type' => 'footer',
                        'props' => ['text' => '© 2026 Aperture Photography. All rights reserved.'],
                    ],
                ],
            ],
        ]);

        // Project 2: SaaS Landing Page (draft)
        $project2 = Project::create([
            'user_id' => $user->id,
            'name' => 'CloudSync SaaS',
            'slug' => 'cloudsync-saas',
            'description' => 'A SaaS product landing page',
            'status' => 'draft',
        ]);

        Page::create([
            'project_id' => $project2->id,
            'title' => 'Home',
            'path' => '/',
            'is_home' => true,
            'schema' => [
                'version' => 1,
                'meta' => [
                    'title' => 'CloudSync — Sync Your World',
                    'description' => 'Cloud storage and sync platform',
                    'language' => 'en',
                ],
                'theme' => [
                    'primaryColor' => '#7c3aed',
                    'secondaryColor' => '#1e1b4b',
                    'backgroundColor' => '#fafafa',
                    'textColor' => '#1e1b4b',
                    'fontHeading' => 'DM Sans',
                    'fontBody' => 'Inter',
                    'radius' => 'xl',
                    'mode' => 'light',
                ],
                'sections' => [
                    [
                        'id' => 'blk_navbar_01',
                        'type' => 'navbar',
                        'props' => [
                            'brand' => 'CloudSync',
                            'links' => [
                                ['label' => 'Features', 'href' => '#features'],
                                ['label' => 'Pricing', 'href' => '#pricing'],
                                ['label' => 'FAQ', 'href' => '#faq'],
                            ],
                            'ctaText' => 'Start Free Trial',
                            'ctaHref' => '#pricing',
                        ],
                    ],
                    [
                        'id' => 'blk_hero_01',
                        'type' => 'hero',
                        'props' => [
                            'heading' => 'Your files, everywhere, always in sync',
                            'subheading' => 'CloudSync keeps your documents, photos, and projects synchronized across all devices in real time.',
                            'ctaText' => 'Start Free Trial',
                            'ctaHref' => '#pricing',
                            'image' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&q=80',
                            'align' => 'center',
                        ],
                    ],
                    [
                        'id' => 'blk_features_01',
                        'type' => 'features',
                        'props' => [
                            'heading' => 'Why Teams Love CloudSync',
                            'items' => [
                                ['icon' => '⚡', 'title' => 'Lightning Fast', 'description' => 'Sync files in milliseconds with our edge network.'],
                                ['icon' => '🔒', 'title' => 'End-to-End Encrypted', 'description' => 'Your data is encrypted at rest and in transit.'],
                                ['icon' => '🌍', 'title' => 'Global CDN', 'description' => 'Access files instantly from 200+ data centers worldwide.'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'blk_stats_01',
                        'type' => 'stats',
                        'props' => [
                            'heading' => 'Trusted Worldwide',
                            'items' => [
                                ['value' => '10M+', 'label' => 'Files Synced Daily'],
                                ['value' => '99.99%', 'label' => 'Uptime'],
                                ['value' => '150+', 'label' => 'Countries'],
                                ['value' => '50K+', 'label' => 'Teams'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'blk_pricing_01',
                        'type' => 'pricing',
                        'props' => [
                            'heading' => 'Simple, Transparent Pricing',
                            'plans' => [
                                ['name' => 'Free', 'price' => '$0', 'period' => '/month', 'features' => ['5GB Storage', '2 Devices', 'Basic Support'], 'ctaText' => 'Get Started', 'highlighted' => false],
                                ['name' => 'Pro', 'price' => '$12', 'period' => '/month', 'features' => ['100GB Storage', 'Unlimited Devices', 'Priority Support', 'Version History'], 'ctaText' => 'Start Trial', 'highlighted' => true],
                                ['name' => 'Business', 'price' => '$29', 'period' => '/user/mo', 'features' => ['1TB Storage', 'Admin Console', '24/7 Support', 'SSO', 'API Access'], 'ctaText' => 'Contact Sales', 'highlighted' => false],
                            ],
                        ],
                    ],
                    [
                        'id' => 'blk_faq_01',
                        'type' => 'faq',
                        'props' => [
                            'heading' => 'Frequently Asked Questions',
                            'items' => [
                                ['question' => 'Is my data secure?', 'answer' => 'Absolutely. We use AES-256 encryption for all files at rest and TLS 1.3 for data in transit.'],
                                ['question' => 'Can I cancel anytime?', 'answer' => 'Yes. No long-term contracts. Cancel your subscription at any time.'],
                                ['question' => 'Do you offer a free trial?', 'answer' => 'Yes, Pro plans come with a 14-day free trial. No credit card required.'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'blk_cta_01',
                        'type' => 'cta',
                        'props' => [
                            'heading' => 'Ready to sync your world?',
                            'subheading' => 'Join 50,000+ teams already using CloudSync.',
                            'ctaText' => 'Start Free Trial',
                            'ctaHref' => '#pricing',
                        ],
                    ],
                    [
                        'id' => 'blk_footer_01',
                        'type' => 'footer',
                        'props' => ['text' => '© 2026 CloudSync Inc. All rights reserved.'],
                    ],
                ],
            ],
        ]);

        $this->command->info('Demo data seeded: demo@example.com / password');
    }
}
