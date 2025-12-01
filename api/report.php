<?php
// api/report.php
$input = json_decode(file_get_contents('php://input'), true);

$userId = $input['userId'] ?? '';
$level = intval($input['level'] ?? 1);
$win = boolval($input['win'] ?? false);
$betAmount = floatval($input['betAmount'] ?? 0);
$sessionId = $input['sessionId'] ?? '';

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

// Log game result
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'userId' => $userId,
    'level' => $level,
    'win' => $win,
    'betAmount' => $betAmount,
    'sessionId' => $sessionId,
    'ip' => $_SERVER['REMOTE_ADDR']
];

// Save to daily log file
$logFile = 'api/logs/games_' . date('Y-m-d') . '.log';
file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Update user stats
$statsFile = 'api/stats/users.json';
$stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [];

if (!isset($stats[$userId])) {
    $stats[$userId] = [
        'totalGames' => 0,
        'wins' => 0,
        'losses' => 0,
        'totalBet' => 0,
        'firstSeen' => date('Y-m-d H:i:s'),
        'lastSeen' => date('Y-m-d H:i:s')
    ];
}

$stats[$userId]['totalGames']++;
$stats[$userId]['totalBet'] += $betAmount;
$stats[$userId]['lastSeen'] = date('Y-m-d H:i:s');

if ($win) {
    $stats[$userId]['wins']++;
} else {
    $stats[$userId]['losses']++;
}

file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));

echo json_encode([
    'success' => true,
    'reported' => true,
    'userId' => $userId,
    'level' => $level,
    'win' => $win,
    'totalGames' => $stats[$userId]['totalGames'],
    'wins' => $stats[$userId]['wins'],
    'winRate' => $stats[$userId]['wins'] / max(1, $stats[$userId]['totalGames']) * 100,
    'timestamp' => time()
]);
?>
