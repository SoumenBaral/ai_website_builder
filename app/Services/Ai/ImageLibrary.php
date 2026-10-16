<?php

namespace App\Services\Ai;

/**
 * Curated Unsplash photos used when no image API key is configured.
 *
 * Every id below was checked to load (HTTP 200) and reviewed visually so the
 * tags describe what is actually in the picture. Tags are matched against the
 * AI's "PLACEHOLDER:<query>" text; `portrait` entries are used for people.
 */
final class ImageLibrary
{
    /** id => tags (first tag is the primary subject) */
    public const PHOTOS = [
        // Work & business
        '1497366216548-37526070297c' => ['office', 'workspace', 'interior', 'corporate', 'business'],
        '1497366811353-6870744d04b2' => ['office', 'coworking', 'workspace', 'business', 'agency'],
        '1522071820081-009f0129c71c' => ['team', 'collaboration', 'meeting', 'startup', 'people', 'agency'],
        '1600880292203-757bb62b4baf' => ['team', 'meeting', 'business', 'celebration', 'people', 'consulting'],
        '1556761175-b413da4baf72' => ['team', 'office', 'meeting', 'business', 'people', 'corporate'],
        '1552664730-d307ca884978' => ['team', 'workshop', 'brainstorm', 'meeting', 'agency', 'marketing'],
        '1460925895917-afdab827c52f' => ['analytics', 'dashboard', 'laptop', 'data', 'marketing', 'saas', 'finance'],
        '1551288049-bebda4e38f71' => ['analytics', 'dashboard', 'charts', 'data', 'saas', 'software'],
        '1504384308090-c894fdcc538d' => ['office', 'coworking', 'startup', 'workspace', 'business'],
        '1521791136064-7986c2920216' => ['handshake', 'partnership', 'deal', 'business', 'consulting', 'agreement'],
        '1517245386807-bb43f82c33c4' => ['meeting', 'laptop', 'business', 'consulting', 'teamwork'],
        '1559136555-9303baea8ebd' => ['startup', 'office', 'coworking', 'workspace', 'modern'],
        '1450101499163-c8848c66ca85' => ['contract', 'signing', 'paperwork', 'legal', 'agreement', 'insurance'],
        '1554224155-6726b3ff858f' => ['finance', 'accounting', 'tax', 'calculator', 'bookkeeping'],
        '1579621970563-ebec7560ff3e' => ['money', 'savings', 'investment', 'finance', 'growth', 'bank'],
        '1589829545856-d10d557cf95f' => ['law', 'lawyer', 'justice', 'legal', 'attorney', 'court'],

        // Technology
        '1518770660439-4636190af475' => ['technology', 'circuit', 'hardware', 'electronics', 'tech'],
        '1550751827-4bd374c3f58b' => ['technology', 'cybersecurity', 'security', 'network', 'tech'],
        '1498050108023-c5249f4df085' => ['code', 'developer', 'programming', 'laptop', 'software', 'web'],
        '1555066931-4365d14bab8c' => ['code', 'programming', 'developer', 'software', 'screen'],
        '1517694712202-14dd9538aa97' => ['code', 'laptop', 'developer', 'programming', 'software'],
        '1531297484001-80022131f5a1' => ['laptop', 'technology', 'gadget', 'saas', 'product'],
        '1488590528505-98d2b5aba04b' => ['laptop', 'code', 'technology', 'developer', 'it'],
        '1511707171634-5f897ff02aa9' => ['smartphone', 'mobile', 'app', 'phone', 'technology'],

        // Food & drink
        '1504674900247-0877df9cc836' => ['food', 'dish', 'dinner', 'restaurant', 'cuisine', 'catering'],
        '1414235077428-338989a2e8c0' => ['restaurant', 'fine dining', 'chef', 'dish', 'plating'],
        '1517248135467-4c7edcad34c4' => ['restaurant', 'interior', 'dining room', 'bistro', 'bar'],
        '1555396273-367ea4eb4db5' => ['restaurant', 'interior', 'dining', 'bistro', 'brasserie'],
        '1565299624946-b28f40a0ae38' => ['pizza', 'italian', 'food', 'pizzeria'],
        '1546069901-ba9599a7e63c' => ['salad', 'healthy food', 'bowl', 'vegan', 'nutrition'],
        '1540189549336-e6e99c3679fe' => ['food', 'healthy food', 'salad', 'bowl', 'meal prep'],
        '1567620905732-2d1ec7ab7445' => ['pancakes', 'breakfast', 'brunch', 'dessert', 'cafe'],
        '1621996346565-e3dbc646d9a9' => ['pasta', 'italian', 'food', 'trattoria'],
        '1473093295043-cdd812d0e601' => ['pasta', 'italian', 'food', 'salad'],
        '1495474472287-4d71bcdd2085' => ['coffee', 'latte', 'cafe', 'barista', 'coffee shop'],
        '1509042239860-f550ce710b93' => ['coffee', 'cappuccino', 'cafe', 'latte art', 'coffee shop'],
        '1501339847302-ac426a4a7cbb' => ['cafe', 'coffee shop', 'storefront', 'shop'],
        '1554118811-1e0d58224f24' => ['cafe', 'coffee shop', 'interior', 'restaurant'],
        '1509440159596-0249088772ff' => ['bread', 'bakery', 'sourdough', 'baking', 'loaf'],
        '1555507036-ab1f4038808a' => ['pastry', 'bakery', 'croissant', 'baking', 'patisserie'],

        // Health, fitness & beauty
        '1534438327276-14e5300c3a48' => ['gym', 'fitness', 'weights', 'training', 'workout'],
        '1571019613454-1cb2f99b2d8b' => ['workout', 'fitness', 'exercise', 'home workout', 'pilates'],
        '1517836357463-d25dfeac3438' => ['gym', 'barbell', 'weightlifting', 'strength', 'crossfit'],
        '1599058917212-d750089bc07e' => ['fitness', 'battle ropes', 'training', 'gym', 'crossfit'],
        '1544367567-0f2fcb009e0b' => ['yoga', 'stretching', 'wellness', 'sunset', 'meditation'],
        '1506126613408-eca07ce68773' => ['yoga', 'meditation', 'mindfulness', 'wellness', 'calm'],
        '1540555700478-4be289fbecef' => ['spa', 'wellness', 'skincare', 'relaxation', 'towels'],
        '1544161515-4ab6ce6db874' => ['spa', 'massage', 'therapy', 'wellness', 'relaxation'],
        '1560066984-138dadb4c035' => ['salon', 'hair salon', 'barbershop', 'interior', 'beauty'],
        '1522337360788-8b13dee7a37e' => ['hair', 'hairstyle', 'salon', 'beauty', 'hairdresser'],
        '1487412947147-5cebf100ffc2' => ['makeup', 'beauty', 'cosmetics', 'makeup artist'],
        '1576091160399-112ba8d25d1d' => ['doctor', 'medical', 'healthcare', 'clinic', 'physician'],
        '1631217868264-e5b90bb7e133' => ['doctor', 'patient', 'healthcare', 'clinic', 'consultation', 'hospital'],
        '1579684385127-1ef15d508118' => ['laboratory', 'science', 'research', 'biotech', 'lab'],
        '1606811971618-4486d14f3f99' => ['dentist', 'dental', 'teeth', 'smile', 'orthodontist'],
        '1588776814546-1ffcf47267a5' => ['dentist', 'dental', 'x-ray', 'clinic'],

        // Weddings, events, creative
        '1519741497674-611481863552' => ['wedding', 'bouquet', 'bride', 'flowers', 'florist'],
        '1511285560929-80b456fea0bc' => ['wedding', 'ceremony', 'couple', 'bride', 'groom', 'venue'],
        '1465495976277-4387d4b0b4c6' => ['wedding', 'rings', 'couple', 'hands', 'engagement'],
        '1606216794074-735e91aa2c92' => ['wedding', 'couple', 'bride', 'groom', 'portrait', 'romance'],
        '1452587925148-ce544e77e70d' => ['camera', 'photography', 'photos', 'photographer', 'prints'],
        '1516035069371-29a1b244cc32' => ['camera', 'lens', 'photography', 'photographer', 'gear'],
        '1542038784456-1ea8e935640e' => ['photographer', 'photography', 'camera', 'outdoor shoot'],
        '1540575467063-178a50c2df87' => ['conference', 'event', 'audience', 'seminar', 'summit'],
        '1505373877841-8d25f7d46678' => ['event', 'presentation', 'conference', 'talk', 'workshop'],
        '1493225457124-a3eb161ffa5f' => ['concert', 'music', 'festival', 'performer', 'live music'],
        '1514525253161-7a46d19cd819' => ['concert', 'festival', 'party', 'nightlife', 'crowd', 'dj'],
        '1511671782779-c97d3d27a1d4' => ['music', 'microphone', 'singer', 'studio', 'karaoke'],
        '1478737270239-2f02b77fc618' => ['podcast', 'microphone', 'studio', 'radio', 'recording'],
        '1513364776144-60967b0f800f' => ['art', 'painting', 'paint', 'artist', 'creative'],
        '1460661419201-fd4cecdf8a8b' => ['art', 'paintbrushes', 'artist', 'studio', 'creative'],
        '1561070791-2526d30994b5' => ['design', 'color swatches', 'branding', 'graphic design', 'agency'],
        '1455390582262-044cdead277a' => ['writing', 'author', 'blog', 'journal', 'copywriting'],

        // Places & travel
        '1506744038136-46273834b3fb' => ['landscape', 'lake', 'mountains', 'nature', 'forest'],
        '1469474968028-56623f02e42e' => ['landscape', 'nature', 'sunrise', 'hills', 'outdoors'],
        '1470071459604-3b5ec3a7fe05' => ['landscape', 'mountains', 'nature', 'mist', 'hiking'],
        '1501785888041-af3ef285b470' => ['lake', 'mountains', 'travel', 'boat', 'adventure'],
        '1507525428034-b723cf961d3e' => ['beach', 'ocean', 'sea', 'tropical', 'vacation', 'resort'],
        '1476514525535-07fb3b4ae5f1' => ['travel', 'lake', 'boat', 'mountains', 'adventure', 'tour'],
        '1488646953014-85cb44e25828' => ['travel', 'map', 'planning', 'trip', 'travel agency'],
        '1530789253388-582c481c54b0' => ['travel', 'hot air balloons', 'adventure', 'tourism', 'explore'],
        '1464822759023-fed622ff2c3b' => ['mountain', 'peak', 'adventure', 'hiking', 'climbing'],
        '1448375240586-882707db888b' => ['forest', 'trees', 'nature', 'woods', 'eco'],
        '1490750967868-88aa4486c946' => ['flowers', 'florist', 'blossom', 'garden', 'spring'],
        '1416879595882-3373a0480b5b' => ['gardening', 'garden', 'soil', 'plants', 'landscaping'],
        '1464226184884-fa280b87c399' => ['vegetables', 'farm', 'produce', 'organic', 'market', 'grocery'],
        '1449824913935-59a10b8d2000' => ['city', 'street', 'downtown', 'urban', 'skyscrapers'],
        '1477959858617-67f85cf4f1df' => ['city', 'skyline', 'urban', 'aerial', 'downtown'],
        '1487958449943-2429e8be8625' => ['architecture', 'building', 'modern', 'architect'],
        '1566073771259-6a8506099945' => ['hotel', 'resort', 'pool', 'vacation', 'villa'],
        '1582719478250-c89cae4dc85b' => ['hotel room', 'hotel', 'bedroom', 'hospitality', 'airbnb'],

        // Home & property
        '1600596542815-ffad4c1539a9' => ['house', 'real estate', 'modern home', 'pool', 'property'],
        '1600585154340-be6161a56a0c' => ['house', 'real estate', 'modern home', 'architecture', 'property'],
        '1564013799919-ab600027ffc6' => ['house', 'real estate', 'villa', 'pool', 'property'],
        '1502672260266-1c1ef2d93688' => ['interior', 'living room', 'apartment', 'interior design', 'home'],
        '1586023492125-27b2c045efd7' => ['interior', 'living room', 'furniture', 'interior design', 'home decor'],
        '1504307651254-35680f356dfd' => ['construction', 'builders', 'site', 'engineering', 'contractor'],
        '1541888946425-d81bb19240f5' => ['construction', 'building site', 'engineering', 'contractor'],
        '1581578731548-c64695cc6952' => ['cleaning', 'cleaner', 'housekeeping', 'janitorial'],
        '1607472586893-edb57bdc0e39' => ['plumbing', 'pipes', 'plumber', 'repair', 'utilities'],

        // Retail & products
        '1445205170230-053b83016050' => ['fashion', 'clothing', 'boutique', 'apparel', 'style'],
        '1490481651871-ab68de25d43d' => ['clothes', 'clothing rack', 'fashion', 'boutique', 'wardrobe'],
        '1441986300917-64674bd600d8' => ['store', 'retail', 'boutique', 'shop interior', 'clothing'],
        '1472851294608-062f824d29cc' => ['shop', 'open sign', 'small business', 'storefront', 'retail'],
        '1556742049-0cfed4f6a45d' => ['small business', 'ecommerce', 'shop owner', 'packaging', 'retail'],
        '1566576721346-d4a3b4eaeb55' => ['delivery', 'package', 'shipping', 'courier', 'logistics'],
        '1523275335684-37898b6baf30' => ['watch', 'smartwatch', 'product', 'accessory', 'wearable'],
        '1505740420928-5e560c06d30e' => ['headphones', 'audio', 'product', 'music', 'gadget'],
        '1542291026-7eec264c27ff' => ['sneaker', 'shoes', 'product', 'footwear', 'sportswear'],
        '1603006905003-be475563bc59' => ['candle', 'candles', 'home fragrance', 'cozy', 'decor'],
        '1602874801007-bd458bb1b8b6' => ['candle', 'candles', 'handmade', 'soy candle', 'decor'],
        '1557683316-973673baf926' => ['abstract', 'gradient', 'background', 'blue', 'purple'],
        '1579546929518-9e396f3cc809' => ['abstract', 'gradient', 'colorful', 'background', 'pastel'],

        // Learning & community
        '1503676260728-1c00da094a0b' => ['education', 'school', 'books', 'learning', 'teacher', 'apple'],
        '1524178232363-1fb2b075b655' => ['classroom', 'lecture', 'students', 'training', 'course', 'workshop'],
        '1427504494785-3a9ca7044f45' => ['library', 'books', 'student', 'study', 'university'],
        '1481627834876-b7833e8f5570' => ['books', 'library', 'reading', 'bookstore', 'literature'],
        '1507842217343-583bb7270b66' => ['library', 'books', 'reading room', 'bookstore'],
        '1503454537195-1dcabb73ffb9' => ['kids', 'child', 'children', 'playful', 'happy'],
        '1515488042361-ee00e0ddd4e4' => ['baby', 'toys', 'kids', 'daycare', 'nursery'],
        '1488521787991-ed7bbaae773c' => ['children', 'community', 'smiling', 'charity', 'nonprofit'],
        '1559027615-cd4628902d4a' => ['volunteer', 'charity', 'nonprofit', 'community', 'donation'],

        // Animals
        '1543466835-00a7907e9de1' => ['dog', 'puppy', 'pet', 'pets', 'grooming', 'veterinary'],
        '1514888286974-6c03e2ca1dba' => ['cat', 'pet', 'pets', 'kitten', 'veterinary'],

        // Sport
        '1531415074968-036ba1b575da' => ['cricket', 'cricket ball', 'sport', 'match', 'stumps'],
        '1540747913346-19e32dc3e97e' => ['cricket stadium', 'stadium', 'sport', 'match', 'cricket'],
        '1461896836934-ffe607ba8211' => ['athletics', 'sprint', 'running track', 'sport', 'race'],
        '1517649763962-0c623066013b' => ['cycling', 'bike race', 'cyclists', 'sport', 'bicycle'],
        '1574629810360-7efbbe195018' => ['football', 'soccer', 'sport', 'pitch', 'club'],
        '1552674605-db6ffd4facb5' => ['running', 'runners', 'jogging', 'marathon', 'sport'],

        // Cars
        '1492144534655-ae79c964c9d7' => ['car', 'sports car', 'automotive', 'dealership', 'luxury car'],
        '1503376780353-7e6692767b70' => ['car', 'porsche', 'automotive', 'driving', 'dealership'],
        '1486262715619-67b85e0b08d3' => ['engine', 'mechanic', 'auto repair', 'garage', 'automotive'],
    ];

