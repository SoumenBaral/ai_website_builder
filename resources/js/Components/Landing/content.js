// All copy and data for the landing page lives here, so the section
// components stay purely presentational and the text is easy to edit.

export const navItems = [
    { id: 'showcase', label: 'Showcase' },
    { id: 'features', label: 'Features' },
    { id: 'how-it-works', label: 'How it works' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'faq', label: 'FAQ' },
];

export const heroPrompts = [
    'a portfolio for a wedding photographer',
    'a landing page for my SaaS',
    'a website for an Italian restaurant',
    'an online store for handmade candles',
    'a homepage for a fintech startup',
];

// The AI "thinking" steps shown in the editor mockup, in build order.
export const buildSteps = ['Planning layout', 'Writing copy', 'Choosing images', 'Applying theme', 'Ready to publish'];

export const stats = [
    { to: 10, suffix: 'K+', label: 'Websites built' },
    { to: 14, suffix: '+', label: 'Block types' },
    { prefix: '<', to: 30, suffix: 's', label: 'Average build time' },
    { to: 99.9, decimals: 1, suffix: '%', label: 'Uptime' },
];

export const audiences = [
    'Portfolios', 'SaaS landing pages', 'Restaurants', 'Online stores', 'Agencies', 'Personal blogs',
    'Events', 'Startups', 'Photographers', 'Consultants', 'Nonprofits', 'Fitness studios',
];

/*
 * Showcase: one sentence in, one website out. Each entry describes a small
 * "generated" site that ShowcaseSection renders as a live preview.
 */
export const showcase = [
    {
        key: 'portfolio',
        tab: 'Portfolio',
        prompt: 'A portfolio for a wedding photographer with a gallery and a booking form',
        brand: 'Lumen Studio',
        url: 'lumen.aiwebbuilder.app',
        eyebrow: 'Wedding & portrait photography',
        headline: 'Moments, beautifully kept.',
        sub: 'Natural, unposed photography for couples who want to remember how it felt.',
        cta: 'Check availability',
        sections: ['Navbar', 'Hero', 'Gallery', 'Testimonials', 'Booking', 'Footer'],
        layout: 'gallery',
        theme: { page: 'bg-[#f7f3ee]', ink: 'text-stone-900', muted: 'text-stone-500', btn: 'bg-stone-900 text-white', card: 'bg-white/70 border border-stone-900/5', serif: true },
        tiles: ['from-amber-200 to-rose-200', 'from-stone-300 to-stone-200', 'from-rose-200 to-orange-100', 'from-amber-100 to-stone-300'],
    },
    {
        key: 'saas',
        tab: 'SaaS',
        prompt: 'A landing page for a project management SaaS with features and pricing',
        brand: 'Flowbase',
        url: 'flowbase.aiwebbuilder.app',
        eyebrow: 'Project management, simplified',
        headline: 'Ship projects, not spreadsheets.',
        sub: 'Plan, track and deliver work in one calm workspace your whole team will actually use.',
        cta: 'Start free trial',
        sections: ['Navbar', 'Hero', 'Features', 'Pricing', 'FAQ', 'CTA'],
        layout: 'features',
        theme: { page: 'bg-[#0b0d1a]', ink: 'text-white', muted: 'text-indigo-200/70', btn: 'bg-indigo-500 text-white', card: 'bg-white/5 border border-white/10', serif: false },
        tiles: [
            { title: 'Boards', text: 'Kanban views for every team' },
            { title: 'Timelines', text: 'Plan sprints visually' },
            { title: 'Reports', text: 'Know what ships when' },
        ],
    },
    {
        key: 'restaurant',
        tab: 'Restaurant',
        prompt: 'A website for an Italian restaurant with our menu and online reservations',
        brand: 'Trattoria Sole',
        url: 'trattoriasole.aiwebbuilder.app',
        eyebrow: 'Family kitchen · Est. 1987',
        headline: 'Handmade pasta, every single day.',
        sub: 'Seasonal Italian cooking, rolled and cut by hand in our open kitchen.',
        cta: 'Reserve a table',
        sections: ['Navbar', 'Hero', 'Menu', 'Gallery', 'Reservations', 'Location'],
        layout: 'menu',
        theme: { page: 'bg-[#fbf6ec]', ink: 'text-[#3b1d12]', muted: 'text-[#8a6a58]', btn: 'bg-[#b4321f] text-white', card: 'bg-white/70 border border-[#3b1d12]/10', serif: true },
        tiles: [
            { title: 'Tagliatelle al ragù', price: '$18' },
            { title: 'Cacio e pepe', price: '$16' },
            { title: 'Burrata & heirloom tomato', price: '$14' },
        ],
    },
    {
        key: 'store',
        tab: 'Store',
        prompt: 'An online store for hand-poured soy candles with reviews and a newsletter',
        brand: 'Ember & Wick',
        url: 'emberandwick.aiwebbuilder.app',
        eyebrow: 'Small-batch soy candles',
        headline: 'Slow-burning. Hand-poured.',
        sub: 'Clean-burning candles in scents inspired by quiet mornings and long evenings.',
        cta: 'Shop the collection',
        sections: ['Navbar', 'Hero', 'Products', 'Reviews', 'Newsletter', 'Footer'],
        layout: 'products',
        theme: { page: 'bg-[#f2f4ef]', ink: 'text-emerald-950', muted: 'text-emerald-900/60', btn: 'bg-emerald-900 text-white', card: 'bg-white/80 border border-emerald-950/5', serif: false },
        tiles: [
            { title: 'Cedar & Smoke', price: '$28', color: 'from-stone-300 to-amber-200' },
            { title: 'Fig Leaf', price: '$26', color: 'from-emerald-200 to-lime-100' },
            { title: 'Sea Salt', price: '$26', color: 'from-sky-100 to-slate-200' },
        ],
    },
];

