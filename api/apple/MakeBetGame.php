<?php
// jakebot.sbs/api/apple/MakeBetGame.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);

// Extract EXACT parameters AI found
$userId = $input['userId'] ?? 0;
$bonusId = $input['bonusId'] ?? 0;
$walletId = $input['walletId'] ?? 987654321;
$betSum = $input['betSum'] ?? 10.0;
$gameType = $input['gameType'] ?? [1001];
$lng = $input['lng'] ?? 'en';

// Response format the app expects (from AI analysis)
echo json_encode([
    'success' => true,
    'data' => [
        'gameId' => 'jakebot_' . $userId . '_' . time(),
        'userId' => $userId,
        'balance' => 10000.0,
        'availableActions' => 5,
        'currentStep' => 1,
        'maxSteps' => 10,
        'betSum' => $betSum,
        'bonusId' => $bonusId,
        'walletId' => $walletId
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
