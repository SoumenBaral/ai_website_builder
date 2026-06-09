# AI-Powered No-Code Website Builder — Full Build Plan
### Laravel + Inertia + Vue 3 + OpenAI API

> **Project:** An AI-Powered No-Code Website Builder with Real-Time Customization
> **Students:** Nur-uddin Ahmed Nuhin (210033), Shakifuzzan khan Showhan (210010)
> **Stack:** Laravel (backend/API) · Inertia.js (glue) · Vue 3 (frontend) · Tailwind CSS · OpenAI API · MySQL

This document is your end-to-end engineering plan: architecture, database, the AI engine, the editor, export/deployment, a phase-by-phase roadmap, real code snippets, and a realistic timeline for a final-year project.

---

## 0. The One Decision That Makes or Breaks This Project

A website builder can represent a generated site in two ways:

| Approach | What the AI returns | Editable? | Drag & drop? | Version history? | Clean export? | Verdict |
|---|---|---|---|---|---|---|
| **A. Raw HTML** | A blob of `<div>...</div>` | Painful | No | Messy diffs | Ugly | ❌ Demo only |
| **B. Structured JSON "blocks"** | A typed tree of sections | Trivial | Yes | Clean | Perfect | ✅ Do this |

**We commit to Approach B.** The AI never writes HTML directly. It returns a **website schema** — a JSON document describing the theme and an ordered list of *blocks* (hero, features, gallery, etc.). Your Vue app maps each block type to a Vue component and renders it. To export, you convert the same schema to a standalone HTML file.

Why this wins:
- **Inline editing** = edit a field in the JSON, the component re-renders instantly.
- **Drag & drop** = reorder items in the `sections` array.
- **Theme changes** = change `theme` object, all blocks pick up CSS variables.
- **AI edits** = "make the hero bigger and change the headline" → AI patches the JSON, not the markup.
- **Version history** = store JSON snapshots (tiny, diffable) instead of HTML dumps.
- **Reliable AI output** = OpenAI **Structured Outputs** force the model to return JSON matching your schema, so you never parse broken HTML.

Memorize this sentence for your defense: *"The AI generates a validated JSON description of the page; the renderer turns blocks into Vue components; export turns the same blocks into static HTML."*

---

## 1. Technology Stack (final, justified)

**Backend**
- **Laravel 11/12** — API, auth, business logic, OpenAI proxy, export, billing.
- **Laravel Sanctum** — session auth (Inertia uses session-based auth, not tokens).
- **openai-php/laravel** — official-style PHP client for the OpenAI API.
- **Laravel Queues (database or Redis)** — run AI generation jobs in the background.

**Glue**
- **Inertia.js v2** — lets Laravel routes return Vue pages with props, no separate REST layer needed for the app UI. You still expose a few JSON endpoints for AI streaming and autosave.

**Frontend**
- **Vue 3** (Composition API + `<script setup>`).
- **Pinia** — editor state store (current schema, selected block, history).
- **Tailwind CSS** — styling for both the app and generated sites.
- **vuedraggable (SortableJS)** — drag-and-drop block reordering.
- **@vueuse/core** — debounced autosave, useful composables.

**Data & infra**
- **MySQL 8** (primary). MongoDB is optional/overkill — MySQL with JSON columns is enough and easier to defend.
- **Cloudinary** or local `storage` — image hosting for uploaded/generated assets.
- **Vercel / Netlify / Laravel Forge / Render** — deployment.
- **OpenAI API** — content + layout generation (model choice in §6).

> Keep MongoDB and Laravel-vs-Node out of scope unless asked. Picking *one* coherent stack (Laravel + Inertia + Vue + MySQL) and executing it well scores higher than listing five alternatives.

---

