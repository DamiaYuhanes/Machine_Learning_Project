<?php
// ============================================================
// Mia Flight n Go — International Flight Data Engine
// ============================================================

$airlines = [
    // Malaysia
    'AK' => ['name'=>'AirAsia',             'color'=>'#e63946', 'type'=>'Low-Cost'],
    'MH' => ['name'=>'Malaysia Airlines',    'color'=>'#003087', 'type'=>'Full-Service'],
    'OD' => ['name'=>'Batik Air Malaysia',   'color'=>'#C8102E', 'type'=>'Full-Service'],
    'FY' => ['name'=>'Firefly',              'color'=>'#FF6600', 'type'=>'Regional'],
    'D7' => ['name'=>'AirAsia X',            'color'=>'#e63946', 'type'=>'Long-Haul LCC'],
    // Southeast Asia
    'SQ' => ['name'=>'Singapore Airlines',   'color'=>'#003B6F', 'type'=>'Full-Service'],
    'TG' => ['name'=>'Thai Airways',         'color'=>'#6600CC', 'type'=>'Full-Service'],
    'GA' => ['name'=>'Garuda Indonesia',     'color'=>'#007DBA', 'type'=>'Full-Service'],
    'PR' => ['name'=>'Philippine Airlines',  'color'=>'#0032A0', 'type'=>'Full-Service'],
    'VN' => ['name'=>'Vietnam Airlines',     'color'=>'#007B40', 'type'=>'Full-Service'],
    'VJ' => ['name'=>'VietJet Air',          'color'=>'#e63946', 'type'=>'Low-Cost'],
    'QZ' => ['name'=>'Indonesia AirAsia',    'color'=>'#e63946', 'type'=>'Low-Cost'],
    // Northeast Asia
    'CX' => ['name'=>'Cathay Pacific',       'color'=>'#006564', 'type'=>'Full-Service'],
    'CI' => ['name'=>'China Airlines',       'color'=>'#005BAC', 'type'=>'Full-Service'],
    'JL' => ['name'=>'Japan Airlines',       'color'=>'#e63946', 'type'=>'Full-Service'],
    'NH' => ['name'=>'ANA',                  'color'=>'#13448F', 'type'=>'Full-Service'],
    'KE' => ['name'=>'Korean Air',           'color'=>'#00256C', 'type'=>'Full-Service'],
    'OZ' => ['name'=>'Asiana Airlines',      'color'=>'#003DA5', 'type'=>'Full-Service'],
    'CA' => ['name'=>'Air China',            'color'=>'#e63946', 'type'=>'Full-Service'],
    'MU' => ['name'=>'China Eastern',        'color'=>'#006AC9', 'type'=>'Full-Service'],
    // South Asia
    'AI' => ['name'=>'Air India',            'color'=>'#e63946', 'type'=>'Full-Service'],
    'UL' => ['name'=>'SriLankan Airlines',   'color'=>'#0047AB', 'type'=>'Full-Service'],
    // Middle East
    'EK' => ['name'=>'Emirates',             'color'=>'#D71920', 'type'=>'Full-Service'],
    'QR' => ['name'=>'Qatar Airways',        'color'=>'#5C0632', 'type'=>'Full-Service'],
    'EY' => ['name'=>'Etihad Airways',       'color'=>'#BD8B13', 'type'=>'Full-Service'],
    // Europe
    'BA' => ['name'=>'British Airways',      'color'=>'#2B5CA7', 'type'=>'Full-Service'],
    'LH' => ['name'=>'Lufthansa',            'color'=>'#0B6CB4', 'type'=>'Full-Service'],
    'AF' => ['name'=>'Air France',           'color'=>'#002157', 'type'=>'Full-Service'],
    'KL' => ['name'=>'KLM',                  'color'=>'#00A1DE', 'type'=>'Full-Service'],
    'TK' => ['name'=>'Turkish Airlines',     'color'=>'#C8102E', 'type'=>'Full-Service'],
    'FR' => ['name'=>'Ryanair',              'color'=>'#073590', 'type'=>'Low-Cost'],
    'U2' => ['name'=>'easyJet',              'color'=>'#FF6600', 'type'=>'Low-Cost'],
    // Americas
    'AA' => ['name'=>'American Airlines',    'color'=>'#0078D2', 'type'=>'Full-Service'],
    'UA' => ['name'=>'United Airlines',      'color'=>'#005DAA', 'type'=>'Full-Service'],
    'DL' => ['name'=>'Delta Air Lines',      'color'=>'#003366', 'type'=>'Full-Service'],
    'AC' => ['name'=>'Air Canada',           'color'=>'#e63946', 'type'=>'Full-Service'],
    // Oceania
    'QF' => ['name'=>'Qantas',               'color'=>'#e63946', 'type'=>'Full-Service'],
    'NZ' => ['name'=>'Air New Zealand',      'color'=>'#1A1A1A', 'type'=>'Full-Service'],
];

