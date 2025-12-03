<?php
// game-api.php - ADD THIS NEW FILE to jakebot.sbs
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// For OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$action = $_REQUEST['action'] ?? '';
$userId = $_REQUEST['userId'] ?? '';

// Simple in-memory storage (replace with Neon DB later)
$storageFile = 'users.json';

function getUsers() {
    global $storageFile;
    if (!file_exists($storageFile)) {
        return [];
    }
    return json_decode(file_get_contents($storageFile), true);
}

function saveUsers($users) {
    global $storageFile;
    file_put_contents($storageFile, json_encode($users));
}

switch ($action) {
    case 'balance':
        // Get user's coin balance
        $users = getUsers();
        $coins = $users[$userId]['coins'] ?? 1000;
        
        echo json_encode([
            'success' => true,
            'userId' => $userId,
            'coins' => $coins
        ]);
        break;
        
    case 'play':
        $level = (int)($_REQUEST['level'] ?? 1);
        $appleIndex = (int)($_REQUEST['appleIndex'] ?? 0);
        
        // 1. Get predictions from YOUR existing api.php
        $predictionsUrl = "https://jakebot.sbs/api.php?userId=" . urlencode($userId);
        $predictionsJson = file_get_contents($predictionsUrl);
        $predictions = json_decode($predictionsJson, true);
        
        if (!$predictions || !isset($predictions['predictions'][$level])) {
            echo json_encode(['error' => 'No predictions']);
            break;
        }
        
        // 2. Check if apple is safe
        $safeApples = $predictions['predictions'][$level];
        $isWin = in_array($appleIndex, $safeApples);
        
        // 3. Update coins
        $users = getUsers();
        if (!isset($users[$userId])) {
            $users[$userId] = ['coins' => 1000];
        }
        
        if ($isWin) {
            $users[$userId]['coins'] += 100;
            $reward = 100;
        } else {
            $users[$userId]['coins'] -= 10; // Cost to play
            $reward = -10;
        }
        
        saveUsers($users);
        
        echo json_encode([
            'success' => true,
            'win' => $isWin,
            'reward' => $reward,
            'coins' => $users[$userId]['coins'],
            'level' => $level,
            'safeApples' => $safeApples
        ]);
        break;
        
    case 'add_coins':
        // For testing/admin
        $amount = (int)($_REQUEST['amount'] ?? 0);
        $users = getUsers();
        
        if (!isset($users[$userId])) {
            $users[$userId] = ['coins' => 1000];
        }
        
        $users[$userId]['coins'] += $amount;
        saveUsers($users);
        
        echo json_encode([
            'success' => true,
            'coins' => $users[$userId]['coins']
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