    /** People photos, tagged by apparent presentation for "portrait of a woman/man" queries. */
    public const PORTRAITS = [
        '1507003211169-0a1dd7228f2d' => 'man',
        '1494790108377-be9c29b29330' => 'woman',
        '1500648767791-00dcc994a43e' => 'man',
        '1438761681033-6461ffad8d80' => 'woman',
        '1472099645785-5658abf4ff4e' => 'man',
        '1544005313-94ddf0286df2' => 'woman',
        '1534528741775-53994a69daeb' => 'woman',
        '1506794778202-cad84cf45f1d' => 'man',
        '1517841905240-472988babdf9' => 'woman',
        '1539571696357-5a69c17a67c6' => 'man',
        '1573496359142-b8d87734a5a2' => 'woman',
        '1560250097-0b93528c311a' => 'man',
    ];

    /** Query words that map onto library tags (synonyms, plurals, other phrasings). */
    public const SYNONYMS = [
        'restaurants' => 'restaurant', 'dining' => 'restaurant', 'eatery' => 'restaurant', 'meal' => 'food', 'dishes' => 'dish', 'cuisine' => 'food', 'chef' => 'restaurant',
        'pizzas' => 'pizza', 'espresso' => 'coffee', 'barista' => 'coffee', 'tea' => 'cafe', 'bakery' => 'bread', 'cake' => 'pastry', 'cakes' => 'pastry', 'dessert' => 'pastry', 'brunch' => 'breakfast',
        'fitness' => 'fitness', 'trainer' => 'gym', 'personal' => 'fitness', 'exercise' => 'workout', 'pilates' => 'workout', 'meditation' => 'meditation',
        'barber' => 'salon', 'hairdresser' => 'hair', 'nails' => 'beauty', 'skincare' => 'spa', 'massage' => 'massage',
        'clinic' => 'clinic', 'hospital' => 'healthcare', 'nurse' => 'healthcare', 'physician' => 'doctor', 'therapy' => 'healthcare', 'teeth' => 'dental', 'dentistry' => 'dental',
        'bride' => 'wedding', 'groom' => 'wedding', 'marriage' => 'wedding', 'engagement' => 'wedding', 'photoshoot' => 'photography', 'photographs' => 'photography', 'photos' => 'photography',
        'developer' => 'code', 'developers' => 'code', 'coding' => 'code', 'software' => 'software', 'app' => 'smartphone', 'apps' => 'smartphone', 'ai' => 'technology', 'startup' => 'startup', 'saas' => 'saas', 'dashboard' => 'dashboard',
        'lawyer' => 'law', 'attorney' => 'law', 'legal' => 'law', 'accountant' => 'finance', 'accounting' => 'finance', 'bank' => 'money', 'investing' => 'investment', 'insurance' => 'contract',
        'home' => 'house', 'homes' => 'house', 'property' => 'house', 'properties' => 'house', 'apartment' => 'interior', 'realtor' => 'real estate', 'furniture' => 'interior', 'decor' => 'interior',
        'builder' => 'construction', 'contractor' => 'construction', 'renovation' => 'construction', 'cleaner' => 'cleaning', 'plumber' => 'plumbing', 'repair' => 'repair',
        'clothing' => 'fashion', 'apparel' => 'fashion', 'boutique' => 'boutique', 'shoes' => 'sneaker', 'product' => 'product', 'products' => 'product', 'shop' => 'shop', 'store' => 'store', 'ecommerce' => 'ecommerce', 'shipping' => 'delivery',
        'candles' => 'candle', 'fragrance' => 'candle',
        'school' => 'education', 'students' => 'classroom', 'teacher' => 'education', 'course' => 'course', 'courses' => 'course', 'tutor' => 'education', 'university' => 'university',
        'concerts' => 'concert', 'band' => 'concert', 'dj' => 'dj', 'party' => 'party', 'events' => 'event', 'conference' => 'conference', 'seminar' => 'conference',
        'trip' => 'travel', 'tourism' => 'travel', 'vacation' => 'vacation', 'holiday' => 'vacation', 'hiking' => 'hiking', 'ocean' => 'beach', 'sea' => 'beach', 'resort' => 'resort',
        'puppy' => 'dog', 'dogs' => 'dog', 'cats' => 'cat', 'pet' => 'pet', 'vet' => 'veterinary',
        'soccer' => 'football', 'cricketer' => 'cricket', 'batsman' => 'cricket', 'stadium' => 'stadium', 'marathon' => 'running', 'bike' => 'cycling',
        'cars' => 'car', 'automotive' => 'automotive', 'mechanic' => 'mechanic',
        'charity' => 'charity', 'nonprofit' => 'nonprofit', 'volunteers' => 'volunteer', 'kids' => 'kids', 'children' => 'children',
    ];

    /** Fallback pools when nothing matches, by placement. */
    public const GENERIC = [
        'wide' => ['1506744038136-46273834b3fb', '1470071459604-3b5ec3a7fe05', '1497366216548-37526070297c', '1557683316-973673baf926', '1477959858617-67f85cf4f1df'],
        'card' => ['1522071820081-009f0129c71c', '1504384308090-c894fdcc538d', '1469474968028-56623f02e42e', '1579546929518-9e396f3cc809', '1556761175-b413da4baf72'],
    ];
}