// Airports with lat/lon for distance-based pricing
$airports = [
    // ── Malaysia Domestic ─────────────────────────────────────
    'KUL'=>['city'=>'Kuala Lumpur', 'country'=>'Malaysia','name'=>'KL International Airport',       'lat'=>2.7456,  'lon'=>101.7099,'region'=>'MY'],
    'PEN'=>['city'=>'Penang',       'country'=>'Malaysia','name'=>'Penang International Airport',    'lat'=>5.2977,  'lon'=>100.2769,'region'=>'MY'],
    'BKI'=>['city'=>'Kota Kinabalu','country'=>'Malaysia','name'=>'Kota Kinabalu International',     'lat'=>5.9372,  'lon'=>116.0510,'region'=>'MY'],
    'KCH'=>['city'=>'Kuching',      'country'=>'Malaysia','name'=>'Kuching International Airport',   'lat'=>1.4847,  'lon'=>110.3467,'region'=>'MY'],
    'JHB'=>['city'=>'Johor Bahru',  'country'=>'Malaysia','name'=>'Senai International Airport',     'lat'=>1.6413,  'lon'=>103.6697,'region'=>'MY'],
    'LGK'=>['city'=>'Langkawi',     'country'=>'Malaysia','name'=>'Langkawi International Airport',  'lat'=>6.3297,  'lon'=>99.7287, 'region'=>'MY'],
    'KBR'=>['city'=>'Kota Bharu',   'country'=>'Malaysia','name'=>'Sultan Ismail Petra Airport',     'lat'=>6.1668,  'lon'=>102.2932,'region'=>'MY'],
    'IPH'=>['city'=>'Ipoh',         'country'=>'Malaysia','name'=>'Sultan Azlan Shah Airport',       'lat'=>4.5680,  'lon'=>101.0921,'region'=>'MY'],
    'MYY'=>['city'=>'Miri',         'country'=>'Malaysia','name'=>'Miri Airport',                    'lat'=>4.3220,  'lon'=>113.9869,'region'=>'MY'],
    'TWU'=>['city'=>'Tawau',        'country'=>'Malaysia','name'=>'Tawau Airport',                   'lat'=>4.3162,  'lon'=>118.1228,'region'=>'MY'],
    'SBW'=>['city'=>'Sibu',         'country'=>'Malaysia','name'=>'Sibu Airport',                    'lat'=>2.2616,  'lon'=>111.9853,'region'=>'MY'],
    'SDK'=>['city'=>'Sandakan',     'country'=>'Malaysia','name'=>'Sandakan Airport',                'lat'=>5.9009,  'lon'=>118.0588,'region'=>'MY'],
    // ── Southeast Asia ─────────────────────────────────────────
    'SIN'=>['city'=>'Singapore',    'country'=>'Singapore','name'=>'Changi Airport',                 'lat'=>1.3644,  'lon'=>103.9915,'region'=>'SEA'],
    'BKK'=>['city'=>'Bangkok',      'country'=>'Thailand', 'name'=>'Suvarnabhumi Airport',           'lat'=>13.6900, 'lon'=>100.7501,'region'=>'SEA'],
    'DMK'=>['city'=>'Bangkok (Don Mueang)','country'=>'Thailand','name'=>'Don Mueang Airport',       'lat'=>13.9126, 'lon'=>100.6069,'region'=>'SEA'],
    'CGK'=>['city'=>'Jakarta',      'country'=>'Indonesia','name'=>'Soekarno-Hatta International',   'lat'=>-6.1256, 'lon'=>106.6559,'region'=>'SEA'],
    'DPS'=>['city'=>'Bali',         'country'=>'Indonesia','name'=>'Ngurah Rai International',       'lat'=>-8.7482, 'lon'=>115.1670,'region'=>'SEA'],
    'SUB'=>['city'=>'Surabaya',     'country'=>'Indonesia','name'=>'Juanda International Airport',   'lat'=>-7.3798, 'lon'=>112.7870,'region'=>'SEA'],
    'KNO'=>['city'=>'Medan',        'country'=>'Indonesia','name'=>'Kualanamu International Airport','lat'=>3.6422,  'lon'=>98.8853, 'region'=>'SEA'],
    'UPG'=>['city'=>'Makassar',     'country'=>'Indonesia','name'=>'Sultan Hasanuddin International','lat'=>-5.0617, 'lon'=>119.5540,'region'=>'SEA'],
    'JOG'=>['city'=>'Yogyakarta',   'country'=>'Indonesia','name'=>'Yogyakarta International Airport','lat'=>-7.9004,'lon'=>110.0572,'region'=>'SEA'],
    'BPN'=>['city'=>'Balikpapan',   'country'=>'Indonesia','name'=>'Sultan Aji Muhammad Sulaiman Intl','lat'=>-1.2683,'lon'=>116.8940,'region'=>'SEA'],
    'LOP'=>['city'=>'Lombok',       'country'=>'Indonesia','name'=>'Zainuddin Abdul Madjid International','lat'=>-8.7574,'lon'=>116.2766,'region'=>'SEA'],
    'SOC'=>['city'=>'Solo',         'country'=>'Indonesia','name'=>'Adi Soemarmo International Airport','lat'=>-7.5161,'lon'=>110.7570,'region'=>'SEA'],
    'PLM'=>['city'=>'Palembang',    'country'=>'Indonesia','name'=>'Sultan Mahmud Badaruddin II Intl','lat'=>-2.8982,'lon'=>104.7000,'region'=>'SEA'],
    'MNL'=>['city'=>'Manila',       'country'=>'Philippines','name'=>'Ninoy Aquino International',  'lat'=>14.5086, 'lon'=>121.0197,'region'=>'SEA'],
    'CEB'=>['city'=>'Cebu',         'country'=>'Philippines','name'=>'Mactan-Cebu International',   'lat'=>10.3075, 'lon'=>123.9795,'region'=>'SEA'],
    'SGN'=>['city'=>'Ho Chi Minh City','country'=>'Vietnam','name'=>'Tan Son Nhat Airport',         'lat'=>10.8188, 'lon'=>106.6520,'region'=>'SEA'],
    'HAN'=>['city'=>'Hanoi',        'country'=>'Vietnam', 'name'=>'Noi Bai International Airport',  'lat'=>21.2212, 'lon'=>105.8072,'region'=>'SEA'],
    'RGN'=>['city'=>'Yangon',       'country'=>'Myanmar', 'name'=>'Yangon International Airport',   'lat'=>16.9073, 'lon'=>96.1332, 'region'=>'SEA'],
    'PNH'=>['city'=>'Phnom Penh',   'country'=>'Cambodia','name'=>'Phnom Penh International',       'lat'=>11.5466, 'lon'=>104.8440,'region'=>'SEA'],
    'VTE'=>['city'=>'Vientiane',    'country'=>'Laos',    'name'=>'Wattay International Airport',   'lat'=>17.9883, 'lon'=>102.5633,'region'=>'SEA'],
    // ── Northeast Asia ──────────────────────────────────────────
    'HKG'=>['city'=>'Hong Kong',    'country'=>'Hong Kong','name'=>'Hong Kong International',       'lat'=>22.3080, 'lon'=>113.9185,'region'=>'NEA'],
    'TPE'=>['city'=>'Taipei',       'country'=>'Taiwan',  'name'=>'Taiwan Taoyuan International',   'lat'=>25.0797, 'lon'=>121.2342,'region'=>'NEA'],
    'NRT'=>['city'=>'Tokyo (Narita)','country'=>'Japan',  'name'=>'Narita International Airport',   'lat'=>35.7720, 'lon'=>140.3929,'region'=>'NEA'],
    'HND'=>['city'=>'Tokyo (Haneda)','country'=>'Japan',  'name'=>'Haneda Airport',                 'lat'=>35.5494, 'lon'=>139.7798,'region'=>'NEA'],
    'KIX'=>['city'=>'Osaka',        'country'=>'Japan',   'name'=>'Kansai International Airport',   'lat'=>34.4347, 'lon'=>135.2440,'region'=>'NEA'],
    'ICN'=>['city'=>'Seoul',        'country'=>'South Korea','name'=>'Incheon International',       'lat'=>37.4602, 'lon'=>126.4407,'region'=>'NEA'],
    'PEK'=>['city'=>'Beijing',      'country'=>'China',   'name'=>'Capital International Airport',  'lat'=>40.0799, 'lon'=>116.6031,'region'=>'NEA'],
    'PVG'=>['city'=>'Shanghai',     'country'=>'China',   'name'=>'Pudong International Airport',   'lat'=>31.1443, 'lon'=>121.8083,'region'=>'NEA'],
    'CAN'=>['city'=>'Guangzhou',    'country'=>'China',   'name'=>'Baiyun International Airport',   'lat'=>23.3925, 'lon'=>113.3029,'region'=>'NEA'],
    'CTU'=>['city'=>'Chengdu',      'country'=>'China',   'name'=>'Tianfu International Airport',   'lat'=>30.3124, 'lon'=>104.4441,'region'=>'NEA'],
    // ── South Asia ──────────────────────────────────────────────
    'DEL'=>['city'=>'New Delhi',    'country'=>'India',   'name'=>'Indira Gandhi International',    'lat'=>28.5665, 'lon'=>77.1031, 'region'=>'SA'],
    'BOM'=>['city'=>'Mumbai',       'country'=>'India',   'name'=>'Chhatrapati Shivaji International','lat'=>19.0887,'lon'=>72.8679,'region'=>'SA'],
    'MAA'=>['city'=>'Chennai',      'country'=>'India',   'name'=>'Chennai International Airport',  'lat'=>12.9900, 'lon'=>80.1693, 'region'=>'SA'],
    'CCU'=>['city'=>'Kolkata',      'country'=>'India',   'name'=>'Netaji Subhas Chandra Bose Intl','lat'=>22.6547, 'lon'=>88.4467, 'region'=>'SA'],
    'CMB'=>['city'=>'Colombo',      'country'=>'Sri Lanka','name'=>'Bandaranaike International',    'lat'=>7.1808,  'lon'=>79.8841, 'region'=>'SA'],
    'DAC'=>['city'=>'Dhaka',        'country'=>'Bangladesh','name'=>'Hazrat Shahjalal International','lat'=>23.8433, 'lon'=>90.3978, 'region'=>'SA'],
    // ── Middle East ─────────────────────────────────────────────
    'DXB'=>['city'=>'Dubai',        'country'=>'UAE',     'name'=>'Dubai International Airport',    'lat'=>25.2532, 'lon'=>55.3657, 'region'=>'ME'],
    'DOH'=>['city'=>'Doha',         'country'=>'Qatar',   'name'=>'Hamad International Airport',    'lat'=>25.2609, 'lon'=>51.6138, 'region'=>'ME'],
    'AUH'=>['city'=>'Abu Dhabi',    'country'=>'UAE',     'name'=>'Zayed International Airport',    'lat'=>24.4330, 'lon'=>54.6511, 'region'=>'ME'],
    'RUH'=>['city'=>'Riyadh',       'country'=>'Saudi Arabia','name'=>'King Khalid International', 'lat'=>24.9578, 'lon'=>46.6988, 'region'=>'ME'],
    'KWI'=>['city'=>'Kuwait City',  'country'=>'Kuwait',  'name'=>'Kuwait International Airport',   'lat'=>29.2267, 'lon'=>47.9689, 'region'=>'ME'],
    'BAH'=>['city'=>'Bahrain',      'country'=>'Bahrain', 'name'=>'Bahrain International Airport',  'lat'=>26.2708, 'lon'=>50.6336, 'region'=>'ME'],
    'AMM'=>['city'=>'Amman',        'country'=>'Jordan',  'name'=>'Queen Alia International',       'lat'=>31.7226, 'lon'=>35.9932, 'region'=>'ME'],
    // ── Europe ──────────────────────────────────────────────────
    'LHR'=>['city'=>'London',       'country'=>'UK',      'name'=>'Heathrow Airport',               'lat'=>51.4775, 'lon'=>-0.4614, 'region'=>'EU'],
    'LGW'=>['city'=>'London (Gatwick)','country'=>'UK',   'name'=>'Gatwick Airport',                'lat'=>51.1537, 'lon'=>-0.1821, 'region'=>'EU'],
    'CDG'=>['city'=>'Paris',        'country'=>'France',  'name'=>'Charles de Gaulle Airport',      'lat'=>49.0097, 'lon'=>2.5479,  'region'=>'EU'],
    'FRA'=>['city'=>'Frankfurt',    'country'=>'Germany', 'name'=>'Frankfurt Airport',               'lat'=>50.0379, 'lon'=>8.5622,  'region'=>'EU'],
    'AMS'=>['city'=>'Amsterdam',    'country'=>'Netherlands','name'=>'Amsterdam Schiphol Airport',  'lat'=>52.3086, 'lon'=>4.7639,  'region'=>'EU'],
    'MAD'=>['city'=>'Madrid',       'country'=>'Spain',   'name'=>'Adolfo Suárez Barajas Airport',  'lat'=>40.4983, 'lon'=>-3.5676, 'region'=>'EU'],
    'BCN'=>['city'=>'Barcelona',    'country'=>'Spain',   'name'=>'Barcelona El Prat Airport',      'lat'=>41.2974, 'lon'=>2.0833,  'region'=>'EU'],
    'FCO'=>['city'=>'Rome',         'country'=>'Italy',   'name'=>'Leonardo da Vinci Airport',      'lat'=>41.8003, 'lon'=>12.2389, 'region'=>'EU'],
    'MXP'=>['city'=>'Milan',        'country'=>'Italy',   'name'=>'Malpensa Airport',               'lat'=>45.6306, 'lon'=>8.7281,  'region'=>'EU'],
    'ZRH'=>['city'=>'Zurich',       'country'=>'Switzerland','name'=>'Zurich Airport',              'lat'=>47.4647, 'lon'=>8.5492,  'region'=>'EU'],
    'VIE'=>['city'=>'Vienna',       'country'=>'Austria', 'name'=>'Vienna International Airport',   'lat'=>48.1102, 'lon'=>16.5697, 'region'=>'EU'],
    'MUC'=>['city'=>'Munich',       'country'=>'Germany', 'name'=>'Munich Airport',                 'lat'=>48.3538, 'lon'=>11.7861, 'region'=>'EU'],
    'IST'=>['city'=>'Istanbul',     'country'=>'Turkey',  'name'=>'Istanbul Airport',               'lat'=>41.2753, 'lon'=>28.7519, 'region'=>'EU'],
    'ATH'=>['city'=>'Athens',       'country'=>'Greece',  'name'=>'Athens International Airport',   'lat'=>37.9364, 'lon'=>23.9445, 'region'=>'EU'],
    'CPH'=>['city'=>'Copenhagen',   'country'=>'Denmark', 'name'=>'Copenhagen Airport',             'lat'=>55.6179, 'lon'=>12.6561, 'region'=>'EU'],
    'ARN'=>['city'=>'Stockholm',    'country'=>'Sweden',  'name'=>'Arlanda Airport',                'lat'=>59.6498, 'lon'=>17.9237, 'region'=>'EU'],
    'OSL'=>['city'=>'Oslo',         'country'=>'Norway',  'name'=>'Gardermoen Airport',             'lat'=>60.1976, 'lon'=>11.1004, 'region'=>'EU'],
    'HEL'=>['city'=>'Helsinki',     'country'=>'Finland', 'name'=>'Helsinki-Vantaa Airport',        'lat'=>60.3172, 'lon'=>24.9633, 'region'=>'EU'],
    'WAW'=>['city'=>'Warsaw',       'country'=>'Poland',  'name'=>'Warsaw Chopin Airport',          'lat'=>52.1657, 'lon'=>20.9671, 'region'=>'EU'],
    'PRG'=>['city'=>'Prague',       'country'=>'Czech Republic','name'=>'Václav Havel Airport',     'lat'=>50.1008, 'lon'=>14.2600, 'region'=>'EU'],
    // ── Americas ────────────────────────────────────────────────
    'JFK'=>['city'=>'New York',     'country'=>'USA',     'name'=>'John F. Kennedy International',  'lat'=>40.6413, 'lon'=>-73.7781,'region'=>'AM'],
    'LAX'=>['city'=>'Los Angeles',  'country'=>'USA',     'name'=>'Los Angeles International',      'lat'=>33.9425, 'lon'=>-118.4081,'region'=>'AM'],
    'ORD'=>['city'=>'Chicago',      'country'=>'USA',     'name'=>"O'Hare International Airport",   'lat'=>41.9742, 'lon'=>-87.9073,'region'=>'AM'],
    'MIA'=>['city'=>'Miami',        'country'=>'USA',     'name'=>'Miami International Airport',    'lat'=>25.7959, 'lon'=>-80.2870,'region'=>'AM'],
    'SFO'=>['city'=>'San Francisco','country'=>'USA',     'name'=>'San Francisco International',    'lat'=>37.6213, 'lon'=>-122.3790,'region'=>'AM'],
    'YYZ'=>['city'=>'Toronto',      'country'=>'Canada',  'name'=>'Toronto Pearson International',  'lat'=>43.6777, 'lon'=>-79.6248,'region'=>'AM'],
    'YVR'=>['city'=>'Vancouver',    'country'=>'Canada',  'name'=>'Vancouver International Airport','lat'=>49.1967, 'lon'=>-123.1815,'region'=>'AM'],
    'GRU'=>['city'=>'São Paulo',    'country'=>'Brazil',  'name'=>'Guarulhos International Airport','lat'=>-23.4356,'lon'=>-46.4731,'region'=>'AM'],
    'BOG'=>['city'=>'Bogotá',       'country'=>'Colombia','name'=>'El Dorado International Airport','lat'=>4.7016,  'lon'=>-74.1469,'region'=>'AM'],
    'MEX'=>['city'=>'Mexico City',  'country'=>'Mexico',  'name'=>'Benito Juárez International',    'lat'=>19.4363, 'lon'=>-99.0721,'region'=>'AM'],
    // ── Oceania ─────────────────────────────────────────────────
    'SYD'=>['city'=>'Sydney',       'country'=>'Australia','name'=>'Sydney Kingsford Smith Airport','lat'=>-33.9399,'lon'=>151.1753,'region'=>'OC'],
    'MEL'=>['city'=>'Melbourne',    'country'=>'Australia','name'=>'Melbourne Airport',             'lat'=>-37.6690,'lon'=>144.8410,'region'=>'OC'],
    'BNE'=>['city'=>'Brisbane',     'country'=>'Australia','name'=>'Brisbane Airport',              'lat'=>-27.3842,'lon'=>153.1175,'region'=>'OC'],
    'PER'=>['city'=>'Perth',        'country'=>'Australia','name'=>'Perth Airport',                 'lat'=>-31.9403,'lon'=>115.9669,'region'=>'OC'],
    'AKL'=>['city'=>'Auckland',     'country'=>'New Zealand','name'=>'Auckland Airport',            'lat'=>-37.0082,'lon'=>174.7850,'region'=>'OC'],
    // ── Africa ──────────────────────────────────────────────────
    'JNB'=>['city'=>'Johannesburg', 'country'=>'South Africa','name'=>'O.R. Tambo International',  'lat'=>-26.1392,'lon'=>28.2460, 'region'=>'AF'],
    'CPT'=>['city'=>'Cape Town',    'country'=>'South Africa','name'=>'Cape Town International',    'lat'=>-33.9715,'lon'=>18.6021, 'region'=>'AF'],
    'CAI'=>['city'=>'Cairo',        'country'=>'Egypt',   'name'=>'Cairo International Airport',    'lat'=>30.1219, 'lon'=>31.4056, 'region'=>'AF'],
    'CMN'=>['city'=>'Casablanca',   'country'=>'Morocco', 'name'=>'Mohammed V International',       'lat'=>33.3675, 'lon'=>-7.5899, 'region'=>'AF'],
    'NBO'=>['city'=>'Nairobi',      'country'=>'Kenya',   'name'=>'Jomo Kenyatta International',    'lat'=>-1.3192, 'lon'=>36.9275, 'region'=>'AF'],
    'ADD'=>['city'=>'Addis Ababa',  'country'=>'Ethiopia','name'=>'Bole International Airport',     'lat'=>8.9779,  'lon'=>38.7993, 'region'=>'AF'],
];