## 2. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                         BROWSER (Vue 3)                       │
│                                                               │
│   Prompt Bar → Dashboard → EDITOR (the core)                  │
│   ┌─────────────┬───────────────────────┬─────────────────┐  │
│   │ Block Tree  │   Canvas / Renderer    │  Inspector      │  │
│   │ (reorder,   │   (live, responsive,   │  (props, theme, │  │
│   │  add/del)   │    inline editing)     │   AI assist)    │  │
│   └─────────────┴───────────────────────┴─────────────────┘  │
│         ▲  Pinia store holds the live website schema (JSON)   │
└─────────┼─────────────────────────────────────────────────────┘
          │ Inertia visits + JSON fetch (autosave / AI stream)
          ▼
┌─────────────────────────────────────────────────────────────┐
│                        LARAVEL (API)                          │
│  Auth │ Projects │ Pages │ Versions │ Assets │ Analytics      │
│                                                               │
│   AiController ──► AiWebsiteService ──► OpenAI API            │
│        │                 │  (Structured Outputs → JSON)       │
│        │                 └─► SchemaValidator (reject bad JSON)│
│   ExportService ──► static HTML/CSS bundle (.zip)            │
└──────────┬──────────────────────────────────┬────────────────┘
           ▼                                   ▼
       ┌────────┐                        ┌──────────┐
       │ MySQL  │                        │ OpenAI   │
       │  + JSON│                        │  API     │
       └────────┘                        └──────────┘
```

The generated site is **data** (JSON in MySQL), not files, until the user clicks Export/Publish.

---

## 3. Database Schema

Keep it lean. Eight tables cover everything in your proposal.

```
users
  id, name, email, password, plan (free|pro|team), role, timestamps

projects
  id, user_id (FK), name, slug (unique), description,
  thumbnail_path, status (draft|published), published_url, timestamps

pages
  id, project_id (FK), title, path ("/", "/about"),
  schema (JSON)  ← the live website schema for this page
  is_home (bool), timestamps

page_versions               ← version history
  id, page_id (FK), schema (JSON snapshot),
  label ("AI generation", "manual edit"), created_by, created_at

assets                      ← uploaded / AI image references
  id, project_id (FK), url, type (image|font|icon), meta (JSON), timestamps

ai_generations              ← audit + cost tracking
  id, user_id, project_id, prompt (TEXT), model,
  input_tokens, output_tokens, cost_usd, status, created_at

deployments
  id, project_id (FK), provider, target_url, status, log (TEXT), created_at

analytics_events            ← visitor tracking for published sites
  id, project_id (FK), event (pageview|click), path, referrer,
  device, country, session_id, created_at
