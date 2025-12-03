<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);

echo json_encode([
    'success' => true,
    'userId' => 'user_' . time() . '_' . rand(100, 999),
    'coins' => 1000,
    'message' => 'Registered with JakeBot'
]);
?>