// Airlines that serve each region pair (from MY perspective)
function get_airlines_for_route(string $from_region, string $to_region): array {
    $key = "$from_region-$to_region";
    $map = [
        'MY-MY'  => ['AK','MH','OD','FY'],
        'MY-SEA' => ['AK','MH','SQ','TG','VN','VJ','D7'],
        'SEA-MY' => ['AK','MH','SQ','TG','VN','VJ','D7'],
        'SEA-SEA'=> ['AK','SQ','TG','VJ','QZ'],
        'MY-NEA' => ['MH','CX','JL','NH','KE','D7','CA'],
        'NEA-MY' => ['MH','CX','JL','NH','KE','D7','CA'],
        'MY-SA'  => ['MH','EK','QR','AI','UL'],
        'SA-MY'  => ['MH','EK','QR','AI','UL'],
        'MY-ME'  => ['MH','EK','QR','EY'],
        'ME-MY'  => ['MH','EK','QR','EY'],
        'MY-EU'  => ['MH','EK','QR','EY','BA','LH','AF','TK'],
        'EU-MY'  => ['MH','EK','QR','EY','BA','LH','AF','TK'],
        'MY-AM'  => ['MH','EK','QR','AA','UA'],
        'AM-MY'  => ['MH','EK','QR','AA','UA'],
        'MY-OC'  => ['MH','QF','D7'],
        'OC-MY'  => ['MH','QF','D7'],
        'MY-AF'  => ['MH','EK','QR'],
        'AF-MY'  => ['MH','EK','QR'],
        'NEA-NEA'=> ['CX','JL','NH','KE','CA','MU'],
        'EU-EU'  => ['BA','LH','AF','KL','FR','U2'],
        'ME-EU'  => ['EK','QR','EY','TK','BA','LH'],
        'EU-ME'  => ['EK','QR','EY','TK','BA','LH'],
    ];
    return $map[$key] ?? ['EK','QR','MH'];
}

