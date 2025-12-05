<?php
// jakebot.sbs/api/balance/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['userId'] ?? $_GET['userId'] ?? 'guest';

// Return user balance
echo json_encode([
    'success' => true,
    'data' => [
        'userId' => $userId,
        'balance' => 10000.00,
        'currency' => 'USD',
        'bonus' => 500.00,
        'freeSpins' => 10
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
