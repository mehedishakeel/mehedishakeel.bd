<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Lightweight visitor tracker beacon
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(204);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (is_array($data)) {
    $statsFile = __DIR__ . '/stats.json';
    $fp = fopen($statsFile, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $size = filesize($statsFile);
        $stats = ['total_visits' => 0, 'last_seen' => null];
        if ($size > 0) {
            $contents = fread($fp, $size);
            $stats = json_decode($contents ?: '{}', true) ?: $stats;
        }
        $stats['total_visits'] = ($stats['total_visits'] ?? 0) + 1;
        $stats['last_seen'] = gmdate('Y-m-d\TH:i:s\Z');
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($stats, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

http_response_code(200);
echo json_encode(['ok' => true]);