export const features = [
    { key: 'ai', icon: 'sparkles', title: 'AI-powered generation', desc: 'Describe your website in plain English and watch AI build every section — layout, copy and imagery — in seconds.', span: 'md:col-span-2' },
    { key: 'editor', icon: 'pencil', title: 'Visual editor', desc: 'Inline editing, drag & drop and real-time preview. Fine-tune every detail without touching code.', span: '' },
    { key: 'publish', icon: 'rocket', title: 'One-click publish', desc: 'Go live instantly with a shareable link, or export clean HTML / ZIP.', span: '' },
    { key: 'blocks', icon: 'grid', title: '14+ block types', desc: 'Navbar, hero, features, gallery, pricing, testimonials, FAQ, team and more.', span: '' },
    { key: 'theme', icon: 'swatch', title: 'Theme customization', desc: 'Colors, fonts, radius and dark mode — tuned globally in one place.', span: '' },
    { key: 'history', icon: 'clock', title: 'Version history', desc: 'Every change is saved automatically. Browse revisions and restore any version with one click.', span: 'md:col-span-2 lg:col-span-3' },
];

export const blockNames = ['Navbar', 'Hero', 'Features', 'Gallery', 'Pricing', 'Testimonials', 'FAQ', 'Team', 'Contact', 'Footer'];

export const steps = [
    { num: '01', icon: 'chat', title: 'Describe your website', desc: 'Type a prompt like “A modern portfolio for a photographer” and AI understands your vision.' },
    { num: '02', icon: 'sparkles', title: 'AI builds it instantly', desc: 'A complete, multi-section website with real content, images and styling — in seconds.' },
    { num: '03', icon: 'rocket', title: 'Customize & publish', desc: 'Polish it in the visual editor, adjust the theme, then publish or export in one click.' },
];

export const comparison = {
    old: {
        title: 'The old way',
        points: [
            'Weeks of design and development',
            'Hiring designers and developers',
            'Learning complex tools or code',
            'Waiting on someone for every small change',
        ],
    },
    new: {
        title: 'With AiWebBuilder',
        points: [
            'A complete website in under 30 seconds',
            'Just describe it in plain English',
            'Edit everything visually — no code',
            'Change anything, anytime, yourself',
        ],
    },
};

export const plans = [
    { name: 'Free', price: '$0', blurb: 'For trying things out and personal projects.', features: ['10 AI generations / month', 'All 14+ block types', 'HTML / ZIP export', 'Publish with a shared link', 'Version history'], cta: 'Get started free', highlight: false },
    { name: 'Pro', price: '$19', blurb: 'For creators and freelancers shipping often.', features: ['100 AI generations / month', 'Everything in Free', 'Priority AI processing', 'Analytics dashboard', 'Custom themes'], cta: 'Upgrade to Pro', highlight: true },
    { name: 'Team', price: '$49', blurb: 'For teams building sites together.', features: ['500 AI generations / month', 'Everything in Pro', 'Team collaboration', 'Priority support', 'Custom branding'], cta: 'Start Team plan', highlight: false },
];

export const faqs = [
    { q: 'Do I need to know how to code?', a: 'No. You describe what you want in plain English, and the visual editor lets you change text, images, layout and theme by clicking and dragging.' },
    { q: 'Can I edit the website after the AI generates it?', a: 'Yes — everything is editable. Edit inline, reorder or add blocks, tweak the theme, or ask the AI to rewrite a single section.' },
    { q: 'How do I put my website online?', a: 'Publish with one click to get a shareable link, or export the site as HTML / ZIP and host it anywhere you like.' },
    { q: 'What happens if I make a mistake?', a: 'Every change is saved to your version history, so you can browse earlier revisions and restore any of them with one click.' },
    { q: 'Is there really a free plan?', a: 'Yes. The Free plan includes 10 AI generations per month, every block type, export and publishing — no credit card required.' },
];