// Haversine distance in km
function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

// Base economy price from distance (MYR)
function distance_to_price(float $km): int {
    return match(true) {
        $km < 300   => 70,
        $km < 700   => 120,
        $km < 1500  => 250,
        $km < 3000  => 480,
        $km < 6000  => 1200,
        $km < 10000 => 2800,
        default     => 4500,
    };
}

// Flight duration in minutes from distance
function distance_to_duration(float $km): int {
    $cruise = 850; // km/h
    $overhead = match(true) {
        $km < 500  => 40,
        $km < 2000 => 60,
        default    => 90,
    };
    return (int)(($km / $cruise) * 60) + $overhead;
}

function generate_flights(string $from, string $to, string $date, int $passengers = 1): array {
    global $airports, $airlines;

    if (!isset($airports[$from], $airports[$to]) || $from === $to) return [];

    $ap_from = $airports[$from];
    $ap_to   = $airports[$to];

    $km        = haversine($ap_from['lat'], $ap_from['lon'], $ap_to['lat'], $ap_to['lon']);
    $base_p    = distance_to_price($km);
    $dur_mins  = distance_to_duration($km);
    $date_ts   = strtotime($date);
    $day_of_week = (int)date('N', $date_ts);
    $days_ahead  = max(0, (int)ceil(($date_ts - mktime(0,0,0)) / 86400));

    $demand = 1.0;
    if ($day_of_week >= 5) $demand += 0.18;
    if ($days_ahead <= 3)  $demand += 0.40;
    if ($days_ahead <= 1)  $demand += 0.30;
    if ($days_ahead >= 60) $demand -= 0.12;
    if ($days_ahead >= 90) $demand -= 0.08;

    $avail_airlines = get_airlines_for_route($ap_from['region'], $ap_to['region']);

    // Aircraft type by distance
    $aircraft = match(true) {
        $km < 1500  => 'Airbus A320',
        $km < 4000  => 'Boeing 737 MAX',
        $km < 7000  => 'Airbus A330-300',
        $km < 10000 => 'Boeing 777-300ER',
        default     => 'Airbus A380 / Boeing 787',
    };

    $dep_times = ['06:00','07:15','08:30','09:45','11:00','12:30','14:00','15:30','17:00','18:30','20:00','21:30','23:00'];
    $flights   = [];
    $used_times= [];

    foreach ($avail_airlines as $code) {
        if (!isset($airlines[$code])) continue;
        $airline = $airlines[$code];

        $airline_mul = match($code) {
            'EK','QR','SQ','CX','JL' => 1.55,
            'MH','BA','LH','AF','KL','NH','KE','QF' => 1.35,
            'TG','GA','PR','AI','AC','AA','UA','DL','NZ' => 1.20,
            'OD','VN','UL' => 1.10,
            'AK','D7','VJ','QZ','FR','U2' => 0.75,
            default => 1.0,
        };

        // More daily flights for popular airlines
        $daily = in_array($code, ['AK','SQ','EK','QR']) ? 3 : 2;

        for ($i = 0; $i < $daily; $i++) {
            do { $dep = $dep_times[array_rand($dep_times)]; }
            while (in_array("$code-$dep", $used_times));
            $used_times[] = "$code-$dep";

            $hour = (int)explode(':',$dep)[0];
            $time_mul = match(true) {
                $hour >= 6  && $hour <= 8  => 1.18,
                $hour >= 17 && $hour <= 19 => 1.22,
                $hour >= 22 || $hour <= 4  => 0.88,
                default => 1.0,
            };

            $eco   = (int)round($base_p * $demand * $airline_mul * $time_mul * $passengers);
            $biz   = (int)round($eco * (($km > 5000) ? 4.5 : 3.2));
            $first = ($km > 5000) ? (int)round($eco * 8.5) : null;

            $arr_ts   = strtotime("$date $dep") + ($dur_mins * 60);
            $arr_time = date('H:i', $arr_ts);
            $arr_date = date('Y-m-d', $arr_ts);

            // 30-day history
            $history = [];
            for ($d = 30; $d >= 0; $d -= 3) {
                $history[] = [
                    'date'  => date('Y-m-d', strtotime("-{$d} days")),
                    'price' => (int)round($eco * (0.78 + (random_int(0, 1000000) / 1000000) * 0.52)),
                ];
            }

            $flights[] = [
                'id'             => uniqid('FL_'),
                'flight_no'      => $code . rand(100, 999),
                'airline_code'   => $code,
                'airline'        => $airline['name'],
                'airline_color'  => $airline['color'],
                'airline_type'   => $airline['type'],
                'from'           => $from,
                'to'             => $to,
                'date'           => $date,
                'arr_date'       => $arr_date,
                'departure'      => $dep,
                'arrival'        => $arr_time,
                'duration_mins'  => $dur_mins,
                'duration_fmt'   => floor($dur_mins/60).'h '.($dur_mins%60).'m',
                'distance_km'    => (int)$km,
                'stops'          => ($km > 6000 && !in_array($code, ['MH','EK','QR','SQ','CX','BA','QF'])) ? 1 : 0,
                'aircraft'       => $aircraft,
                'economy_price'  => $eco,
                'business_price' => $biz,
                'first_price'    => $first,
                'seats_left'     => rand(2, 52),
                'baggage'        => in_array($code, ['AK','D7','VJ','QZ','FR','U2']) ? '20 kg (add-on)' : '23–30 kg included',
                'meal'           => in_array($code, ['AK','D7','VJ','QZ','FR','U2']) ? 'Purchase on-board' : 'Included',
                'refundable'     => !in_array($code, ['AK','D7','VJ','QZ','FR','U2']),
                'price_history'  => $history,
                'low_price'      => min(array_column($history, 'price')),
                'high_price'     => max(array_column($history, 'price')),
                'co2_kg'         => (int)round($km * 0.089),
            ];
        }
    }

    usort($flights, fn($a,$b) => $a['economy_price'] <=> $b['economy_price']);
    return $flights;
}

function format_price(int $price): string {
    return 'RM ' . number_format($price);
}

function get_airport_list(): array {
    global $airports;
    return $airports;
}

function get_airlines_list(): array {
    global $airlines;
    return $airlines;
}

// Grouped airports for select menus
function get_airports_grouped(): array {
    global $airports;
    $groups = [];
    $labels = ['MY'=>'🇲🇾 Malaysia','SEA'=>'🌏 Southeast Asia','NEA'=>'🌏 Northeast Asia',
               'SA'=>'🇮🇳 South Asia','ME'=>'🌍 Middle East','EU'=>'🇪🇺 Europe',
               'AM'=>'🌎 Americas','OC'=>'🌏 Oceania / Pacific','AF'=>'🌍 Africa'];
    foreach ($airports as $code => $ap) {
        $r = $ap['region'];
        if (!isset($groups[$r])) $groups[$r] = ['label'=>$labels[$r]??$r,'airports'=>[]];
        $groups[$r]['airports'][$code] = $ap;
    }
    return $groups;
}
