# AI Website Builder — Session Log

## Session: June 9–10, 2026

### Project Overview
**AI-Powered No-Code Website Builder** — Final-year project for Nur-uddin Ahmed Nuhin (210033) & Shakifuzzan khan Showhan (210010).

**Stack:** Laravel 13 + Inertia.js v2 + Vue 3 + Pinia + Tailwind CSS v4 + OpenAI API + SQLite (dev)

**Architecture:** Users type a prompt → AI generates a JSON website schema → Vue renders it as blocks → Users can inline-edit, drag-drop, change themes → Export to HTML/ZIP or publish live at `/s/{slug}`

---

## What Was Implemented (All 7 Phases Complete)

### Phase 0: Setup ✅
- Installed Laravel Breeze with Vue/Inertia (auth scaffolding)
- Installed: `openai-php/laravel`, `pinia`, `vuedraggable@next`, `@vueuse/core`, `axios`
- Configured Pinia in `resources/js/app.js`
- Database: SQLite (switched from MySQL for dev simplicity)
- Created `resources/js/bootstrap.js` (was missing after Breeze install)

### Phase 1: Schema + Renderer ✅
- **8 migrations created & run:** projects, pages, page_versions, assets, ai_generations, deployments, analytics_events, + added plan/role to users
- **7 models with relationships:** Project, Page, PageVersion, Asset, AiGeneration, Deployment, AnalyticsEvent
- **Schema definition:** `resources/js/schema/defaults.js` — block type registry with 14 types and default props
- **Theme utility:** `resources/js/composables/useTheme.js` — converts theme object to CSS variables
- **8 MVP block components** built in `resources/js/Components/builder/blocks/`
- **BlockRenderer.vue** — maps block type string to Vue component
- **Demo page** at `/demo` with hardcoded photography portfolio schema

### Phase 2: AI Generation ✅
- **`app/Services/SchemaValidator.php`** — validates AI JSON output structure
- **`app/Services/ImageResolver.php`** — replaces `PLACEHOLDER:<desc>` with real Unsplash URLs
- **`app/Services/AiWebsiteService.php`** — calls OpenAI GPT-4.1 with structured outputs, logs tokens/cost
- **`app/Http/Controllers/AiController.php`** — generate + editBlock endpoints
- **`app/Http/Controllers/ProjectController.php`** — CRUD, dashboard
- **`app/Http/Controllers/EditorController.php`** — serves editor page
- **`app/Http/Controllers/PageController.php`** — autosave endpoint
- **`resources/js/Components/builder/PromptBar.vue`** — AI prompt input with animated status messages
- **`resources/js/Pages/Dashboard.vue`** — project grid with create modal
- Routes: `/dashboard`, `/projects`, `/projects/{id}/editor`, `/ai/generate`, `/ai/edit-block`

### Phase 3: Editor ✅
- **`resources/js/stores/editor.js`** — Pinia store: schema, selectedId, history/future (undo/redo), dirty/saving states
- **Inline editing** wired into all block components (contenteditable + blur → update store)
- **`resources/js/Components/builder/Inspector.vue`** — dynamic prop editor per block type + AI Assist box
- **`resources/js/Components/builder/BlockTree.vue`** — sidebar with block list, add/delete blocks
- **`resources/js/Components/builder/ThemePanel.vue`** — color pickers, font selectors, radius, dark/light mode
- **`resources/js/Pages/Editor.vue`** — full 3-panel layout with top bar (undo/redo, responsive preview, export, publish)
- **Autosave** with `watchDebounced` (1.2s delay)
- **Keyboard shortcuts:** Ctrl+Z undo, Ctrl+Shift+Z redo, Delete to remove selected block
- **Responsive preview toggles:** Desktop (100%), Tablet (768px), Mobile (390px)

### Phase 4: Export + Publish ✅
- **`app/Services/ExportService.php`** — toHtml() and toZip() methods
- **`resources/views/export/shell.blade.php`** — HTML export shell with Tailwind CDN + Google Fonts
- **14 Blade export templates** in `resources/views/export/blocks/` (one per block type)
- **`app/Http/Controllers/ExportController.php`** — download ZIP + HTML preview
- **`app/Http/Controllers/PublishController.php`** — publish/unpublish projects
- **`app/Http/Controllers/PublicSiteController.php`** — serves published sites at `/s/{slug}` with analytics beacon
- Editor top bar has Export ZIP, Publish, View Live, Unpublish buttons

