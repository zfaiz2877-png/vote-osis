<?php
require 'config.php';
checkAdmin();

$tokens = $pdo->query(
    "SELECT token_code FROM tokens WHERE status = 'tersedia' ORDER BY id ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Token Pemilihan</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #172033; font-family: Arial, sans-serif; }
        .actions { display: flex; justify-content: center; gap: 12px; margin-bottom: 22px; }
        button { border: 0; border-radius: 8px; padding: 11px 18px; color: #fff; background: #FF4949; cursor: pointer; font-weight: 700; }
        button.secondary { color: #263044; background: #e8ebf1; }
        .tokens { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 9mm 7mm; }
        .token { min-height: 32mm; display: grid; place-items: center; border: 1.5px dashed #394150; border-radius: 3mm; text-align: center; break-inside: avoid; }
        .token small { display: block; color: #687386; font-size: 8pt; letter-spacing: .12em; }
        .token strong { display: block; margin-top: 3mm; font: 800 22pt/1 monospace; letter-spacing: .15em; }
        .empty { grid-column: 1 / -1; color: #667085; text-align: center; }
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { padding: 0; }
            .actions { display: none; }
            .tokens { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 7mm 5mm; }
            .token { min-height: 30mm; }
            .token:nth-child(24n + 1) { break-before: page; }
            .token:first-child { break-before: auto; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Cetak token (<?= count($tokens) ?>)</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>
    <main class="tokens">
        <?php if (!$tokens): ?>
            <p class="empty">Tidak ada token tersedia untuk dicetak.</p>
        <?php endif; ?>
        <?php foreach ($tokens as $token): ?>
            <div class="token">
                <div><small>OSKANER · E-VOTING OSIS</small><strong><?= sanitize($token['token_code']) ?></strong></div>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>