```

Notes:
- The `schema` JSON column is the heart. MySQL 8 supports `JSON` columns and you can query into them if needed.
- `page_versions` gives you undo/restore for free — save a row before each AI edit and on a timer.
- `ai_generations` lets you cap usage per plan and show cost in your report (great for the "Business Prospect" section).

---

## 4. The Website Schema (define this first — everything depends on it)

This is the contract between the AI, the renderer, and the exporter. Lock it down early.

```jsonc
{
  "version": 1,
  "meta": {
    "title": "Aperture — Photography Portfolio",
    "description": "Portfolio of a Dhaka-based photographer",
    "language": "en"
  },
  "theme": {
    "primaryColor": "#2563eb",
    "secondaryColor": "#0f172a",
    "backgroundColor": "#ffffff",
    "textColor": "#0f172a",
    "fontHeading": "Poppins",
    "fontBody": "Inter",
    "radius": "lg",          // none | sm | md | lg | xl | full
    "mode": "light"          // light | dark
  },
  "sections": [
    {
      "id": "blk_navbar_01",
      "type": "navbar",
      "props": {
        "brand": "Aperture",
        "links": [
          { "label": "Gallery", "href": "#gallery" },
          { "label": "About", "href": "#about" },
          { "label": "Contact", "href": "#contact" }
        ],
        "ctaText": "Book a shoot",
        "ctaHref": "#contact"
      }
    },
    {
      "id": "blk_hero_01",
      "type": "hero",
      "props": {
        "heading": "Capturing moments that last forever",
        "subheading": "Wedding, portrait and street photography in Dhaka.",
        "ctaText": "View my work",
        "ctaHref": "#gallery",
        "image": "https://.../hero.jpg",
        "align": "center"      // left | center
      }
    },
    {
      "id": "blk_gallery_01",
      "type": "gallery",
      "props": {
        "heading": "Selected Work",
        "columns": 3,
        "images": [
          { "src": "https://.../1.jpg", "caption": "Bride at golden hour" }
        ]
      }
    },
    {
      "id": "blk_about_01",
      "type": "about",
      "props": {
        "heading": "About me",
        "body": "I'm a photographer with 6 years of experience...",
        "image": "https://.../me.jpg"
      }
    },
    {
      "id": "blk_contact_01",
      "type": "contact",
      "props": {
        "heading": "Get in touch",
        "email": "hello@aperture.com",
        "phone": "+8801XXXXXXXXX",
        "fields": ["name", "email", "message"]
      }
    },
    {
      "id": "blk_footer_01",
      "type": "footer",
      "props": { "text": "© 2026 Aperture. All rights reserved." }
    }
  ]
}
```

### Supported block types (your "component library")
Start with ~10. Each maps to one Vue component **and** one export template.

`navbar` · `hero` · `features` · `gallery` · `about` · `services` · `testimonials` · `pricing` · `stats` · `faq` · `team` · `cta` · `contact` · `footer`

Ship 6–8 for the MVP (navbar, hero, features, gallery, about, contact, footer, cta) and add the rest later. **More polished blocks = better demo.**

---

## 5. Project Setup (step by step)

### 5.1 Create the Laravel + Inertia + Vue app

```bash
# 1. New Laravel app
composer create-project laravel/laravel ai-website-builder
cd ai-website-builder

# 2. Install Inertia + Vue starter (Laravel Breeze is the easiest)
composer require laravel/breeze --dev
php artisan breeze:install vue        # choose Vue with Inertia
# this wires Inertia, Vue 3, Tailwind, Vite, and auth scaffolding

npm install
npm run dev          # in one terminal
php artisan serve    # in another  (or use `composer run dev` if defined)
```

Breeze gives you login/register/dashboard out of the box — that covers your **Authentication System** requirement on day one.

### 5.2 Add the project-specific packages

```bash
# Backend
composer require openai-php/laravel
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"

# Frontend
npm install pinia vuedraggable@next @vueuse/core
```

### 5.3 Configure the OpenAI key

```env
# .env
OPENAI_API_KEY=sk-...your-key...
OPENAI_ORGANIZATION=        # optional
```

> **Security rule:** the key lives only on the server (`.env`). The browser/Vue **never** sees it. All AI calls go Vue → Laravel → OpenAI.

### 5.4 Migrations

Create migrations for the tables in §3:

```bash
php artisan make:model Project -m
php artisan make:model Page -m
php artisan make:model PageVersion -m
php artisan make:model Asset -m
php artisan make:model AiGeneration -m
php artisan make:model Deployment -m
php artisan make:migration create_analytics_events_table
php artisan migrate
```

Example `pages` migration:

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('path')->default('/');
    $table->json('schema');           // the website schema
    $table->boolean('is_home')->default(true);
    $table->timestamps();
});
```

---

## 6. The AI Engine (the brain)

### 6.1 Model choice (verified June 2026)

OpenAI's lineup as of mid-2026:

| Model | Input / Output (per 1M tokens) | Best for in this project |
|---|---|---|
| **GPT-4.1** | ~$2 / $8 | **Main generator.** Explicitly strong at structured outputs & long context. |
| GPT-4.1 mini | ~$0.40 / $1.60 | Inline edits, single-block tweaks, suggestions. |
| GPT-4.1 nano / GPT-5.4 nano | ~$0.10–0.20 / $0.40–1.25 | Classification, "which block does the user mean", autocomplete. |
| GPT-5.4 / GPT-5.5 | ~$5 / $30 | Only if you need top reasoning; usually overkill here. |