### Phase 5: Auth, Analytics, Versions ✅
- **`app/Policies/ProjectPolicy.php`** — owner-only access control
- **Generation caps** in AiWebsiteService: Free=10/mo, Pro=100/mo, Team=500/mo
- **`app/Http/Controllers/VersionController.php`** — list versions, restore any version
- **`resources/js/Components/builder/VersionHistory.vue`** — slide-out panel with version list + restore
- **`app/Http/Controllers/AnalyticsController.php`** — beacon store (POST /track, no auth) + dashboard with stats
- **`app/Models/AnalyticsEvent.php`** — model for tracking
- **`resources/js/Pages/Analytics.vue`** — dashboard with daily views chart, top referrers, device breakdown
- CSRF excluded for `/track` endpoint in `bootstrap/app.php`
- Routes: `/projects/{id}/analytics`, `/projects/{id}/versions`, `/versions/{id}/restore`, `/s/{slug}`, `/track`

### Phase 6: Polish + Testing ✅
- **6 additional block components:** BlockServices, BlockTestimonials, BlockPricing, BlockStats, BlockFaq, BlockTeam
- **Schema defaults updated** with all 14 block types and their default props
- **BlockRenderer updated** to import and map all 14 block types
- **Demo seeder** (`database/seeders/DemoSeeder.php`): 2 sample projects — photography portfolio (published) + SaaS landing page (draft). Login: `demo@example.com` / `password`
- **40 Pest tests passing:**
  - `SchemaValidatorTest.php` — valid/invalid schema validation
  - `ProjectTest.php` — CRUD, home page creation, authorization
  - `ExportTest.php` — HTML export content, ZIP download
  - `PublishTest.php` — publish/unpublish, public site access

---

## File Structure Created

```
app/
  Http/Controllers/
    AiController.php
    AnalyticsController.php
    EditorController.php
    ExportController.php
    PageController.php
    ProjectController.php
    PublicSiteController.php
    PublishController.php
    VersionController.php
  Models/
    AiGeneration.php, AnalyticsEvent.php, Asset.php, Deployment.php,
    Page.php, PageVersion.php, Project.php, User.php (modified)
  Policies/
    ProjectPolicy.php
  Services/
    AiWebsiteService.php
    ExportService.php
    ImageResolver.php
    SchemaValidator.php

resources/js/
  schema/defaults.js
  composables/useTheme.js
  stores/editor.js
  Pages/
    Dashboard.vue, Editor.vue, Demo.vue, Analytics.vue
  Components/builder/
    BlockRenderer.vue, PromptBar.vue, BlockTree.vue,
    Inspector.vue, ThemePanel.vue, VersionHistory.vue
    blocks/
      BlockNavbar.vue, BlockHero.vue, BlockFeatures.vue,
      BlockGallery.vue, BlockAbout.vue, BlockContact.vue,
      BlockCta.vue, BlockFooter.vue, BlockServices.vue,
      BlockTestimonials.vue, BlockPricing.vue, BlockStats.vue,
      BlockFaq.vue, BlockTeam.vue

resources/views/export/
  shell.blade.php
  blocks/ (14 files — one per block type)

database/
  migrations/ (8 new migration files)
  seeders/DemoSeeder.php

tests/Feature/
  SchemaValidatorTest.php, ProjectTest.php,
  ExportTest.php, PublishTest.php
```

---

## Current Configuration
- **Database:** SQLite (`database/database.sqlite`)
- **APP_URL:** `http://websideBuilder.test`
- **OpenAI:** Requires `OPENAI_API_KEY` in `.env` for AI features
- **Demo user:** `demo@example.com` / `password` (seeded)

---

## What Could Be Done Next (Future Enhancements)
1. **Drag-and-drop integration** — vuedraggable is installed but needs `<draggable>` wrapper in Editor canvas
2. **Real-time collaboration** — Laravel Reverb/Pusher for multiplayer editing
3. **Custom domains** — Let Pro users point a domain to their published site
4. **Image upload** — Cloudinary/S3 integration for user-uploaded images (Asset model ready)
5. **Multi-page support** — Pages model supports it, UI needs page management
6. **Template library** — Pre-built schemas users can start from
7. **More block types** — Blog, video, map, countdown, form builder
8. **Vercel/Netlify deploy** — Programmatic deploy via their APIs
9. **Admin dashboard** — Usage stats, user management (role system ready)
10. **Frontend tests** — Vitest for Pinia store and component tests
11. **Streaming AI** — Show generation progress via SSE/WebSocket
12. **Dark mode for editor UI** — Currently only the generated site supports dark mode

---

## How to Run
```bash
# Terminal 1
npm run dev

# Terminal 2
php artisan serve

# Run tests
php artisan test

# Seed demo data (if fresh DB)
php artisan migrate:fresh
php artisan db:seed --class=DemoSeeder
```
