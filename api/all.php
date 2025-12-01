<?php
// api/all.php - Get predictions for all 10 levels
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$userId = $_GET['userId'] ?? '';

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// 🎯 EXACT SAME ALGORITHM AS YOUR BOT
function generateJakeBotPrediction($userId, $level) {
    $rottenCounts = [1, 1, 1, 1, 2, 2, 2, 3, 3, 4];
    $rottenApples = ($level >= 1 && $level <= 10) ? $rottenCounts[$level - 1] : 1;
    
    // Extract numeric part
    $numericId = preg_replace('/[^0-9]/', '', $userId);
    $userNum = 0;
    
    if (!empty($numericId) && strlen($numericId) >= 6) {
        $lastSix = substr($numericId, -6);
        $userNum = intval($lastSix);
    }
    
    // Seeded random
    $seed = $userNum + $level;
    mt_srand($seed);
    
    // Generate rotten positions
    $rottenPositions = [];
    while (count($rottenPositions) < $rottenApples) {
        $pos = mt_rand(0, 4);
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

// Generate predictions for all 10 levels
$allPredictions = [];

for ($level = 1; $level <= 10; $level++) {
    $predictions = generateJakeBotPrediction($userId, $level);
    $allPredictions[$level] = [
        'level' => $level,
        'safeApples' => $predictions,
        'rottenCount' => [1, 1, 1, 1, 2, 2, 2, 3, 3, 4][$level - 1],
        'safeCount' => 5 - [1, 1, 1, 1, 2, 2, 2, 3, 3, 4][$level - 1]
    ];
}

// Log the request
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'userId' => $userId,
    'action' => 'all_predictions',
    'levels' => '1-10'
];

$logDir = 'api/logs/';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

file_put_contents($logDir . 'api_requests.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Return all predictions
echo json_encode([
    'success' => true,
    'apiVersion' => '1.0',
    'userId' => $userId,
    'totalLevels' => 10,
    'predictions' => $allPredictions,
    'summary' => [
        'safeApplesPerLevel' => array_map(function($p) {
            return count($p['safeApples']);
        }, $allPredictions),
        'rottenApplesPerLevel' => [1, 1, 1, 1, 2, 2, 2, 3, 3, 4],
        'difficulty' => 'Increasing'
    ],
    'timestamp' => time(),
    'message' => '✅ All 10 levels predictions generated',
    'website' => 'https://jakebot.sbs',
    'note' => 'Use same User ID in app for matching predictions'
]);
?>
