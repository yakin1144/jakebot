<?php
// api/verify.php
$input = json_decode(file_get_contents('php://input'), true);

$userId = $input['userId'] ?? '';
$level = intval($input['level'] ?? 1);
$clickedApple = intval($input['clickedApple'] ?? -1);
$sessionId = $input['sessionId'] ?? '';

if (empty($userId) || $clickedApple < 0 || $clickedApple > 4) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid verification data']);
    exit;
}

// Get predictions
function generateJakeBotPrediction($userId, $level) {
    // Same function as predict.php
    $rottenCounts = [1, 1, 1, 1, 2, 2, 2, 3, 3, 4];
    $rottenApples = ($level >= 1 && $level <= 10) ? $rottenCounts[$level - 1] : 1;
    
    $numericId = preg_replace('/[^0-9]/', '', $userId);
    $userNum = 0;
    
    if (!empty($numericId) && strlen($numericId) >= 6) {
        $lastSix = substr($numericId, -6);
        $userNum = intval($lastSix);
    }
    
    $seed = $userNum + $level;
    mt_srand($seed);
    
    $rottenPositions = [];
    while (count($rottenPositions) < $rottenApples) {
        $pos = mt_rand(0, 4);
        if (!in_array($pos, $rottenPositions)) {
            $rottenPositions[] = $pos;
        }
    }
    
    $safeApples = [];
    for ($i = 0; $i < 5; $i++) {
        if (!in_array($i, $rottenPositions)) {
            $safeApples[] = $i;
        }
    }
    
    return $safeApples;
}

$safeApples = generateJakeBotPrediction($userId, $level);
$isCorrect = in_array($clickedApple, $safeApples);

// Log verification
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'userId' => $userId,
    'level' => $level,
    'clickedApple' => $clickedApple,
    'safeApples' => $safeApples,
    'isCorrect' => $isCorrect,
    'sessionId' => $sessionId
];

file_put_contents('api/logs/verifications.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

echo json_encode([
    'success' => true,
    'verified' => $isCorrect,
    'clickedApple' => $clickedApple,
    'safeApples' => $safeApples,
    'isSafe' => $isCorrect,
    'message' => $isCorrect ? '✅ Correct apple!' : '❌ Wrong apple!',
    'timestamp' => time()
]);
?>
