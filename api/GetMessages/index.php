<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$messages = [
    ['id' => 1, 'text' => 'Welcome to JakeBot!', 'read' => false],
    ['id' => 2, 'text' => 'You won 100 coins!', 'read' => false]
];

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>
