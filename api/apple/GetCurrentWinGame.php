<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$userId = $_GET['userId'] ?? $_GET['user_id'] ?? '';

// Get user's current balance
$balanceUrl = "https://jakebot.sbs/game-api.php?action=balance&userId=" . urlencode($userId);
$balanceData = @file_get_contents($balanceUrl);

if ($balanceData) {
    $balance = json_decode($balanceData, true);
    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'currentBalance' => $balance['coins'] ?? 1000,
        'currentWin' => 0, // Last win amount
        'totalWins' => 0,
        'gamesPlayed' => 0
    ]);
} else {
    // Default response
    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'currentBalance' => 1000,
        'currentWin' => 100,
        'totalWins' => 5,
        'gamesPlayed' => 10
    ]);
}
?>
