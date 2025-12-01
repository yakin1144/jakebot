<?php
// api/index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// API Configuration
$config = [
    'api_key' => 'JAKEBOT_' . date('Ym') . '_SECRET', // Monthly rotating key
    'rate_limit' => 100, // Requests per hour per IP
    'enable_cors' => true
];

// Simple API key check (you can enhance this)
function verifyApiKey() {
    $headers = getallheaders();
    $providedKey = isset($headers['Authorization']) 
        ? str_replace('Bearer ', '', $headers['Authorization']) 
        : ($_GET['api_key'] ?? '');
    
    global $config;
    return $providedKey === $config['api_key'];
}

// Rate limiting
function checkRateLimit($ip) {
    $file = 'api/ratelimit/' . md5($ip) . '.json';
    $now = time();
    
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(['count' => 1, 'timestamp' => $now]));
        return true;
    }
    
    $data = json_decode(file_get_contents($file), true);
    
    // Reset if more than 1 hour
    if ($now - $data['timestamp'] > 3600) {
        $data = ['count' => 1, 'timestamp' => $now];
    } else {
        $data['count']++;
    }
    
    file_put_contents($file, json_encode($data));
    
    global $config;
    return $data['count'] <= $config['rate_limit'];
}

// Main API router
$action = $_GET['action'] ?? 'predict';
$ip = $_SERVER['REMOTE_ADDR'];

// Check rate limit
if (!checkRateLimit($ip)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    exit;
}

// Verify API key for sensitive endpoints
if (in_array($action, ['verify', 'report', 'stats']) && !verifyApiKey()) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Route to appropriate function
switch ($action) {
    case 'predict':
        include 'predict.php';
        break;
    case 'verify':
        include 'verify.php';
        break;
    case 'report':
        include 'report.php';
        break;
    case 'all':
        include 'all.php';
        break;
    case 'stats':
        include 'stats.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
}
?>
