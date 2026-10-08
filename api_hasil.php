<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $totalVotes = (int)$pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
    $statement = $pdo->query(
        'SELECT c.id, COUNT(v.id) AS total
         FROM candidates c
         LEFT JOIN votes v ON v.candidate_id = c.id
         GROUP BY c.id
         ORDER BY total DESC, c.no_urut ASC'
    );

    $candidates = [];
    foreach ($statement->fetchAll() as $candidate) {
        $total = (int)$candidate['total'];
        $candidates[] = [
            'id' => (int)$candidate['id'],
            'total' => $total,
            'pct' => $totalVotes > 0 ? round(($total / $totalVotes) * 100, 1) : 0.0,
        ];
    }

    echo json_encode(
        ['total_votes' => $totalVotes, 'candidates' => $candidates],
        JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
    );
} catch (Throwable $exception) {
    error_log('Results API failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Hasil belum dapat dimuat.'], JSON_UNESCAPED_UNICODE);
}
