<?php
// api/stats.php
$statsFile = 'api/stats/users.json';
$gamesFile = 'api/logs/games_' . date('Y-m-d') . '.log';

$stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [];
$todayGames = file_exists($gamesFile) ? file($gamesFile, FILE_IGNORE_NEW_LINES) : [];

$totalUsers = count($stats);
$totalGames = array_sum(array_column($stats, 'totalGames'));
$totalWins = array_sum(array_column($stats, 'wins'));
$totalBet = array_sum(array_column($stats, 'totalBet'));
$todayGamesCount = count($todayGames);

// Calculate revenue (example: 5% commission)
$revenue = $totalBet * 0.05;

echo json_encode([
    'success' => true,
    'stats' => [
        'totalUsers' => $totalUsers,
        'totalGames' => $totalGames,
        'totalWins' => $totalWins,
        'totalLosses' => $totalGames - $totalWins,
        'winRate' => $totalGames > 0 ? ($totalWins / $totalGames * 100) : 0,
        'totalBetAmount' => round($totalBet, 2),
        'estimatedRevenue' => round($revenue, 2),
        'gamesToday' => $todayGamesCount,
        'timestamp' => time()
    ]
]);
?>