**Recommended cascade (cost-smart and impressive to examiners):**
- **Full site generation** → `gpt-4.1` (structured outputs).
- **Edit one block / suggestions** → `gpt-4.1-mini`.
- **Tiny classification (intent routing)** → `gpt-4.1-nano`.

Use **Structured Outputs** (JSON Schema response format), enable **prompt caching** (your big system prompt gets cached → up to ~90% cheaper on repeats), and use the **Batch API** (50% off) for any non-urgent bulk work. Confirm exact current prices at `https://openai.com/api/pricing` before your report's cost table.

### 6.2 The generation flow

```
User prompt ──► AiController::generate
   │
   ├─ 1. Build messages: [system prompt + schema rules, user prompt]
   ├─ 2. Call OpenAI with response_format = json_schema (Structured Output)
   ├─ 3. Receive guaranteed-valid JSON (the website schema)
   ├─ 4. Validate again server-side (defense in depth)
   ├─ 5. Inject real image URLs (placeholder service or Unsplash/Cloudinary)
   ├─ 6. Save as Page.schema + create a PageVersion
   └─ 7. Return schema to Vue → renderer paints the site
```

### 6.3 The system prompt (the secret sauce)

```text
You are a senior web designer that outputs ONLY a website layout as JSON.

Rules:
- Return a JSON object matching the provided schema EXACTLY. No prose, no HTML.
- Choose 5–8 sections that fit the user's purpose and industry.
- Always start with a "navbar" and end with a "footer".
- Pick a tasteful theme: a primary color suited to the brand, readable fonts,
  and a light/dark mode that fits the mood.
- Write realistic, specific marketing copy — never "Lorem ipsum".
- For images, use the string "PLACEHOLDER:<short description>"; the server
  will replace these with real image URLs.
- Keep headings short and punchy; body copy concise.

You will be given a user request describing the website they want.
```

The `PLACEHOLDER:<desc>` trick lets you swap in real images server-side (Unsplash API, Cloudinary, or a stock set) instead of having the model hallucinate broken URLs.

### 6.4 The OpenAI service (Laravel)

```php
// app/Services/AiWebsiteService.php
namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class AiWebsiteService
{
    public function generate(string $userPrompt): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1',
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name'   => 'website_schema',
                    'strict' => true,
                    'schema' => $this->jsonSchema(), // §6.5
                ],
            ],
        ]);

        $json = $response->choices[0]->message->content;
        $schema = json_decode($json, true);

        // Defense in depth: validate + repair placeholders
        $schema = app(SchemaValidator::class)->validate($schema);
        $schema = app(ImageResolver::class)->resolvePlaceholders($schema);

        // (optional) log tokens for cost tracking
        // $response->usage->promptTokens / completionTokens

        return $schema;
    }

    private function systemPrompt(): string { /* the prompt above */ }
    private function jsonSchema(): array { /* §6.5 */ }
}
```

### 6.5 Designing the JSON Schema for Structured Outputs

Structured Outputs requires every property to be listed and `additionalProperties: false`. Because blocks are a *union* of types, model each block with the fields it can have and let the model fill only the relevant ones, OR use `anyOf` per block type (cleaner but more verbose). A pragmatic, reliable shape:

