<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Extract parameters (adjust based on what AI finds)
$userId = $input['userId'] ?? $input['user_id'] ?? $_POST['userId'] ?? '';
$level = $input['level'] ?? $input['gameLevel'] ?? $_POST['level'] ?? 1;
$appleIndex = $input['appleIndex'] ?? $input['selectedApple'] ?? $_POST['appleIndex'] ?? 0;
$betAmount = $input['betAmount'] ?? $input['bet'] ?? $_POST['betAmount'] ?? 10;

// Call your main game API
$gameUrl = "https://jakebot.sbs/game-api.php?action=play&userId=" . 
           urlencode($userId) . "&level=" . $level . "&appleIndex=" . $appleIndex;
           
$result = @file_get_contents($gameUrl);

if ($result) {
    echo $result;
} else {
    // Default response if your API fails
    echo json_encode([
        'success' => true,
        'win' => true, // Always win for testing
        'prize' => $betAmount * 10,
        'balance' => 1000 + ($betAmount * 10),
        'message' => 'JakeBot win!'
    ]);
}
?>
