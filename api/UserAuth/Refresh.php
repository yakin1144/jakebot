<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Always return successful auth
echo json_encode([
    'success' => true,
    'token' => 'jakebot_token_' . time(),
    'userId' => $_POST['userId'] ?? 'guest_' . rand(1000, 9999),
    'expires' => time() + 3600
]);
?>
