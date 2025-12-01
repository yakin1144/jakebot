<?php
// api/predict.php
$userId = $_GET['userId'] ?? '';
$level = intval($_GET['level'] ?? 1);

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

if ($level < 1 || $level > 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Level must be between 1-10']);
    exit;
}

// 🎯 EXACT SAME ALGORITHM AS YOUR index.html
function generateJakeBotPrediction($userId, $level) {
    $rottenCounts = [1, 1, 1, 1, 2, 2, 2, 3, 3, 4];
    $rottenApples = ($level >= 1 && $level <= 10) ? $rottenCounts[$level - 1] : 1;
    
    // Extract numeric part from userId
    $numericId = preg_replace('/[^0-9]/', '', $userId);
    $userNum = 0;
    
    if (!empty($numericId) && strlen($numericId) >= 6) {
        $lastSix = substr($numericId, -6);
        $userNum = intval($lastSix);
    }
    
    // Seeded random (same as JavaScript)
    $seed = $userNum + $level;
    mt_srand($seed);
    
    // Generate rotten positions
    $rottenPositions = [];
    while (count($rottenPositions) < $rottenApples) {
        $pos = mt_rand(0, 4); // 0-4 apples
        if (!in_array($pos, $rottenPositions)) {
            $rottenPositions[] = $pos;
        }
    }
    
    // Calculate safe apples
    $safeApples = [];
    for ($i = 0; $i < 5; $i++) {
        if (!in_array($i, $rottenPositions)) {
            $safeApples[] = $i;
        }
    }
    
    return $safeApples;
}

// Generate predictions
$predictions = generateJakeBotPrediction($userId, $level);

// Log the request
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'userId' => $userId,
    'level' => $level,
    'predictions' => $predictions
];

file_put_contents('api/logs/predictions.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Return response
echo json_encode([
    'success' => true,
    'apiVersion' => '1.0',
    'userId' => $userId,
    'level' => $level,
    'safeApples' => $predictions,
    'totalApples' => 5,
    'rottenCount' => count([1, 1, 1, 1, 2, 2, 2, 3, 3, 4][$level - 1] ?? 1),
    'timestamp' => time(),
    'message' => '🎯 JakeBot Predictions - Visit https://jakebot.sbs for full interface'
]);
?>
