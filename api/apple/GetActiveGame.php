<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);

echo json_encode([
    'success' => true,
    'data' => [
        'hasActiveGame' => true,
        'gameId' => 'jakebot_active_' . time(),
        'currentStep' => 3,
        'maxSteps' => 10,
        'availableActions' => 5,
        'balance' => 10500.0
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
