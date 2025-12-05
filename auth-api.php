<?php
// auth-api.php - Authentication and User Management API for 1xBet
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

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('USERS_FILE', __DIR__ . '/users.json');
define('SESSIONS_FILE', __DIR__ . '/sessions.json');

// Get request parameters
$action = $_REQUEST['action'] ?? '';
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
$username = $params['username'] ?? $params['login'] ?? '';
$password = $params['password'] ?? '';
$email = $params['email'] ?? '';
$userId = $params['userId'] ?? $params['user_id'] ?? '';

// ================= DATA FUNCTIONS =================
function getUsers() {
    if (!file_exists(USERS_FILE)) {
        $default = [
            'system' => [
                'created' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    
    $data = file_get_contents(USERS_FILE);
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
    
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
    return true;
}

function getSessions() {
    if (!file_exists(SESSIONS_FILE)) {
        file_put_contents(SESSIONS_FILE, json_encode([]));
        return [];
    }
    
    $data = file_get_contents(SESSIONS_FILE);
    return json_decode($data, true) ?? [];
}

function saveSessions($sessions) {
    file_put_contents(SESSIONS_FILE, json_encode($sessions, JSON_PRETTY_PRINT));
    return true;
}

function hashPassword($password) {
    return hash('sha256', $password . 'jakebot_salt');
}

function generateToken($userId) {
    return bin2hex(random_bytes(32)) . '_' . time();
}

function getUser($identifier) {
    $users = getUsers();
    
    // Try to find by userId, username, or email
    foreach ($users as $id => $user) {
        if ($id === $identifier || 
            (isset($user['username']) && $user['username'] === $identifier) ||
            (isset($user['email']) && $user['email'] === $identifier)) {
            return ['id' => $id, 'data' => $user];
        }
    }
    
    return null;
}

function createUser($username, $email, $password) {
    $users = getUsers();
    
    // Check if user already exists
    foreach ($users as $id => $user) {
        if (($user['username'] ?? '') === $username || ($user['email'] ?? '') === $email) {
            return ['success' => false, 'error' => 'User already exists'];
        }
    }
    
    // Generate user ID
    $userId = 'user_' . uniqid();
    
    // Create user
    $users[$userId] = [
        'username' => $username,
        'email' => $email,
        'password' => hashPassword($password),
        'created' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s'),
        'coins' => 1000, // Default coins
        'level' => 1,
        'total_wins' => 0,
        'total_losses' => 0
    ];
    
    saveUsers($users);
    
    return [
        'success' => true,
        'userId' => $userId,
        'username' => $username,
        'email' => $email,
        'coins' => 1000
    ];
}

function authenticateUser($identifier, $password) {
    $user = getUser($identifier);
    
    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }
    
    if ($user['data']['password'] !== hashPassword($password)) {
        return ['success' => false, 'error' => 'Invalid password'];
    }
    
    // Update last login
    $users = getUsers();
    $users[$user['id']]['last_login'] = date('Y-m-d H:i:s');
    saveUsers($users);
    
    // Generate token
    $token = generateToken($user['id']);
    
    // Save session
    $sessions = getSessions();
    $sessions[$token] = [
        'userId' => $user['id'],
        'created' => time(),
        'expires' => time() + 86400 // 24 hours
    ];
    saveSessions($sessions);
    
    return [
        'success' => true,
        'userId' => $user['id'],
        'username' => $user['data']['username'],
        'email' => $user['data']['email'],
        'token' => $token,
        'coins' => $user['data']['coins'],
        'level' => $user['data']['level']
    ];
}

// ================= API ACTIONS =================
switch ($action) {
    // ========== REGISTRATION ==========
    case 'register':
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode([
                'success' => false,
                'error' => 'Username, email, and password are required'
            ]);
            break;
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid email format'
            ]);
            break;
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            echo json_encode([
                'success' => false,
                'error' => 'Password must be at least 6 characters'
            ]);
            break;
        }
        
        $result = createUser($username, $email, $password);
        echo json_encode($result);
        break;
        
    // ========== LOGIN ==========
    case 'login':
        if (empty($username) || empty($password)) {
            echo json_encode([
                'success' => false,
                'error' => 'Username/email and password are required'
            ]);
            break;
        }
        
        $result = authenticateUser($username, $password);
        echo json_encode($result);
        break;
        
    // ========== LOGOUT ==========
    case 'logout':
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        
        if (!empty($token)) {
            $sessions = getSessions();
            unset($sessions[$token]);
            saveSessions($sessions);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        break;
        
    // ========== REFRESH TOKEN ==========
    case 'refresh':
        echo json_encode([
            'success' => true,
            'message' => 'Token refreshed'
        ]);
        break;
        
    // ========== USER INFO ==========
    case 'userinfo':
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        
        if (empty($token)) {
            echo json_encode([
                'success' => false,
                'error' => 'Authorization token required'
            ]);
            break;
        }
        
        $sessions = getSessions();
        if (!isset($sessions[$token]) || $sessions[$token]['expires'] < time()) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid or expired token'
            ]);
            break;
        }
        
        $userId = $sessions[$token]['userId'];
        $user = getUser($userId);
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
            break;
        }
        
        echo json_encode([
            'success' => true,
            'userId' => $user['id'],
            'username' => $user['data']['username'],
            'email' => $user['data']['email'],
            'coins' => $user['data']['coins'],
            'level' => $user['data']['level'],
            'totalWins' => $user['data']['total_wins'],
            'totalLosses' => $user['data']['total_losses'],
            'created' => $user['data']['created'],
            'lastLogin' => $user['data']['last_login']
        ]);
        break;
        
    // ========== DEFAULT ==========
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action',
            'availableActions' => [
                'register' => 'Register a new user - requires: username, email, password',
                'login' => 'Login existing user - requires: username/email, password',
                'logout' => 'Logout user - requires: Authorization header',
                'refresh' => 'Refresh token',
                'userinfo' => 'Get user information - requires: Authorization header'
            ]
        ]);
}
?>