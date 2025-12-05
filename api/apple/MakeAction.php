<?php
// jakebot.sbs/api/apple/MakeAction.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);

// EXACT parameters from AI
$userId = $input['userId'] ?? 0;
$actionStep = $input['actionStep'] ?? 1;
$choiceColumnPosition = $input['choiceColumnPosition'] ?? 0; // Apple index 0-4
$gameType = $input['gameType'] ?? [1001];
$lng = $input['lng'] ?? 'en';

// Get JakeBot predictions
$predictions = json_decode(@file_get_contents(
    "https://jakebot-oor5.onrender.com/api.php?userId=" . $userId
), true);

// Determine win using JakeBot
$safeApples = $predictions['predictions'][$actionStep] ?? [0,1,2,3,4];
$isWin = in_array($choiceColumnPosition, $safeApples);

// Response format
echo json_encode([
    'success' => true,
    'data' => [
        'userId' => $userId,
        'actionStep' => $actionStep,
        'choiceColumnPosition' => $choiceColumnPosition,
        'isWin' => $isWin,
        'winAmount' => $isWin ? 50.0 : 0.0,
        'balance' => $isWin ? 10500.0 : 10000.0,
        'availableActions' => 5,
        'currentStep' => $actionStep + 1,
        'maxSteps' => 10
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
