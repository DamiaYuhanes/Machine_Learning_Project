<?php
// ============================================================
// Malaysia Flight Tracker — Mock Flight Data Engine
// ============================================================

define('BASE_CURRENCY', 'MYR');

$airlines = [
    'AK' => ['name' => 'AirAsia', 'logo' => 'AK', 'color' => '#FF0000', 'type' => 'Low-Cost'],
    'MH' => ['name' => 'Malaysia Airlines', 'logo' => 'MH', 'color' => '#003087', 'type' => 'Full-Service'],
    'OD' => ['name' => 'Batik Air Malaysia', 'logo' => 'OD', 'color' => '#C8102E', 'type' => 'Full-Service'],
    'FY' => ['name' => 'Firefly', 'logo' => 'FY', 'color' => '#FF6600', 'type' => 'Regional'],
    'D7' => ['name' => 'AirAsia X', 'logo' => 'D7', 'color' => '#FF0000', 'type' => 'Long-Haul Low-Cost'],
    'MX' => ['name' => 'Malindo Air', 'logo' => 'MX', 'color' => '#004B87', 'type' => 'Full-Service'],
];

$airports = [
    'KUL' => ['city' => 'Kuala Lumpur', 'name' => 'KL International Airport', 'code' => 'KUL', 'terminal' => 'KLIA Main / KLIA2'],
    'KBR' => ['city' => 'Kota Bharu', 'name' => 'Sultan Ismail Petra Airport', 'code' => 'KBR', 'terminal' => 'Terminal A'],
    'PEN' => ['city' => 'Penang', 'name' => 'Penang International Airport', 'code' => 'PEN', 'terminal' => 'Terminal 1'],
    'BKI' => ['city' => 'Kota Kinabalu', 'name' => 'Kota Kinabalu International', 'code' => 'BKI', 'terminal' => 'Terminal 1 / Terminal 2'],
    'KCH' => ['city' => 'Kuching', 'name' => 'Kuching International Airport', 'code' => 'KCH', 'terminal' => 'Terminal A'],
    'JHB' => ['city' => 'Johor Bahru', 'name' => 'Senai International Airport', 'code' => 'JHB', 'terminal' => 'Terminal'],
    'LGK' => ['city' => 'Langkawi', 'name' => 'Langkawi International Airport', 'code' => 'LGK', 'terminal' => 'Terminal 1'],
    'MKZ' => ['city' => 'Melaka', 'name' => 'Melaka International Airport', 'code' => 'MKZ', 'terminal' => 'Terminal A'],
    'IPH' => ['city' => 'Ipoh', 'name' => 'Sultan Azlan Shah Airport', 'code' => 'IPH', 'terminal' => 'Terminal A'],
    'AOR' => ['city' => 'Alor Setar', 'name' => 'Sultan Abdul Halim Airport', 'code' => 'AOR', 'terminal' => 'Terminal A'],
    'TWU' => ['city' => 'Tawau', 'name' => 'Tawau Airport', 'code' => 'TWU', 'terminal' => 'Terminal A'],
    'SBW' => ['city' => 'Sibu', 'name' => 'Sibu Airport', 'code' => 'SBW', 'terminal' => 'Terminal A'],
    'MYY' => ['city' => 'Miri', 'name' => 'Miri Airport', 'code' => 'MYY', 'terminal' => 'Terminal A'],
    'SDK' => ['city' => 'Sandakan', 'name' => 'Sandakan Airport', 'code' => 'SDK', 'terminal' => 'Terminal A'],
    'LBU' => ['city' => 'Labuan', 'name' => 'Labuan Airport', 'code' => 'LBU', 'terminal' => 'Terminal A'],
];

$route_base_prices = [
    'KUL-PEN' => 89,  'PEN-KUL' => 92,
    'KUL-BKI' => 189, 'BKI-KUL' => 195,
    'KUL-KCH' => 169, 'KCH-KUL' => 175,
    'KUL-JHB' => 69,  'JHB-KUL' => 72,
    'KUL-LGK' => 109, 'LGK-KUL' => 115,
    'KUL-KBR' => 129, 'KBR-KUL' => 132,
    'KUL-IPH' => 79,  'IPH-KUL' => 82,
    'KUL-MYY' => 199, 'MYY-KUL' => 205,
    'PEN-BKI' => 229, 'BKI-PEN' => 235,
    'PEN-KCH' => 209, 'KCH-PEN' => 215,
    'JHB-BKI' => 219, 'BKI-JHB' => 225,
    'KUL-SDK' => 249, 'SDK-KUL' => 255,
    'KUL-TWU' => 239, 'TWU-KUL' => 245,
];

$route_durations = [
    'KUL-PEN' => 55,  'PEN-KUL' => 55,
    'KUL-BKI' => 155, 'BKI-KUL' => 155,
    'KUL-KCH' => 105, 'KCH-KUL' => 105,
    'KUL-JHB' => 40,  'JHB-KUL' => 40,
    'KUL-LGK' => 60,  'LGK-KUL' => 60,
    'KUL-KBR' => 70,  'KBR-KUL' => 70,
    'KUL-IPH' => 45,  'IPH-KUL' => 45,
    'KUL-MYY' => 155, 'MYY-KUL' => 155,
    'PEN-BKI' => 185, 'BKI-PEN' => 185,
    'PEN-KCH' => 165, 'KCH-PEN' => 165,
    'JHB-BKI' => 195, 'BKI-JHB' => 195,
    'KUL-SDK' => 175, 'SDK-KUL' => 175,
    'KUL-TWU' => 180, 'TWU-KUL' => 180,
];

