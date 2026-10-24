<?php

namespace App\Services\Photos;

/**
 * What `photos:import` searches for. Each topic is a concrete, photographable
 * subject that websites built here actually need, grouped by picker category.
 * ~125 topics × 20 photos ≈ 2,500 photos.
 */
final class PhotoTopics
{
    public const TOPICS = [
        'business' => [
            'modern office interior', 'business team meeting', 'startup founders working', 'coworking space laptop',
            'handshake business deal', 'customer service headset', 'accountant finance desk', 'marketing team whiteboard',
            'corporate skyscraper', 'woman working laptop cafe', 'consultant presenting chart', 'lawyer office books',
        ],
        'food' => [
            'restaurant dinner table', 'chef cooking kitchen', 'coffee latte art', 'cafe interior cozy', 'bakery bread croissant',
            'pizza wood oven', 'burger fries', 'sushi platter', 'healthy salad bowl', 'dessert cake slice',
            'cocktail bar drinks', 'breakfast pancakes', 'pasta italian dish', 'street food market', 'ice cream cone',
            'tea cup ceremony', 'biryani rice dish', 'grilled steak', 'fresh juice smoothie', 'wine glasses cheers',
        ],
        'fashion' => [
            'fashion model editorial', 'clothing boutique rack', 'sneakers product shot', 'leather handbag', 'wristwatch luxury',
            'sunglasses summer', 'mens suit tailor', 'womens dress elegant', 'jewelry necklace gold', 'denim jeans outfit',
            'streetwear hoodie', 'traditional saree', 'kids clothing', 'shoes store display',
        ],
        'beauty' => [
            'hair salon stylist', 'barbershop haircut', 'spa massage relax', 'skincare products', 'makeup artist brushes',
            'nail salon manicure', 'perfume bottle', 'facial treatment', 'bridal makeup',
        ],
        'health' => [
            'doctor with patient', 'dentist clinic', 'hospital corridor', 'pharmacy shelves', 'nurse smiling',
            'physiotherapy session', 'medical equipment', 'eye doctor optometrist', 'veterinarian with dog', 'mental health therapy',
        ],
        'fitness' => [
            'gym workout weights', 'yoga class studio', 'personal trainer', 'running outdoors', 'cycling road bike',
            'swimming pool', 'football match', 'cricket player', 'basketball court', 'boxing training', 'martial arts',
        ],
        'nature' => [
            'mountain landscape sunrise', 'forest trail', 'beach sunset', 'ocean waves', 'lake reflection',
            'desert dunes', 'waterfall', 'flower field', 'autumn leaves', 'night sky stars', 'rice paddy green',
            'tropical palm trees', 'snow winter', 'river boat',
        ],
        'travel' => [
            'hotel room luxury', 'resort pool', 'airplane window', 'backpacker hiking', 'city skyline night',
            'old town street europe', 'temple asia', 'road trip car', 'cruise ship', 'tourist map', 'camping tent',
        ],
        'home' => [
            'modern house exterior', 'living room interior', 'kitchen modern', 'bedroom cozy', 'bathroom tiles',
            'real estate agent keys', 'apartment building', 'construction site workers', 'architect blueprint', 'garden landscaping',
            'furniture sofa', 'plumber working', 'electrician', 'house painting', 'cleaning service',
        ],
        'tech' => [
            'developer coding screen', 'smartphone app', 'server room data center', 'robot ai', 'cybersecurity lock',
            'analytics dashboard', 'gaming setup', 'drone flying', 'electric car charging', 'circuit board', '3d printer',
            'video editing studio', 'podcast microphone',
        ],
        'education' => [
            'students classroom', 'university campus', 'library books', 'online learning laptop', 'teacher whiteboard',
            'kids drawing', 'graduation cap', 'science lab', 'music lesson piano', 'language tutoring',
        ],
        'events' => [
            'wedding couple', 'wedding ceremony flowers', 'concert crowd', 'dj party lights', 'birthday celebration',
            'conference stage speaker', 'festival fireworks', 'live band guitar', 'corporate event', 'photographer camera',
        ],
        'animals' => [
            'dog portrait', 'cat portrait', 'puppy playing', 'pet grooming', 'horse farm', 'aquarium fish', 'bird colorful',
        ],
        'shop' => [
            'online shopping phone', 'grocery store aisle', 'delivery courier package', 'retail store checkout',
            'product packaging box', 'flower shop', 'bookstore', 'electronics store', 'farmers market', 'warehouse logistics',
            'handmade crafts', 'candle product', 'cosmetics product flat lay',
        ],
        'backgrounds' => [
            'abstract gradient', 'marble texture', 'wood texture', 'concrete wall', 'bokeh lights', 'geometric pattern',
            'minimal white desk', 'dark texture', 'paper texture', 'colorful paint',
        ],
        'people' => [
            'smiling woman portrait', 'smiling man portrait', 'businesswoman headshot', 'businessman headshot',
            'young woman portrait natural', 'young man portrait', 'senior woman portrait', 'senior man portrait',
            'woman hijab portrait', 'man beard portrait', 'south asian woman portrait', 'south asian man portrait',
            'doctor portrait', 'chef portrait', 'team members diverse',
        ],
        'services' => [
            'car mechanic garage', 'car wash', 'taxi driver', 'moving boxes', 'tailor sewing machine', 'printing press',
            'laundry service', 'security guard', 'solar panels roof', 'farm tractor', 'fishing boat', 'textile factory',
        ],
        'creative' => [
            'artist painting studio', 'graphic designer tablet', 'pottery hands clay', 'photography studio lights',
            'dance performance', 'film camera set', 'architecture modern building', 'interior design moodboard',
        ],
        'charity' => [
            'volunteers helping', 'charity donation', 'children school rural', 'community gathering', 'planting trees',
        ],
    ];

    /** @return array<int, array{topic: string, category: string}> */
    public static function all(): array
    {
        $out = [];
        foreach (self::TOPICS as $category => $topics) {
            foreach ($topics as $topic) {
                $out[] = ['topic' => $topic, 'category' => $category];
            }
        }

        return $out;
    }
}
