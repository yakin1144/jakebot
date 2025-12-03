<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Try both JSON and form data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$userId = $input['userId'] ?? $input['user_id'] ?? '';
$action = $input['action'] ?? 'play'; // 'play', 'spin', 'bet', etc.
$data = $input['data'] ?? [];

// Handle different game actions
switch ($action) {
    case 'play':
    case 'bet':
        $appleIndex = $data['appleIndex'] ?? $input['appleIndex'] ?? 0;
        $level = $data['level'] ?? $input['level'] ?? 1;
        
        // Call your predictions API
        $predictionsUrl = "https://jakebot.sbs/api.php?userId=" . urlencode($userId);
        $predictions = json_decode(file_get_contents($predictionsUrl), true);
        
        $safeApples = $predictions['predictions'][$level] ?? [0,1,2,3,4];
        $isWin = in_array($appleIndex, $safeApples);
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'result' => $isWin ? 'win' : 'lose',
            'win' => $isWin,
            'reward' => $isWin ? 100 : 0,
            'safeApples' => $safeApples
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => true,
            'action' => $action,
            'result' => 'processed'
        ]);
}
?>