```jsonc
{
  "type": "object",
  "additionalProperties": false,
  "required": ["version", "meta", "theme", "sections"],
  "properties": {
    "version": { "type": "integer" },
    "meta": {
      "type": "object", "additionalProperties": false,
      "required": ["title", "description", "language"],
      "properties": {
        "title": { "type": "string" },
        "description": { "type": "string" },
        "language": { "type": "string" }
      }
    },
    "theme": {
      "type": "object", "additionalProperties": false,
      "required": ["primaryColor","backgroundColor","textColor","fontHeading","fontBody","radius","mode"],
      "properties": {
        "primaryColor": { "type": "string" },
        "backgroundColor": { "type": "string" },
        "textColor": { "type": "string" },
        "fontHeading": { "type": "string" },
        "fontBody": { "type": "string" },
        "radius": { "type": "string", "enum": ["none","sm","md","lg","xl","full"] },
        "mode": { "type": "string", "enum": ["light","dark"] }
      }
    },
    "sections": {
      "type": "array",
      "items": {
        "type": "object", "additionalProperties": false,
        "required": ["id","type","props"],
        "properties": {
          "id":   { "type": "string" },
          "type": { "type": "string",
                    "enum": ["navbar","hero","features","gallery","about",
                             "services","testimonials","pricing","stats",
                             "faq","team","cta","contact","footer"] },
          "props": { "type": "object" }   // free-form; validate per type in PHP
        }
      }
    }
  }
}
```

Then in `SchemaValidator` you check, per `type`, that `props` has the expected keys — this catches the rare bad output and keeps the renderer safe.

### 6.6 Editing an existing site with AI ("make the hero darker")

Send the **current schema + the instruction** and ask for the patched schema (or a JSON Patch). Use `gpt-4.1-mini` to keep it cheap:

```text
System: You edit an existing website JSON. Apply the user's change and return
the full updated JSON matching the schema. Change only what is requested.
User: <instruction>
Context: <current schema JSON>
```

For surgical edits, ask for a list of operations like
`[{"op":"set","path":"sections[1].props.heading","value":"..."}]` and apply them server-side — cheaper tokens and safer.

### 6.7 Streaming (nice-to-have polish)

Stream tokens so the user sees generation progress. Laravel returns a streamed response; Vue reads it with `fetch` + `ReadableStream`. For the schema use-case, streaming the JSON is awkward — better to stream a *status* ("Choosing layout… writing copy… picking theme…") while the structured call runs in a queued job, then push the final schema. Looks great in a demo.

### 6.8 Cost & abuse guardrails (put this in your report)

- Per-plan monthly generation cap (free = N generations) enforced via `ai_generations` count.
- Rate limit the AI route (`throttle` middleware).
- `max_completion_tokens` cap on every call.
- Cache the system prompt (prompt caching) and reuse it.
- Log tokens + estimated cost per generation for the dashboard.

---

## 7. The Editor (the core UX)

Three panels around a live canvas.

### 7.1 Pinia store — single source of truth

```js
// resources/js/stores/editor.js
import { defineStore } from 'pinia'

export const useEditor = defineStore('editor', {
  state: () => ({
    schema: null,        // the website schema
    selectedId: null,    // currently selected block
    history: [],         // past schemas (undo)
    future: [],          // redo
    dirty: false,
  }),
  actions: {
    setSchema(s) { this.schema = s; this.dirty = true },
    select(id) { this.selectedId = id },
    updateProps(id, patch) {
      this.pushHistory()
      const b = this.schema.sections.find(s => s.id === id)
      b.props = { ...b.props, ...patch }
      this.dirty = true
    },
    reorder(newSections) { this.pushHistory(); this.schema.sections = newSections },
    addBlock(block, index) { this.pushHistory(); this.schema.sections.splice(index, 0, block) },
    removeBlock(id) {
      this.pushHistory()
      this.schema.sections = this.schema.sections.filter(s => s.id !== id)
    },
    pushHistory() { this.history.push(JSON.parse(JSON.stringify(this.schema))); this.future = [] },
    undo() { if (this.history.length) { this.future.push(this.schema); this.schema = this.history.pop() } },
    redo() { if (this.future.length) { this.history.push(this.schema); this.schema = this.future.pop() } },
  },
})
```

### 7.2 The renderer (turns blocks → Vue components)

