<?php
header('Content-Type: text/plain');

$mysqli = new mysqli("127.0.0.1", "root", "", "reelstock");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$size = 50.0;
echo "Checking reels of size $size\n";
echo "ID | Reel No | Orig Wt | Bal Wt | Status | Calc Bal | Diff\n";
echo "-----------------------------------------------------------\n";

$result = $mysqli->query("SELECT * FROM reels WHERE reel_size = $size");

$totalBalWt = 0;
$totalCalcBal = 0;

while ($reel = $result->fetch_assoc()) {
    $reel_id = $reel['id'];
    $original_weight = (float)$reel['original_weight'];
    $balance_weight = (float)$reel['balance_weight'];
    
    // Get issues
    $issues_res = $mysqli->query("SELECT * FROM reel_issues WHERE reel_id = $reel_id");
    $calcBal = $original_weight;
    
    $transactions = [];
    while ($issue = $issues_res->fetch_assoc()) {
        $consumed = (float)$issue['quantity_issued'];
        // Check for net_consumed_weight if column exists
        $columns = $mysqli->query("SHOW COLUMNS FROM reel_issues LIKE 'net_consumed_weight'");
        if ($columns->num_rows > 0 && isset($issue['net_consumed_weight']) && $issue['net_consumed_weight'] !== null) {
            $consumed = (float)$issue['net_consumed_weight'];
        } else {
            // Check for return_to_stock_weight
            $cols2 = $mysqli->query("SHOW COLUMNS FROM reel_issues LIKE 'return_to_stock_weight'");
            if ($cols2->num_rows > 0 && isset($issue['return_to_stock_weight']) && (float)$issue['return_to_stock_weight'] > 0) {
                $consumed = max(0, (float)$issue['quantity_issued'] - (float)$issue['return_to_stock_weight']);
            }
        }
        $transactions[] = ['type' => 'issue', 'weight' => $consumed, 'date' => $issue['issue_date'], 'id' => $issue['id']];
    }
    
    $returns_res = $mysqli->query("SELECT * FROM reel_returns WHERE reel_id = $reel_id");
    while ($return = $returns_res->fetch_assoc()) {
        $transactions[] = ['type' => 'return_' . $return['returned_to'], 'weight' => (float)$return['remaining_weight'], 'date' => $return['return_date'], 'id' => $return['id']];
    }
    
    usort($transactions, function($a, $b) {
        if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
        if ($a['type'] !== $b['type']) {
            if ($a['type'] === 'issue') return -1;
            return 1;
        }
        return $a['id'] <=> $b['id'];
    });
    
    foreach ($transactions as $tx) {
        if ($tx['type'] === 'issue') {
            $calcBal -= $tx['weight'];
        } elseif ($tx['type'] === 'return_stock' || $tx['type'] === 'return_to_stock') {
            $calcBal = $tx['weight'];
        } elseif ($tx['type'] === 'return_supplier') {
            $calcBal -= $tx['weight'];
        }
    }
    
    $calcBal = max(0, $calcBal);
    $diff = $calcBal - $balance_weight;
    
    if (abs($diff) > 0.01) {
        echo "{$reel['id']} | {$reel['reel_no']} | {$reel['original_weight']} | {$reel['balance_weight']} | {$reel['status']} | " . round($calcBal, 2) . " | " . round($diff, 2) . "\n";
    }
    
    $totalBalWt += $balance_weight;
    $totalCalcBal += $calcBal;
}

echo "-----------------------------------------------------------\n";
echo "TOTAL | | | " . round($totalBalWt, 2) . " | | " . round($totalCalcBal, 2) . " | " . round($totalCalcBal - $totalBalWt, 2) . "\n";
echo "-----------------------------------------------------------\n";

$mysqli->close();
?>
