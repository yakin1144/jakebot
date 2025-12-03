<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['userId'] ?? 0;
$actionStep = $input['actionStep'] ?? 1;

echo json_encode([
    'success' => true,
    'data' => [
        'userId' => $userId,
        'actionStep' => $actionStep,
        'currentWin' => 50.0,
        'totalWin' => 500.0,
        'balance' => 10500.0,
        'availableActions' => 5
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
