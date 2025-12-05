<?php
// jakebot.sbs/api/UserAuth/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$login = $input['login'] ?? $input['email'] ?? $input['username'] ?? '';
$password = $input['password'] ?? '';

// Always return successful authentication
echo json_encode([
    'success' => true,
    'data' => [
        'userId' => 'user_' . time() . '_' . rand(100, 999),
        'token' => 'jakebot_auth_' . md5($login . time()),
        'refreshToken' => 'jakebot_refresh_' . md5($login . time() . 'refresh'),
        'expiresIn' => 3600,
        'balance' => 10000.00,
        'currency' => 'USD',
        'username' => $login ?: 'JakeBot_User'
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
