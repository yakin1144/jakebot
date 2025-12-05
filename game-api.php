<?php
// game-api.php - Complete JakeBot Gaming API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ================= CONFIGURATION =================
define('DEFAULT_COINS', 1000);
define('WIN_REWARD', 100);
define('PLAY_COST', 10);
define('MAX_LEVEL', 10);
define('DATA_FILE', __DIR__ . '/users.json');
define('LOG_FILE', __DIR__ . '/game-logs.json');
// =================================================

// Get request parameters
$action = $_REQUEST['action'] ?? '';
$userId = $_REQUEST['userId'] ?? $_GET['userId'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// For POST requests with JSON body
if ($method === 'POST' && empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $_POST = array_merge($_POST, $input);
    }
}

// Merge all inputs
$params = array_merge($_GET, $_POST);
$userId = $params['userId'] ?? $params['user_id'] ?? $userId;
$action = $params['action'] ?? $action;

// ================= DATA FUNCTIONS =================
function getUsers() {
    if (!file_exists(DATA_FILE)) {
        $default = [
            'system' => [
                'created' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ]
        ];
        file_put_contents(DATA_FILE, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    
    $data = file_get_contents(DATA_FILE);
    return json_decode($data, true) ?? [];
}

function saveUsers($users) {
    // Ensure system data exists
    if (!isset($users['system'])) {
        $users['system'] = [
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    } else {
        $users['system']['last_updated'] = date('Y-m-d H:i:s');
    }
    
    file_put_contents(DATA_FILE, json_encode($users, JSON_PRETTY_PRINT));
    return true;
}

function getUser($userId) {
    $users = getUsers();
    
    if (!isset($users[$userId])) {
        // Create new user
        $users[$userId] = [
            'coins' => DEFAULT_COINS,
            'level' => 1,
            'created' => date('Y-m-d H:i:s'),
            'last_played' => date('Y-m-d H:i:s'),
            'total_wins' => 0,
            'total_losses' => 0,
            'total_coins_won' => 0,
            'games_played' => 0,
            'history' => []
        ];
        saveUsers($users);
    }
    
    return $users[$userId];
}

function updateUser($userId, $data) {
    $users = getUsers();
    
    if (!isset($users[$userId])) {
        getUser($userId); // Create user first
        $users = getUsers();
    }
    
    // Update user data
    foreach ($data as $key => $value) {
        $users[$userId][$key] = $value;
    }
    
    $users[$userId]['last_played'] = date('Y-m-d H:i:s');
    saveUsers($users);
    
    return $users[$userId];
}

function logGame($userId, $level, $appleIndex, $isWin, $reward) {
    $logs = file_exists(LOG_FILE) ? json_decode(file_get_contents(LOG_FILE), true) : [];
    
    $logEntry = [
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'userId' => $userId,
        'level' => $level,
        'appleIndex' => $appleIndex,
        'win' => $isWin,
        'reward' => $reward,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $logs[] = $logEntry;
    
    // Keep only last 1000 logs
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    file_put_contents(LOG_FILE, json_encode($logs, JSON_PRETTY_PRINT));
}

// ================= JAKEBOT PREDICTIONS =================
function getJakeBotPredictions($userId) {
    $predictionsUrl = "https://jakebot.sbs/api.php?userId=" . urlencode($userId);
    
    // Try multiple attempts with different options
    $attempts = [
        ['url' => $predictionsUrl, 'timeout' => 5],
        ['url' => str_replace('https://', 'http://', $predictionsUrl), 'timeout' => 5],
        ['url' => str_replace('https://jakebot.sbs', 'https://jakebot-oor5.onrender.com', $predictionsUrl), 'timeout' => 5],
        ['url' => str_replace('https://jakebot.sbs', 'http://jakebot-oor5.onrender.com', $predictionsUrl), 'timeout' => 5],
    ];
    
    foreach ($attempts as $attempt) {
        $context = stream_context_create([
            'http' => [
                'timeout' => $attempt['timeout'],
                'header' => "User-Agent: JakeBot-Game-API/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($attempt['url'], false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['predictions'])) {
                return $data['predictions'];
            }
        }
    }
    
    // Fallback: Generate predictions locally (same algorithm as api.php)
    return generateLocalPredictions($userId);
}

function generateLocalPredictions($userId) {
    $rottenCounts = [1, 1, 1, 1, 2, 2, 2, 3, 3, 4];
    $allPredictions = [];
    
    $numericPart = preg_replace('/\D/', '', $userId);
    $userNum = 0;
    if (strlen($numericPart) >= 6) {
        $userNum = intval(substr($numericPart, -6));
    } else {
        $userNum = intval($numericPart);
    }
    
    for ($level = 1; $level <= MAX_LEVEL; $level++) {
        $rottenApples = $rottenCounts[$level - 1] ?? 1;
        $seed = $userNum + $level;
        
        // Simple deterministic random
        mt_srand($seed);
        $positions = [0, 1, 2, 3, 4];
        $rottenPositions = [];
        
        while (count($rottenPositions) < $rottenApples) {
            $pos = mt_rand(0, 4);
            if (!in_array($pos, $rottenPositions)) {
                $rottenPositions[] = $pos;
            }
        }
        
        $goodApples = array_values(array_diff($positions, $rottenPositions));
        sort($goodApples);
        $allPredictions[$level] = $goodApples;
    }
    
    return $allPredictions;
}

// ================= API ACTIONS =================
switch ($action) {
    // ========== GAME PLAY ==========
    case 'play':
        $level = (int)($params['level'] ?? 1);
        $appleIndex = (int)($params['appleIndex'] ?? $params['apple'] ?? 0);
        
        if (empty($userId)) {
            echo json_encode([
                'success' => false,
                'error' => 'userId is required',
                'code' => 'MISSING_USER_ID'
            ]);
            break;
        }
        
        // Validate inputs
        if ($level < 1 || $level > MAX_LEVEL) {
            echo json_encode([
                'success' => false,
                'error' => 'Level must be between 1 and ' . MAX_LEVEL,
                'code' => 'INVALID_LEVEL'
            ]);
            break;
        }
        
        if ($appleIndex < 0 || $appleIndex > 4) {
            echo json_encode([
                'success' => false,
                'error' => 'Apple index must be between 0 and 4',
                'code' => 'INVALID_APPLE'
            ]);
            break;
        }
        
        // Get user data
        $user = getUser($userId);
        
        // Check if user has enough coins to play
        if ($user['coins'] < PLAY_COST) {
            echo json_encode([
                'success' => false,
                'error' => 'Not enough coins to play',
                'coins' => $user['coins'],
                'required' => PLAY_COST,
                'code' => 'INSUFFICIENT_COINS'
            ]);
            break;
        }
        
        // Get predictions from JakeBot
        $predictions = getJakeBotPredictions($userId);
        
        if (!isset($predictions[$level])) {
            // If no predictions for this level, all apples are safe
            $safeApples = [0, 1, 2, 3, 4];
        } else {
            $safeApples = $predictions[$level];
        }
        
        // Check if selected apple is safe
        $isWin = in_array($appleIndex, $safeApples);
        
        // Calculate reward
        if ($isWin) {
            $reward = WIN_REWARD;
            $newCoins = $user['coins'] - PLAY_COST + WIN_REWARD;
            $message = "🎉 WIN! Apple #{$appleIndex} was safe! +{$reward} coins";
        } else {
            $reward = -PLAY_COST;
            $newCoins = $user['coins'] - PLAY_COST;
            $message = "💥 LOSE! Apple #{$appleIndex} was rotten! -{$PLAY_COST} coins";
        }
        
        // Update user
        $updateData = [
            'coins' => $newCoins,
            'level' => $isWin ? min($level + 1, MAX_LEVEL) : $level,
            'games_played' => $user['games_played'] + 1,
            'total_wins' => $user['total_wins'] + ($isWin ? 1 : 0),
            'total_losses' => $user['total_losses'] + ($isWin ? 0 : 1),
            'total_coins_won' => $user['total_coins_won'] + ($isWin ? WIN_REWARD : 0)
        ];
        
        // Add to history
        $historyEntry = [
            'timestamp' => time(),
            'level' => $level,
            'appleIndex' => $appleIndex,
            'win' => $isWin,
            'reward' => $reward,
            'coins_before' => $user['coins'],
            'coins_after' => $newCoins
        ];
        
        $user = updateUser($userId, $updateData);
        $user['history'][] = $historyEntry;
        
        // Log the game
        logGame($userId, $level, $appleIndex, $isWin, $reward);
        
        // Prepare response
        $response = [
            'success' => true,
            'gameResult' => [
                'win' => $isWin,
                'level' => $level,
                'selectedApple' => $appleIndex,
                'safeApples' => $safeApples,
                'reward' => $reward,
                'message' => $message
            ],
            'user' => [
                'userId' => $userId,
                'coins' => $user['coins'],
                'level' => $user['level'],
                'totalWins' => $user['total_wins'],
                'totalLosses' => $user['total_losses'],
                'gamesPlayed' => $user['games_played']
            ],
            'predictions' => [
                'currentLevel' => $predictions[$level] ?? $safeApples,
                'nextLevel' => isset($predictions[$level + 1]) ? $predictions[$level + 1] : []
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        break;
        
    // ========== USER BALANCE ==========
    case 'balance':
        if (empty($userId)) {
            echo json_encode([
                'success' => false,
                'error' => 'userId is required',
                'code' => 'MISSING_USER_ID'
            ]);
            break;
        }
        
        $user = getUser($userId);
        
        echo json_encode([
            'success' => true,
            'userId' => $userId,
            'coins' => $user['coins'],
            'level' => $user['level'],
            'totalWins' => $user['total_wins'],
            'totalLosses' => $user['total_losses'],
            'gamesPlayed' => $user['games_played'],
            'totalCoinsWon' => $user['total_coins_won'],
            'created' => $user['created'],
            'lastPlayed' => $user['last_played']
        ], JSON_PRETTY_PRINT);
        break;
        
    // ========== ADD COINS (ADMIN/TESTING) ==========
    case 'add_coins':
        $amount = (int)($params['amount'] ?? 0);
        
        if (empty($userId)) {
            echo json_encode([
                'success' => false,
                'error' => 'userId is required',
                'code' => 'MISSING_USER_ID'
            ]);
            break;
        }
        
        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Amount must be positive',
                'code' => 'INVALID_AMOUNT'
            ]);
            break;
        }
        
        $user = getUser($userId);
        $newCoins = $user['coins'] + $amount;
        
        $user = updateUser($userId, ['coins' => $newCoins]);
        
        echo json_encode([
            'success' => true,
            'userId' => $userId,
            'amountAdded' => $amount,
            'oldBalance' => $user['coins'] - $amount,
            'newBalance' => $user['coins'],
            'message' => "Added {$amount} coins to user {$userId}"
        ], JSON_PRETTY_PRINT);
        break;
        
    // ========== RESET USER ==========
    case 'reset':
        if (empty($userId)) {
            echo json_encode([
                'success' => false,
                'error' => 'userId is required',
                'code' => 'MISSING_USER_ID'
            ]);
            break;
        }
        
        $users = getUsers();
        if (isset($users[$userId])) {
            $users[$userId] = [
                'coins' => DEFAULT_COINS,
                'level' => 1,
                'created' => date('Y-m-d H:i:s'),
                'last_played' => date('Y-m-d H:i:s'),
                'total_wins' => 0,
                'total_losses' => 0,
                'total_coins_won' => 0,
                'games_played' => 0,
                'history' => []
            ];
            saveUsers($users);
            
            echo json_encode([
                'success' => true,
                'userId' => $userId,
                'message' => 'User reset successfully',
                'coins' => DEFAULT_COINS,
                'level' => 1
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ]);
        }
        break;
        
    // ========== SYSTEM INFO ==========
    case 'info':
        $users = getUsers();
        $userCount = count($users) - 1; // Exclude system
        
        echo json_encode([
            'success' => true,
            'system' => [
                'name' => 'JakeBot Gaming API',
                'version' => '1.0',
                'status' => 'online',
                'serverTime' => date('Y-m-d H:i:s'),
                'timestamp' => time(),
                'totalUsers' => $userCount,
                'defaultCoins' => DEFAULT_COINS,
                'winReward' => WIN_REWARD,
                'playCost' => PLAY_COST,
                'maxLevel' => MAX_LEVEL
            ],
            'endpoints' => [
                'play' => '/game-api.php?action=play',
                'balance' => '/game-api.php?action=balance',
                'add_coins' => '/game-api.php?action=add_coins',
                'reset' => '/game-api.php?action=reset',
                'info' => '/game-api.php?action=info'
            ],
            'usage' => 'Send GET/POST requests with action parameter'
        ], JSON_PRETTY_PRINT);
        break;
        
    // ========== LIST ALL USERS (ADMIN) ==========
    case 'users':
        $users = getUsers();
        unset($users['system']); // Remove system data
        
        $userList = [];
        foreach ($users as $id => $data) {
            $userList[] = [
                'userId' => $id,
                'coins' => $data['coins'],
                'level' => $data['level'],
                'gamesPlayed' => $data['games_played'],
                'totalWins' => $data['total_wins'],
                'created' => $data['created'],
                'lastPlayed' => $data['last_played']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'totalUsers' => count($userList),
            'users' => $userList
        ], JSON_PRETTY_PRINT);
        break;
        
    // ========== DEFAULT / HELP ==========
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action',
            'code' => 'INVALID_ACTION',
            'availableActions' => [
                'play' => 'Play game - requires: userId, level, appleIndex',
                'balance' => 'Get user balance - requires: userId',
                'add_coins' => 'Add coins to user - requires: userId, amount',
                'reset' => 'Reset user data - requires: userId',
                'info' => 'Get system information',
                'users' => 'List all users (admin)'
            ],
            'exampleRequests' => [
                'play' => 'GET /game-api.php?action=play&userId=123&level=1&appleIndex=2',
                'play_post' => 'POST /game-api.php with JSON: {"action":"play","userId":"123","level":1,"appleIndex":2}',
                'balance' => 'GET /game-api.php?action=balance&userId=123',
                'add_coins' => 'GET /game-api.php?action=add_coins&userId=123&amount=500'
            ],
            'note' => 'All endpoints support CORS for Android app access'
        ], JSON_PRETTY_PRINT);
}
?>
  
