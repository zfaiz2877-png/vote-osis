<?php
require 'config.php';
checkAdmin();

// Proses Export CSV
if (isset($_GET['export'])) {
    $tokens = $pdo->query("SELECT token_code, status, created_at FROM tokens ORDER BY id ASC")->fetchAll();
    
    // Header untuk download file CSV yang kompatibel dengan Excel
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="TOKEN_VOTING_OSIS_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Tambahkan BOM agar Excel bisa baca karakter khusus/UTF-8 dengan benar
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header Kolom
    fputcsv($output, ['No', 'Kode Token', 'Status', 'Tanggal Dibuat']);
    
    $no = 1;
    foreach ($tokens as $t) {
        fputcsv($output, [
            $no++,
            $t['token_code'],
            strtoupper($t['status']),
            date('d/m/Y H:i:s', strtotime($t['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

// Jika tidak ada parameter export, redirect ke admin
header('Location: admin.php');
exit;
?>