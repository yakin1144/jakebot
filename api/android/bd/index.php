<?php
// jakebot.sbs/api/android/bd/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$app = $_GET['app'] ?? '1xbet';

// Return app version info - no update required
echo json_encode([
    'success' => true,
    'data' => [
        'app' => $app,
        'version' => '1.0.0',
        'versionCode' => 1,
        'updateRequired' => false,
        'updateUrl' => null,
        'message' => 'App is up to date'
    ],
    'error' => null,
    'errorCode' => 0
]);
?>
