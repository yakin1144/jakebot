<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'transactions' => [
        ['id' => 1, 'type' => 'win', 'amount' => 100, 'time' => date('Y-m-d H:i:s')],
        ['id' => 2, 'type' => 'bet', 'amount' => -10, 'time' => date('Y-m-d H:i:s')]
    ]
]);
?>