```vue
<!-- resources/js/components/builder/BlockRenderer.vue -->
<script setup>
import BlockNavbar  from './blocks/BlockNavbar.vue'
import BlockHero    from './blocks/BlockHero.vue'
import BlockFeatures from './blocks/BlockFeatures.vue'
import BlockGallery from './blocks/BlockGallery.vue'
import BlockAbout   from './blocks/BlockAbout.vue'
import BlockContact from './blocks/BlockContact.vue'
import BlockFooter  from './blocks/BlockFooter.vue'

const map = {
  navbar: BlockNavbar, hero: BlockHero, features: BlockFeatures,
  gallery: BlockGallery, about: BlockAbout, contact: BlockContact, footer: BlockFooter,
}
defineProps({ block: Object, editable: Boolean })
</script>

<template>
  <component :is="map[block.type]" :props="block.props" :editable="editable" :id="block.id" />
</template>
```

The canvas loops the sections:

```vue
<div class="canvas" :style="themeVars(schema.theme)">
  <BlockRenderer
    v-for="b in schema.sections" :key="b.id"
    :block="b" :editable="true"
    @click="editor.select(b.id)" />
</div>
```

`themeVars()` returns CSS variables (`--primary`, `--font-heading`, …) so every block styles itself from the theme. Change the theme → whole site reskins instantly.

### 7.3 Inline editing

Inside each block component, make text `contenteditable` and emit changes:

```vue
<h1
  :contenteditable="editable" @blur="onEdit($event, 'heading')"
  class="text-5xl font-bold">{{ props.heading }}</h1>
```

```js
function onEdit(e, key) {
  useEditor().updateProps(props.id, { [key]: e.target.innerText })
}
```

### 7.4 Drag & drop reordering

```vue
<draggable :list="schema.sections" item-key="id" handle=".drag-handle"
           @end="editor.reorder(schema.sections)">
  <template #item="{ element }">
    <BlockRenderer :block="element" :editable="true" />
  </template>
</draggable>
```

### 7.5 The inspector panel

When a block is selected, show a form bound to its `props` (heading, image, columns, colors). Add an **"Ask AI"** box: "rewrite this in a friendlier tone" → calls the edit endpoint (§6.6) scoped to that block.

### 7.6 Autosave

Debounce schema changes and POST to `/projects/{id}/pages/{page}/autosave`. Snapshot a `page_version` periodically and before each AI edit.

```js
import { watchDebounced } from '@vueuse/core'
watchDebounced(() => editor.schema, save, { debounce: 1200, deep: true })
```

---

## 8. Live Preview (responsive)

Reuse the **same** `BlockRenderer` with `editable=false` inside a width-constrained wrapper. Toggle Desktop / Tablet / Mobile by changing the wrapper width (e.g. 1280 / 768 / 390 px) — a true responsive preview without an iframe. For a fully isolated preview, render into an `<iframe>` whose `srcdoc` is the exported HTML (§9).

```vue
<div :style="{ width: previewWidth + 'px' }" class="mx-auto shadow-xl transition-all">
  <BlockRenderer v-for="b in schema.sections" :key="b.id" :block="b" :editable="false" />
</div>
```

---

## 9. Export & Deployment

### 9.1 Export to static HTML

Build an `ExportService` that maps each block type to an HTML string template, assembles the page, inlines the theme as CSS variables, and includes Tailwind (via CDN for simplicity, or a prebuilt CSS file for production).

```php
// app/Services/ExportService.php
public function toHtml(array $schema): string
{
    $body = collect($schema['sections'])
        ->map(fn ($b) => $this->renderBlock($b))   // per-type template
        ->implode("\n");

    return view('export.shell', [
        'meta'  => $schema['meta'],
        'theme' => $schema['theme'],
        'body'  => $body,
    ])->render();
}
```

