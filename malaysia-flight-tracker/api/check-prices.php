<?php
// Mia Flight n Go — Price Check API
// POST JSON array of watchlist items, returns current prices + match status

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/flights_data.php';

$body = file_get_contents('php://input');
$items = json_decode($body, true);

if (!is_array($items) || empty($items)) {
    echo json_encode(['ok'=>false,'error'=>'No items provided','results'=>[]]);
    exit;
}

$results = [];

foreach ($items as $item) {
    $from   = strtoupper(preg_replace('/[^A-Z]/', '', $item['from'] ?? ''));
    $to     = strtoupper(preg_replace('/[^A-Z]/', '', $item['to']   ?? ''));
    $date   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date'] ?? '') ? $item['date'] : '';
    $target = is_numeric($item['targetPrice'] ?? null) ? (int)$item['targetPrice'] : 0;
    $id     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $item['id'] ?? '');

    if (!$from || !$to || !$date) {
        $results[] = ['id'=>$id,'ok'=>false,'error'=>'Invalid params'];
        continue;
    }

    $flights = generate_flights($from, $to, $date, 1);

    if (empty($flights)) {
        $results[] = ['id'=>$id,'ok'=>false,'from'=>$from,'to'=>$to,'date'=>$date,'currentPrice'=>null,'targetPrice'=>$target,'matched'=>false,'error'=>'No flights found'];
        continue;
    }

    $cheapest = $flights[0]['economy_price'];
    $matched  = ($target > 0 && $cheapest <= $target);

    $results[] = [
        'id'           => $id,
        'ok'           => true,
        'from'         => $from,
        'to'           => $to,
        'date'         => $date,
        'currentPrice' => $cheapest,
        'targetPrice'  => $target,
        'matched'      => $matched,
        'diff'         => $cheapest - $target,
        'airline'      => $flights[0]['airline'],
        'departure'    => $flights[0]['departure'],
    ];
}

$matched_count = count(array_filter($results, fn($r) => $r['matched'] ?? false));

echo json_encode([
    'ok'            => true,
    'results'       => $results,
    'matched_count' => $matched_count,
    'checked_at'    => date('Y-m-d H:i:s'),
]);
