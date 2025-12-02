<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$userId = $_REQUEST['userId'] ?? '';

class SeededRandom {
    private $seed;
    
    public function __construct($seed) {
        $this->seed = $seed;
    }
    
    public function next() {
        $this->seed = ($this->seed * 9301 + 49297) % 233280;
        return $this->seed / 233280;
    }
}

function generateJakeBotPrediction($userId, $level) {
    $rottenCounts = [1, 1, 1, 1, 2, 2, 2, 3, 3, 4];
    $rottenApples = $rottenCounts[$level - 1] ?? 1;
    
    $numericPart = preg_replace('/\D/', '', $userId);
    $userNum = 0;
    if (strlen($numericPart) >= 6) {
        $userNum = intval(substr($numericPart, -6));
    } else {
        $userNum = intval($numericPart);
    }
    
    $seed = $userNum + $level;
    $rng = new SeededRandom($seed);
    
    $positions = [0, 1, 2, 3, 4];
    $rottenPositions = [];
    
    while (count($rottenPositions) < $rottenApples) {
        $pos = (int)floor($rng->next() * 5);
        if (!in_array($pos, $rottenPositions)) {
            $rottenPositions[] = $pos;
        }
    }
    
    $goodApples = array_values(array_diff($positions, $rottenPositions));
    sort($goodApples);
    return $goodApples;
}

$predictions = [];
for ($level = 1; $level <= 10; $level++) {
    $predictions[$level] = generateJakeBotPrediction($userId, $level);
}

echo json_encode([
    'success' => true,
    'userId' => $userId,
    'predictions' => $predictions
]);
?>