Offer two outputs:
1. **Single `index.html`** (Tailwind CDN) — instant, works anywhere.
2. **Zip bundle** (`index.html`, `style.css`, `/assets`) — clean source export, satisfies the "code export" objective.

> Pro tip: your block HTML templates should mirror your Vue block markup so the exported site looks identical to the editor. Keep them side by side.

### 9.2 One-click publish

- **Subdomain hosting (simplest, most impressive):** serve published sites at `username-project.yourbuilder.com` — store the schema, render on request via a public route. No external deploy needed; instant publish/unpublish.
- **Vercel/Netlify deploy:** push the exported static bundle via their deploy API and save the returned URL in `deployments`.
- **Custom domain (Service Fee feature):** let Pro users point a domain; document DNS/CNAME steps.

A Vercel connector is already available in your environment if you want programmatic deploys later.

---

## 10. Auth, Roles, Analytics, Collaboration

- **Auth & roles:** Breeze covers register/login/profile. Add a `role` (user/admin) and `plan` column; gate features with Laravel **Policies** + **Gates**.
- **Analytics (visitor tracking):** drop a tiny JS beacon on published pages that POSTs `pageview`/`click` to `/track`. Aggregate in a dashboard (Chart.js or a Vue chart lib): views, top pages, referrers, devices. This directly fills your "Analytics & Tracking" box.
- **Real-time collaboration (future / bonus):** Laravel Reverb (WebSockets) or Pusher broadcasts schema patches; clients merge them. For the MVP, "Share with others" (read-only link) + version history is enough — full multiplayer is genuinely hard; keep it as a documented future enhancement.

---

## 11. Routes (the essential ones)

```php
// Inertia (pages)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index']);
    Route::get('/projects/{project}/editor', [EditorController::class, 'show']);
});

// JSON / actions
Route::middleware('auth')->group(function () {
    Route::post('/ai/generate',  [AiController::class, 'generate'])->middleware('throttle:ai');
    Route::post('/ai/edit-block',[AiController::class, 'editBlock'])->middleware('throttle:ai');
    Route::post('/projects',     [ProjectController::class, 'store']);
    Route::post('/projects/{project}/pages/{page}/autosave', [PageController::class, 'autosave']);
    Route::get('/projects/{project}/export', [ExportController::class, 'download']);
    Route::post('/projects/{project}/publish', [PublishController::class, 'publish']);
    Route::get('/projects/{project}/versions', [VersionController::class, 'index']);
    Route::post('/versions/{version}/restore', [VersionController::class, 'restore']);
});

// Public
Route::get('/s/{slug}', [PublicSiteController::class, 'show']);   // published site
Route::post('/track', [AnalyticsController::class, 'store']);     // beacon
```

---

## 12. Folder Structure (suggested)

```
app/
  Http/Controllers/{Ai,Project,Page,Editor,Export,Publish,Version,Analytics}Controller.php
  Services/{AiWebsiteService,SchemaValidator,ImageResolver,ExportService}.php
  Models/{User,Project,Page,PageVersion,Asset,AiGeneration,Deployment}.php
resources/js/
  Pages/{Dashboard,Editor}.vue                ← Inertia pages
  components/builder/
    BlockRenderer.vue
    Inspector.vue
    BlockTree.vue
    PromptBar.vue
    blocks/{BlockNavbar,BlockHero,BlockFeatures,BlockGallery,BlockAbout,BlockContact,BlockFooter}.vue
  stores/editor.js
resources/views/export/{shell.blade.php, blocks/*.blade.php}
```

---

## 13. Implementation Roadmap (phase by phase)

Build vertically: get a thin slice working end-to-end early, then deepen.

**Phase 0 — Setup (Week 1)**
Laravel + Breeze (Inertia/Vue), Tailwind, MySQL, OpenAI key, repo + Git. Auth works.