function generate_flights(string $from, string $to, string $date, int $passengers = 1): array {
    global $airlines, $airports, $route_base_prices, $route_durations;

    $route_key = "$from-$to";
    if (!isset($route_base_prices[$route_key])) {
        return [];
    }

    $base_price  = $route_base_prices[$route_key];
    $duration    = $route_durations[$route_key];
    $date_ts     = strtotime($date);
    $day_of_week = (int) date('N', $date_ts);
    $days_ahead  = (int) ceil(($date_ts - time()) / 86400);

    // Demand multiplier
    $demand = 1.0;
    if ($day_of_week >= 5) $demand += 0.15;        // weekend premium
    if ($days_ahead <= 3)  $demand += 0.35;         // last-minute premium
    if ($days_ahead <= 1)  $demand += 0.25;
    if ($days_ahead >= 60) $demand -= 0.10;         // book-early discount

    $available_airlines = match(true) {
        in_array($from, ['KUL','PEN','JHB','BKI','KCH']) && in_array($to, ['KUL','PEN','JHB','BKI','KCH'])
            => ['AK','MH','OD','FY'],
        default => ['AK','MH'],
    };

    $departure_times = ['06:00','07:30','08:45','10:00','11:15','12:30','14:00','15:30','17:00','18:30','20:00','21:30'];
    $flights = [];
    $used_times = [];

    foreach ($available_airlines as $code) {
        $airline    = $airlines[$code] ?? null;
        if (!$airline) continue;

        $daily_flights = ($code === 'AK') ? 4 : 2;

        for ($i = 0; $i < $daily_flights; $i++) {
            do { $dep_time = $departure_times[array_rand($departure_times)]; } while (in_array("$code-$dep_time", $used_times));
            $used_times[] = "$code-$dep_time";

            $arr_mins   = strtotime("$date $dep_time") + ($duration * 60);
            $arr_time   = date('H:i', $arr_mins);
            $arr_date   = date('Y-m-d', $arr_mins);

            // Airline-specific multiplier
            $airline_mul = match($code) {
                'MH' => 1.35, 'OD' => 1.20, 'FY' => 1.10,
                'AK' => 0.85, 'D7' => 0.90, default => 1.0,
            };

            // Time-of-day multiplier
            $hour = (int) explode(':', $dep_time)[0];
            $time_mul = match(true) {
                $hour >= 6  && $hour <= 8  => 1.15,
                $hour >= 17 && $hour <= 19 => 1.20,
                $hour >= 22 || $hour <= 4  => 0.90,
                default                    => 1.0,
            };

            $economy_price   = round($base_price * $demand * $airline_mul * $time_mul * $passengers);
            $business_price  = round($economy_price * 2.8);
            $seats_left      = rand(3, 47);
            $flight_no       = $code . rand(100, 999);
            $aircraft        = ($code === 'MH') ? 'Boeing 737-800' : (($code === 'OD') ? 'Boeing 737-900' : 'Airbus A320');

            $history = [];
            for ($d = 30; $d >= 0; $d -= 3) {
                $history[] = [
                    'date'  => date('Y-m-d', strtotime("-$d days")),
                    'price' => round($economy_price * (0.80 + lcg_value() * 0.50)),
                ];
            }

            $flights[] = [
                'id'             => uniqid("FL_"),
                'flight_no'      => $flight_no,
                'airline_code'   => $code,
                'airline'        => $airline['name'],
                'airline_color'  => $airline['color'],
                'airline_type'   => $airline['type'],
                'from'           => $from,
                'to'             => $to,
                'date'           => $date,
                'arr_date'       => $arr_date,
                'departure'      => $dep_time,
                'arrival'        => $arr_time,
                'duration_mins'  => $duration,
                'duration_fmt'   => floor($duration / 60) . 'h ' . ($duration % 60) . 'm',
                'stops'          => 0,
                'aircraft'       => $aircraft,
                'economy_price'  => $economy_price,
                'business_price' => $business_price,
                'seats_left'     => $seats_left,
                'baggage'        => ($code === 'AK') ? '20 kg (add-on)' : '20 kg included',
                'meal'           => in_array($code, ['MH','OD']) ? 'Included' : 'Purchase on-board',
                'refundable'     => in_array($code, ['MH','OD']),
                'price_history'  => $history,
                'low_price'      => min(array_column($history, 'price')),
                'high_price'     => max(array_column($history, 'price')),
            ];
        }
    }

    usort($flights, fn($a, $b) => $a['economy_price'] <=> $b['economy_price']);
    return $flights;
}

function format_price(int $price): string {
    return 'RM ' . number_format($price);
}

function get_airport_list(): array {
    global $airports;
    return $airports;
}

function duration_label(int $mins): string {
    return floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
}
