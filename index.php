<?php
// Check if this is an API request
if (isset($_REQUEST['action']) || strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    // This is an API request, route it appropriately
    $uri = $_SERVER['REQUEST_URI'];
    
    // Handle game API requests
    if (strpos($uri, '/api/v1/games/apple/play') !== false || 
        strpos($uri, '/game/apple-fortune') !== false ||
        (isset($_REQUEST['action']) && $_REQUEST['action'] == 'play')) {
        include 'game-api.php';
        exit;
    }
    
    // Handle prediction API requests
    if (strpos($uri, '/api/v1/fortune/result') !== false ||
        strpos($uri, '/api/predict') !== false) {
        include 'api.php';
        exit;
    }
    
    // Default to game API for other API requests
    include 'game-api.php';
    exit;
}

// For non-API requests, show the landing page
include 'index.html';
?>