**Phase 1 — Schema + Renderer (Weeks 2–3)** ← *most important*
- Finalize the website schema (§4).
- Build 4 blocks: navbar, hero, gallery, footer.
- Hardcode a sample schema, render it. Add theme CSS variables.
- **Milestone:** a JSON file renders as a styled page.

**Phase 2 — AI Generation (Weeks 4–5)**
- `AiWebsiteService` with Structured Outputs.
- PromptBar → generate → save Page → render.
- Image placeholder resolution.
- **Milestone:** type a prompt, get a real site.

**Phase 3 — The Editor (Weeks 6–8)**
- Inline editing, inspector form, add/remove blocks.
- Drag-and-drop reorder, theme panel, undo/redo, autosave.
- Add remaining blocks (features, about, contact, cta, testimonials, pricing).
- **Milestone:** fully editable, polished builder.

**Phase 4 — Preview, Export, Publish (Weeks 9–10)**
- Responsive preview toggle.
- ExportService → HTML + zip.
- Subdomain publish + public site route.
- **Milestone:** generate → edit → publish a live URL.

**Phase 5 — Auth depth, Analytics, Versions (Week 11)**
- Plans/roles, generation caps, analytics beacon + dashboard, version restore.

**Phase 6 — Polish, AI edit, Testing, Docs (Weeks 12–14)**
- AI block-level editing, error states, loading/streaming UX.
- Tests, seed demo data, write the report, record the demo video.

**Phase 7 — Buffer + Defense prep (Week 15)**
Rehearse the demo; prepare answers on architecture, cost, and security.

---

## 14. Testing

- **Backend (Pest/PHPUnit):** schema validation, generation endpoint (mock OpenAI), export output, auth/policy gates, generation caps.
- **Frontend:** Vitest for the store (reorder/undo/redo), a couple of component tests for blocks.
- **Manual demo script:** prompt → generate → edit → reorder → theme change → preview → publish → export. Time it; keep it under 3 minutes.

> Mock the OpenAI client in tests so you don't burn credits and tests stay deterministic.

---

## 15. Security Checklist

- API key server-side only; never returned to the browser.
- All AI routes behind `auth` + `throttle`.
- Validate + sanitize AI output before render (no raw HTML injection — you control the markup via components, so XSS surface is small; still escape user/AI text).
- Per-plan rate/usage limits; cap `max_completion_tokens`.
- CSRF (Inertia handles it), validated form requests, mass-assignment guards.
- Published-site routes are read-only; no user input echoed unescaped.

---

## 16. What Makes This the *Best* (your differentiators)

1. **Block-schema architecture** — the right engineering choice; explain it confidently.
2. **Structured Outputs** — reliable JSON, no broken HTML parsing.
3. **Edit with AI at the block level**, not just whole-site regeneration.
4. **True version history + undo/redo** from JSON snapshots.
5. **One-click publish to a live subdomain** *and* clean source export.
6. **Built-in analytics** for published sites.
7. **Cost-aware AI** (model cascade + caching + caps) — shows production thinking and supports your Business Prospect section.
8. **Polished, responsive blocks** — a beautiful demo beats a feature list.

### For your report / defense, be ready to explain
- Why JSON blocks over raw HTML.
- The generate → validate → render → export pipeline.
- How inline editing maps back to the schema.
- Cost model and how you cap it.
- What's MVP vs. future (real-time collab, explainable AI, advanced visual editor).

---

## 17. Quick Start Command Recap

```bash
composer create-project laravel/laravel ai-website-builder
cd ai-website-builder
composer require laravel/breeze --dev
php artisan breeze:install vue
composer require openai-php/laravel
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
npm install pinia vuedraggable@next @vueuse/core
# add OPENAI_API_KEY to .env
php artisan migrate
npm run dev   # + php artisan serve
```

Build the **schema and renderer first** (Phase 1). Once a JSON file paints a beautiful page, everything else — AI, editing, export — clicks into place.

Good luck. Ship the thin vertical slice early, then make the blocks gorgeous.
