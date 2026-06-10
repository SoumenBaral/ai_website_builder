export const BLOCK_TYPES = {
    navbar: {
        label: 'Navbar',
        icon: '🧭',
        defaultProps: {
            brand: 'My Site',
            links: [
                { label: 'Home', href: '#' },
                { label: 'About', href: '#about' },
                { label: 'Contact', href: '#contact' },
            ],
            ctaText: 'Get Started',
            ctaHref: '#contact',
        },
    },
    hero: {
        label: 'Hero',
        icon: '🎯',
        defaultProps: {
            heading: 'Welcome to Our Website',
            subheading: 'We help you build amazing things.',
            ctaText: 'Get Started',
            ctaHref: '#',
            image: 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&q=80',
            align: 'center',
            layout: 'center',
            overlayOpacity: 50,
            minHeight: 'lg',
        },
    },
    features: {
        label: 'Features',
        icon: '✨',
        defaultProps: {
            heading: 'Our Features',
            image: '',
            columns: 3,
            align: 'center',
            items: [
                { icon: '🚀', title: 'Fast', description: 'Blazing fast performance.' },
                { icon: '🔒', title: 'Secure', description: 'Enterprise-grade security.' },
                { icon: '🎨', title: 'Beautiful', description: 'Stunning modern design.' },
            ],
        },
    },
    gallery: {
        label: 'Gallery',
        icon: '🖼️',
        defaultProps: {
            heading: 'Our Work',
            columns: 3,
            images: [
                { src: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&q=80', caption: 'Project One' },
                { src: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600&q=80', caption: 'Project Two' },
                { src: 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600&q=80', caption: 'Project Three' },
            ],
        },
    },
    about: {
        label: 'About',
        icon: '👤',
        defaultProps: {
            heading: 'About Us',
            body: 'We are a passionate team dedicated to creating amazing digital experiences.',
            image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&q=80',
            imagePosition: 'right',
        },
    },
    contact: {
        label: 'Contact',
        icon: '📧',
        defaultProps: {
            heading: 'Get in Touch',
            image: '',
            email: 'hello@example.com',
            phone: '+1 234 567 890',
            fields: ['name', 'email', 'message'],
            layout: 'split',
        },
    },
    services: {
        label: 'Services',
        icon: '🛠️',
        defaultProps: {
            heading: 'Our Services',
            image: '',
            columns: 3,
            align: 'center',
            items: [
                { icon: '💻', title: 'Web Development', description: 'Custom websites built for your needs.' },
                { icon: '📱', title: 'Mobile Apps', description: 'iOS and Android applications.' },
                { icon: '🎨', title: 'UI/UX Design', description: 'Beautiful, user-centered design.' },
            ],
        },
    },
    testimonials: {
        label: 'Testimonials',
        icon: '💬',
        defaultProps: {
            heading: 'What Our Clients Say',
            columns: 3,
            items: [
                { quote: 'Amazing work! Exceeded all expectations.', author: 'Sarah Johnson', role: 'CEO, TechCo' },
                { quote: 'Professional, fast, and reliable.', author: 'Mike Chen', role: 'Founder, StartupXYZ' },
                { quote: 'Highly recommend their services.', author: 'Emily Davis', role: 'Marketing Director' },
            ],
        },
    },
    pricing: {
        label: 'Pricing',
        icon: '💰',
        defaultProps: {
            heading: 'Simple Pricing',
            image: '',
            columns: 3,
            plans: [
                { name: 'Starter', price: '$9', period: '/month', features: ['1 Project', '5GB Storage', 'Email Support'], ctaText: 'Get Started', highlighted: false },
                { name: 'Pro', price: '$29', period: '/month', features: ['10 Projects', '50GB Storage', 'Priority Support', 'Analytics'], ctaText: 'Go Pro', highlighted: true },
                { name: 'Enterprise', price: '$99', period: '/month', features: ['Unlimited Projects', '500GB Storage', '24/7 Support', 'Custom Domain', 'API Access'], ctaText: 'Contact Us', highlighted: false },
            ],
        },
    },
    stats: {
        label: 'Stats',
        icon: '📊',
        defaultProps: {
            heading: 'By the Numbers',
            image: '',
            columns: 4,
            items: [
                { value: '500+', label: 'Projects Completed' },
                { value: '98%', label: 'Client Satisfaction' },
                { value: '50+', label: 'Team Members' },
                { value: '24/7', label: 'Support' },
            ],
        },
    },
    faq: {
        label: 'FAQ',
        icon: '❓',
        defaultProps: {
            heading: 'Frequently Asked Questions',
            image: '',
            maxWidth: 'md',
            items: [
                { question: 'How do I get started?', answer: 'Simply sign up for a free account and follow our getting started guide.' },
                { question: 'Can I cancel anytime?', answer: 'Yes, you can cancel your subscription at any time with no hidden fees.' },
                { question: 'Do you offer support?', answer: 'We offer 24/7 email support and priority support for Pro plans.' },
            ],
        },
    },
    team: {
        label: 'Team',
        icon: '👥',
        defaultProps: {
            heading: 'Meet Our Team',
            columns: 4,
            members: [
                { name: 'Alex Smith', role: 'CEO & Founder', image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&q=80' },
                { name: 'Jamie Lee', role: 'Lead Designer', image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=300&q=80' },
                { name: 'Sam Wilson', role: 'CTO', image: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&q=80' },
            ],
        },
    },
    cta: {
        label: 'Call to Action',
        icon: '📢',
        defaultProps: {
            heading: 'Ready to Get Started?',
            subheading: 'Join thousands of happy customers today.',
            ctaText: 'Start Now',
            ctaHref: '#',
            image: '',
            align: 'center',
        },
    },
    custom: {
        label: 'Custom',
        icon: '🧩',
        defaultProps: {
            columns: 1,
            gap: 'md',
            padding: 'lg',
            align: 'center',
            elements: [
                { type: 'heading', text: 'Custom Section', size: '3xl', align: 'center' },
                { type: 'text', text: 'Add your own elements to build any layout you want.', size: 'base', align: 'center' },
            ],
        },
    },
    footer: {
        label: 'Footer',
        icon: '🔻',
        defaultProps: {
            text: '© 2026 My Site. All rights reserved.',
        },
    },
};

export function createBlock(type) {
    const def = BLOCK_TYPES[type];
    if (!def) throw new Error(`Unknown block type: ${type}`);
    return {
        id: `blk_${type}_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`,
        type,
        props: JSON.parse(JSON.stringify(def.defaultProps)),
    };
}

export function defaultSchema() {
    return {
        version: 1,
        meta: { title: 'Untitled Site', description: '', language: 'en' },
        theme: {
            primaryColor: '#2563eb',
            secondaryColor: '#0f172a',
            backgroundColor: '#ffffff',
            textColor: '#0f172a',
            fontHeading: 'Poppins',
            fontBody: 'Inter',
            radius: 'lg',
            mode: 'light',
        },
        sections: [],
    };
}

export function sampleSchema() {
    return {
        version: 1,
        meta: {
            title: 'Aperture — Photography Portfolio',
            description: 'Portfolio of a Dhaka-based photographer',
            language: 'en',
        },
        theme: {
            primaryColor: '#2563eb',
            secondaryColor: '#0f172a',
            backgroundColor: '#ffffff',
            textColor: '#0f172a',
            fontHeading: 'Poppins',
            fontBody: 'Inter',
            radius: 'lg',
            mode: 'light',
        },
        sections: [
            {
                id: 'blk_navbar_01',
                type: 'navbar',
                props: {
                    brand: 'Aperture',
                    links: [
                        { label: 'Gallery', href: '#gallery' },
                        { label: 'About', href: '#about' },
                        { label: 'Contact', href: '#contact' },
                    ],
                    ctaText: 'Book a Shoot',
                    ctaHref: '#contact',
                },
            },
            {
                id: 'blk_hero_01',
                type: 'hero',
                props: {
                    heading: 'Capturing moments that last forever',
                    subheading: 'Wedding, portrait and street photography in Dhaka.',
                    ctaText: 'View my work',
                    ctaHref: '#gallery',
                    image: 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=1200&q=80',
                    align: 'center',
                },
            },
            {
                id: 'blk_features_01',
                type: 'features',
                props: {
                    heading: 'Why Choose Me',
                    items: [
                        { icon: '📸', title: '6+ Years Experience', description: 'Capturing moments across Dhaka and beyond.' },
                        { icon: '🎨', title: 'Creative Vision', description: 'Every shoot is a unique artistic expression.' },
                        { icon: '⚡', title: 'Quick Delivery', description: 'Get your edited photos within 48 hours.' },
                    ],
                },
            },
            {
                id: 'blk_gallery_01',
                type: 'gallery',
                props: {
                    heading: 'Selected Work',
                    columns: 3,
                    images: [
                        { src: 'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', caption: 'Wedding ceremony' },
                        { src: 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=600&q=80', caption: 'Golden hour portrait' },
                        { src: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&q=80', caption: 'Street photography' },
                        { src: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=600&q=80', caption: 'Nature walk' },
                        { src: 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=600&q=80', caption: 'Landscape' },
                        { src: 'https://images.unsplash.com/photo-1447684808650-354ae64db5b8?w=600&q=80', caption: 'Event coverage' },
                    ],
                },
            },
            {
                id: 'blk_about_01',
                type: 'about',
                props: {
                    heading: 'About Me',
                    body: "I'm a photographer with 6 years of experience specializing in wedding, portrait, and street photography. Based in Dhaka, I've covered over 200 events and worked with clients across Bangladesh. My goal is to tell your story through authentic, emotional imagery.",
                    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80',
                },
            },
            {
                id: 'blk_cta_01',
                type: 'cta',
                props: {
                    heading: 'Ready to Create Something Beautiful?',
                    subheading: 'Let\'s discuss your next project and bring your vision to life.',
                    ctaText: 'Book Now',
                    ctaHref: '#contact',
                },
            },
            {
                id: 'blk_contact_01',
                type: 'contact',
                props: {
                    heading: 'Get in Touch',
                    email: 'hello@aperture.com',
                    phone: '+8801XXXXXXXXX',
                    fields: ['name', 'email', 'message'],
                },
            },
            {
                id: 'blk_footer_01',
                type: 'footer',
                props: { text: '© 2026 Aperture Photography. All rights reserved.' },
            },
        ],
    };
}
