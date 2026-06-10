<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;

class AiWebsiteService
{
    public function __construct(
        private SchemaValidator $validator,
        private ImageResolver $imageResolver,
    ) {}

    public function generate(string $userPrompt, ?int $userId = null, ?int $projectId = null): array
    {
        // Enforce generation caps
        if ($userId) {
            $this->enforceGenerationCap($userId);
        }

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1',
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'website_schema',
                    'strict' => false,
                    'schema' => $this->jsonSchema(),
                ],
            ],
            'max_completion_tokens' => 4000,
        ]);

        $json = $response->choices[0]->message->content;
        $schema = json_decode($json, true);

        $schema = $this->validator->validate($schema);
        $schema = $this->imageResolver->resolvePlaceholders($schema);

        if ($userId) {
            AiGeneration::create([
                'user_id' => $userId,
                'project_id' => $projectId,
                'prompt' => $userPrompt,
                'model' => 'gpt-4.1',
                'input_tokens' => $response->usage->promptTokens ?? null,
                'output_tokens' => $response->usage->completionTokens ?? null,
                'cost_usd' => $this->estimateCost($response->usage->promptTokens ?? 0, $response->usage->completionTokens ?? 0),
                'status' => 'completed',
            ]);
        }

        return $schema;
    }

    public function editSchema(array $currentSchema, string $instruction, ?int $userId = null): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'system', 'content' => $this->editSystemPrompt()],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\nCurrent schema:\n" . json_encode($currentSchema)],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'website_schema',
                    'strict' => false,
                    'schema' => $this->jsonSchema(),
                ],
            ],
            'max_completion_tokens' => 8000,
        ]);

        $json = $response->choices[0]->message->content;
        $schema = json_decode($json, true);

        $schema = $this->validator->validate($schema);
        $schema = $this->imageResolver->resolvePlaceholders($schema);

        return $schema;
    }

    public function editSection(array $section, string $instruction, array $theme, ?int $userId = null): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                ['role' => 'system', 'content' => $this->sectionEditPrompt()],
                ['role' => 'user', 'content' => "Instruction: {$instruction}\n\nTheme: " . json_encode($theme) . "\n\nSection:\n" . json_encode($section)],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'section_schema',
                    'strict' => false,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'props' => ['type' => 'object'],
                        ],
                        'required' => ['id', 'type', 'props'],
                    ],
                ],
            ],
            'max_completion_tokens' => 3000,
        ]);

        $json = $response->choices[0]->message->content;
        $result = json_decode($json, true);

        if (!$result || !isset($result['props'])) {
            throw new \Exception('AI returned invalid section data.');
        }

        // Preserve original id and type
        $result['id'] = $section['id'];
        $result['type'] = $section['type'];

        // Resolve image placeholders in the section
        $tempSchema = ['sections' => [$result]];
        $tempSchema = $this->imageResolver->resolvePlaceholders($tempSchema);
        $result = $tempSchema['sections'][0];

        return $result;
    }

    private function blockPropsReference(): string
    {
        return <<<'REF'
BLOCK TYPES & THEIR PROPS (including layout props):

navbar: { brand, links [{label, href}], ctaText, ctaHref }
hero: { heading, subheading, ctaText, ctaHref, image, align ("center"|"left"|"right"), layout ("center"|"left"|"right"|"split-left"|"split-right"), overlayOpacity (0-100, default 50), minHeight ("sm"|"md"|"lg"|"full") }
  - layout "center"/"left"/"right": full background image with text alignment
  - layout "split-left": image on LEFT, text on RIGHT (50/50 grid)
  - layout "split-right": text on LEFT, image on RIGHT (50/50 grid)
features: { heading, image (optional header image), items [{icon (emoji), title, description, image (optional item image URL)}], columns (1-4, default 3), align ("center"|"left") }
gallery: { heading, columns (2-4), images [{src, caption}] }
about: { heading, body, image, imagePosition ("left"|"right", default "right") }
services: { heading, image (optional header image), items [{icon (emoji), title, description, image (optional item image URL)}], columns (1-4, default 3), align ("center"|"left") }
testimonials: { heading, items [{quote, author, role, image}], columns (1-3, default 3) }
pricing: { heading, image (optional header image), plans [{name, price, period, features (string[]), cta, highlighted (bool)}], columns (1-3, default 3) }
stats: { heading, image (optional background image with dark overlay), items [{value, label}], columns (2-6, default 4) }
faq: { heading, image (optional side image, creates 2-col layout), items [{question, answer}], maxWidth ("sm"|"md"|"lg"|"full", default "md") }
team: { heading, members [{name, role, image, bio}], columns (1-4, default 4) }
cta: { heading, subheading, ctaText, ctaHref, image (optional background image with overlay), align ("center"|"left") }
contact: { heading, subheading, image (optional top image), email, phone, address, fields (string[]), layout ("split"|"stacked", default "split") }
custom: { columns (1-4), gap (sm/md/lg/xl), padding (sm/md/lg/xl), elements (array of element objects) }
  Element types: heading {text, size (xl/2xl/3xl/4xl), align}, text {text, size (sm/base/lg/xl), align}, image {src ("PLACEHOLDER:desc"), alt, radius, shadow, border, width (full/half/third)}, icon {emoji, size (2xl-5xl)}, button {text, href, variant (primary/secondary/outline)}, divider {style (solid/dashed/dotted)}, spacer {height (sm/md/lg/xl)}
  Use custom blocks when users want a unique layout that doesn't fit predefined block types.
footer: { brand, links [{label, href}], copyright, socials [{platform, url}] }

NOTE: ALL blocks now support an optional `image` prop. Use it to add visual richness.

STYLING PROPS (available on ALL section types):
- sectionBg: custom background color (hex like "#1e293b" or any CSS color)
- sectionText: custom text color (hex like "#ffffff")
These override the global theme for that section only.

IMAGE STYLING PROPS (available on blocks that display images):
- imageRadius: "none" | "sm" | "md" | "lg" | "xl" | "full" — controls border-radius of the image
- imageShadow: "none" | "sm" | "md" | "lg" | "xl" — adds shadow to the image
- imageBorder: "none" | "thin" | "thick" — adds border around the image
Example: imageRadius "full" + imageShadow "lg" = circular image with large shadow

LAYOUT TIPS:
- Use "split-left"/"split-right" for hero to create modern side-by-side layouts
- Set imagePosition "left" on about sections for variety
- Use columns to control grid density (fewer columns = bigger cards)
- Set align "left" for a more editorial/blog feel, "center" for landing pages
- Use minHeight "full" for full-screen hero sections
- Use layout "stacked" on contact for a centered single-column form
- Add image to CTA/stats sections for dramatic background effects
- Add image to FAQ for a visual 2-column layout

IMAGES:
- Use "PLACEHOLDER:<short description>" for new images — server replaces with real Unsplash photos.
- Set imageRadius, imageShadow, imageBorder to style images (round, shadow, border).
REF;
    }

    private function systemPrompt(): string
    {
        $ref = $this->blockPropsReference();
        return <<<PROMPT
You are a senior web designer that outputs ONLY a website layout as JSON.

Rules:
- Return a JSON object matching the provided schema EXACTLY. No prose, no HTML, no markdown.
- Choose 5–8 sections that fit the user's purpose and industry.
- Always start with a "navbar" and end with a "footer".
- Pick a tasteful theme: a primary color suited to the brand, readable fonts, and a light/dark mode that fits the mood.
- Write realistic, specific marketing copy — never "Lorem ipsum".
- Keep headings short and punchy; body copy concise but persuasive.
- Each section must have a unique id in the format "blk_<type>_<number>".
- Use sectionBg/sectionText props to create visual contrast between sections (e.g. alternate light/dark sections, colored hero).
- Available section types: navbar, hero, features, gallery, about, services, testimonials, pricing, stats, faq, team, cta, contact, footer.

{$ref}

You will be given a user request describing the website they want.
PROMPT;
    }

    private function editSystemPrompt(): string
    {
        $ref = $this->blockPropsReference();
        return <<<PROMPT
You edit an existing website JSON schema. Apply the user's requested change and return the FULL updated JSON matching the schema. Change only what is requested — preserve everything else EXACTLY as-is.

You can do ANYTHING the user asks:
- Change text content (headings, body, CTA text, etc.)
- Change colors and styling (theme colors, sectionBg, sectionText for any section)
- Add/remove/reorder sections
- Add/remove items within sections (features, testimonials, team members, pricing plans, FAQ items, etc.)
- Change images (use "PLACEHOLDER:<description>" for new ones)
- Change fonts, border radius, dark/light mode in the theme
- Change the navbar brand name, links, CTA
- Restructure layout (e.g. change number of gallery columns)
- Add new sections of any type
- Improve copy quality, make it more professional, change tone
- Translate content to another language
- Anything else — you have full control over the JSON

Rules:
- Return the complete updated JSON schema.
- Only modify what the user's instruction asks for.
- Keep all section IDs unchanged unless adding/removing sections.
- New sections need unique ids: "blk_<type>_<number>".

{$ref}
PROMPT;
    }

    private function sectionEditPrompt(): string
    {
        $ref = $this->blockPropsReference();
        return <<<PROMPT
You are an expert web designer. You edit a SINGLE section of a website JSON schema.

You receive:
1. The current section JSON (with id, type, props)
2. The site's theme (colors, fonts, etc.)
3. An instruction describing what to change

Return ONLY the updated section JSON object with id, type, and props.

You can do ANYTHING the user asks for this section:
- Rewrite/improve any text (headings, descriptions, quotes, etc.)
- Change section colors: set sectionBg (background) and sectionText (text color) props
- Add/remove/edit items (features, testimonials, team members, FAQ, pricing plans, stats, etc.)
- Change images (use "PLACEHOLDER:<description>" for new ones)
- Change layout props (columns, alignment, etc.)
- Add new props like email, phone, address for contact sections
- Change nav links, brand name, CTA text and URL
- Change pricing (amounts, features, plan names, highlighting)
- Translate content to another language
- Change the tone (formal, casual, playful, corporate, etc.)
- Make content longer or shorter
- Add social media links, opening hours, location details
- Anything else — you have full control over the section's props

Rules:
- Keep the id and type unchanged.
- Preserve all existing props that are not being changed.
- Write professional, realistic copy — never "Lorem ipsum".
- When improving, enhance copy quality AND adjust styling (colors, layout) if appropriate.

{$ref}
PROMPT;
    }

    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['version', 'meta', 'theme', 'sections'],
            'properties' => [
                'version' => ['type' => 'integer'],
                'meta' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['title', 'description', 'language'],
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'language' => ['type' => 'string'],
                    ],
                ],
                'theme' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['primaryColor', 'secondaryColor', 'backgroundColor', 'textColor', 'fontHeading', 'fontBody', 'radius', 'mode'],
                    'properties' => [
                        'primaryColor' => ['type' => 'string'],
                        'secondaryColor' => ['type' => 'string'],
                        'backgroundColor' => ['type' => 'string'],
                        'textColor' => ['type' => 'string'],
                        'fontHeading' => ['type' => 'string'],
                        'fontBody' => ['type' => 'string'],
                        'radius' => ['type' => 'string', 'enum' => ['none', 'sm', 'md', 'lg', 'xl', 'full']],
                        'mode' => ['type' => 'string', 'enum' => ['light', 'dark']],
                    ],
                ],
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'type', 'props'],
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => ['navbar', 'hero', 'features', 'gallery', 'about', 'services', 'testimonials', 'pricing', 'stats', 'faq', 'team', 'cta', 'contact', 'custom', 'footer'],
                            ],
                            'props' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function enforceGenerationCap(int $userId): void
    {
        $user = User::findOrFail($userId);
        $limit = match ($user->plan) {
            'pro' => 100,
            'team' => 500,
            default => 10, // free
        };

        $monthlyCount = AiGeneration::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($monthlyCount >= $limit) {
            throw new \Exception("Monthly generation limit reached ({$limit}). Upgrade your plan for more.");
        }
    }

    public function getRemainingGenerations(int $userId): array
    {
        $user = User::findOrFail($userId);
        $limit = match ($user->plan) {
            'pro' => 100,
            'team' => 500,
            default => 10,
        };

        $used = AiGeneration::where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'plan' => $user->plan,
        ];
    }

    private function estimateCost(int $inputTokens, int $outputTokens): float
    {
        // GPT-4.1 pricing: ~$2/1M input, ~$8/1M output
        return ($inputTokens * 2 / 1_000_000) + ($outputTokens * 8 / 1_000_000);
    }
}
