<?php
require 'config.php';

$totalVotes = (int)$pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
$statement = $pdo->query(
    'SELECT c.id, c.no_urut, c.name, c.photo_url, COUNT(v.id) AS total
     FROM candidates c
     LEFT JOIN votes v ON v.candidate_id = c.id
     GROUP BY c.id
     ORDER BY total DESC, c.no_urut ASC'
);
$candidates = $statement->fetchAll();
$highestVotes = $candidates ? (int)$candidates[0]['total'] : 0;
$barGradients = [
    'linear-gradient(0deg, #2563eb, #60a5fa)',
    'linear-gradient(0deg, #7c3aed, #c084fc)',
    'linear-gradient(0deg, #047857, #34d399)',
    'linear-gradient(0deg, #c2410c, #fb923c)',
    'linear-gradient(0deg, #be185d, #f472b6)',
    'linear-gradient(0deg, #0e7490, #22d3ee)',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>Hasil Live — OSKANER</title>
    <style>
        :root { color-scheme: dark; --bg: #0f172a; --panel: #151f33; --muted: #94a3b8; --gold: #FFD700; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; color: #f8fafc; background: radial-gradient(ellipse at 50% -18%, #493c79 0, transparent 43%), var(--bg); font-family: system-ui, sans-serif; }
        .topbar { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; padding: 17px clamp(16px, 4vw, 52px); border-bottom: 1px solid #ffffff12; background: #09111fbb; }
        .logo-box { display: grid; justify-items: start; gap: 4px; min-width: 64px; color: var(--muted); font-size: 10px; font-weight: 750; }
        .logo-box.logo-right { justify-items: end; }
        .logo-smk, .logo-osis { width: 52px; height: 52px; object-fit: contain; }
        .logo-fallback { display: none; width: 52px; height: 52px; place-items: center; border: 1px solid #ffffff22; border-radius: 14px; color: #e9d5ff; background: #ffffff0a; font-size: 12px; font-weight: 900; }
        .brand { text-align: center; }
        .brand h1 { margin: 0; color: #e9d5ff; font-size: 17px; letter-spacing: .04em; }
        .brand p { margin: 3px 0 0; color: var(--muted); font-size: 12px; }
        .live { display: flex; align-items: center; justify-self: end; gap: 8px; color: #86efac; font-size: 11px; font-weight: 850; letter-spacing: .1em; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 13px #4ade80; animation: pulse 1.5s infinite; }
        @keyframes pulse { 50% { opacity: .4; } }
        .hero { padding: 38px 16px 26px; text-align: center; }
        .eyebrow { color: #c4b5fd; font-size: 11px; font-weight: 850; letter-spacing: .2em; text-transform: uppercase; }
        .hero h2 { margin: 8px 0 4px; font-size: clamp(27px, 5vw, 43px); letter-spacing: -.035em; }
        .hero p { margin: 0; color: var(--muted); }
        .total-card { display: inline-flex; align-items: center; gap: 12px; margin-top: 18px; padding: 11px 19px; border: 1px solid #ffffff1a; border-radius: 14px; background: #ffffff0a; }
        .total-label { color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-align: left; }
        .total-value { display: inline-block; min-width: 35px; color: #fff; font-size: 27px; font-weight: 900; font-variant-numeric: tabular-nums; }
        .panel { width: min(100% - 32px, 1120px); margin: 0 auto 36px; padding: clamp(17px, 3vw, 30px); border: 1px solid #ffffff12; border-radius: 22px; background: linear-gradient(145deg, #ffffff0c, #ffffff05); box-shadow: 0 25px 70px #00000019; }
        .panel-title { margin: 0 0 22px; color: #e2e8f0; font-size: 14px; font-weight: 850; letter-spacing: .05em; }
        .chart-scroll { overflow-x: auto; padding: 4px 2px 10px; }
        .bar-chart { display: grid; grid-template-columns: repeat(<?= max(1, count($candidates)) ?>, minmax(125px, 1fr)); gap: clamp(10px, 2vw, 22px); min-width: max(100%, <?= max(1, count($candidates)) * 125 ?>px); }
        .bar-column { display: grid; grid-template-rows: 43px minmax(180px, 300px) 66px; gap: 10px; min-width: 0; }
        .bar-values { align-self: end; min-width: 0; padding-bottom: 2px; text-align: center; font-variant-numeric: tabular-nums; }
        .vote-count { display: inline-block; color: #f8fafc; font-size: 20px; font-weight: 900; }
        .percentage { display: block; margin-top: 1px; color: var(--muted); font-size: 11px; font-weight: 750; }
        .bar-track { display: flex; width: 100%; height: 100%; align-items: flex-end; justify-content: center; overflow: hidden; border: 1px solid #ffffff12; border-radius: 12px 12px 4px 4px; background: repeating-linear-gradient(to top, #ffffff09 0, #ffffff09 1px, transparent 1px, transparent 25%); }
        .bar-fill { width: min(72%, 82px); height: var(--bar-height, 0%); min-height: 0; border: 1px solid #ffffff18; border-bottom: 0; border-radius: 10px 10px 0 0; background: var(--bar-gradient); transition: height .5s ease, background .35s ease, box-shadow .35s ease; }
        .bar-column.winner .bar-fill { background: linear-gradient(0deg, #a87900, #FFD700 65%, #fff0a0); box-shadow: 0 0 24px #ffd70057; }
        .candidate-profile { display: flex; align-items: center; gap: 9px; min-width: 0; padding: 3px; }
        .candidate-photo { width: 44px; height: 44px; flex: 0 0 44px; border: 1px solid #ffffff24; border-radius: 12px; object-fit: cover; background: #243047; }
        .photo-fallback { display: grid; place-items: center; color: #cbd5e1; font-size: 17px; font-weight: 800; }
        .candidate-label { min-width: 0; }
        .candidate-name { display: block; overflow: hidden; color: #e2e8f0; font-size: 12px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .winner .candidate-name { color: #fff2b3; }
        .candidate-number { display: block; margin-top: 4px; color: var(--muted); font-size: 10px; font-weight: 650; }
        .crown { margin-left: 3px; opacity: 0; transform: translateY(3px) scale(.8); transition: opacity .3s ease, transform .3s ease; }
        .winner .crown { opacity: 1; transform: translateY(0) scale(1); }
        .flash-number { animation: number-flash .85s ease; }
        @keyframes number-flash { 35% { color: #bbf7d0; text-shadow: 0 0 18px #4ade80; } }
        .empty { grid-column: 1 / -1; padding: 32px 15px; color: var(--muted); text-align: center; }
        .status { min-height: 20px; margin: 16px 0 25px; color: var(--muted); font-size: 11px; text-align: center; }
        .status.error { color: #fda4af; }
        .admin-link { display: block; width: fit-content; margin: 0 auto 30px; color: #a5b4fc; font-size: 12px; text-decoration: none; }
        @media (max-width: 650px) {
            .topbar { gap: 8px; padding: 12px; }
            .logo-smk, .logo-osis, .logo-fallback { width: 40px; height: 40px; }
            .logo-box { min-width: 44px; font-size: 9px; }
            .brand h1 { font-size: 13px; }
            .brand p { font-size: 9px; }
            .live { gap: 5px; font-size: 9px; }
            .bar-chart { grid-template-columns: repeat(<?= max(1, count($candidates)) ?>, minmax(112px, 1fr)); min-width: max(100%, <?= max(1, count($candidates)) * 112 ?>px); gap: 9px; }
            .bar-column { grid-template-rows: 39px minmax(165px, 250px) 62px; gap: 8px; }
            .candidate-profile { gap: 6px; }
            .candidate-photo { width: 38px; height: 38px; flex-basis: 38px; border-radius: 10px; }
            .candidate-name { font-size: 11px; }
            .vote-count { font-size: 17px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="logo-box">
            <img class="logo-smk" src="assets/logo-smk.png" alt="Logo SMK" onerror="this.hidden=true;this.nextElementSibling.style.display='grid'">
            <span class="logo-fallback" aria-hidden="true">SMK</span>
        </div>
        <div class="brand"><h1>OSKANER · HASIL LIVE</h1><p>Pemilihan Ketua OSIS · SMKN 6 Jember</p></div>
        <div class="logo-box logo-right">
            <img class="logo-osis" src="assets/logo-osis.png" alt="Logo OSIS" onerror="this.hidden=true;this.nextElementSibling.style.display='grid'">
            <span class="logo-fallback" aria-hidden="true">OSIS</span>
        </div>
    </header>
    <section class="hero">
        <div class="eyebrow">Hasil sementara pemilihan</div>
        <h2>Suara Anda, masa depan kita.</h2>
        <p>Perolehan suara diperbarui otomatis setiap 3 detik.</p>
        <div class="total-card"><span class="total-label">TOTAL<br>SUARA</span><strong class="total-value" id="total-votes"><?= number_format($totalVotes) ?></strong></div>
    </section>
    <main class="panel">
        <h3 class="panel-title">📊 PEROLEHAN SUARA KANDIDAT</h3>
        <div class="chart-scroll">
        <div class="bar-chart" id="bar-chart">
            <?php if (!$candidates): ?>
                <div class="empty" id="empty-state">Belum ada kandidat terdaftar.</div>
            <?php endif; ?>
            <?php foreach ($candidates as $index => $candidate): ?>
                <?php
                $total = (int)$candidate['total'];
                $percentage = $totalVotes > 0 ? round(($total / $totalVotes) * 100, 1) : 0;
                $height = $highestVotes > 0 ? round(($total / $highestVotes) * 100, 1) : 0;
                $gradient = $barGradients[$index % count($barGradients)];
                $photo = !empty($candidate['photo_url']) && $candidate['photo_url'] !== 'default.jpg'
                    ? 'upload/' . rawurlencode(basename($candidate['photo_url']))
                    : '';
                $isWinner = $highestVotes > 0 && $index === 0;
                ?>
                <article class="bar-column<?= $isWinner ? ' winner' : '' ?>" data-id="<?= (int)$candidate['id'] ?>">
                <div class="bar-values">
                    <span class="vote-count"><?= number_format($total) ?></span>
                    <span class="percentage"><?= number_format($percentage, 1) ?>%</span>
                </div>
                <div class="bar-track" role="progressbar"
                     aria-label="Jumlah suara <?= sanitize($candidate['name']) ?>"
                     aria-valuenow="<?= $total ?>" aria-valuemin="0">
                    <div class="bar-fill" style="--bar-gradient: <?= $gradient ?>; --bar-height: <?= $height ?>%"></div>
                    </div>
                <div class="candidate-profile">
                    <?php if ($photo !== ''): ?>
                        <img class="candidate-photo" src="<?= sanitize($photo) ?>" alt="Foto <?= sanitize($candidate['name']) ?>" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                        <span class="candidate-photo photo-fallback" aria-hidden="true" hidden>◎</span>
                    <?php else: ?>
                        <span class="candidate-photo photo-fallback" aria-label="Foto belum tersedia">◎</span>
                    <?php endif; ?>
                    <div class="candidate-label">
                        <span class="candidate-name"><span class="name-text"><?= sanitize($candidate['name']) ?></span><span class="crown" aria-label="Suara tertinggi">👑</span></span>
                        <span class="candidate-number">Nomor urut <?= (int)$candidate['no_urut'] ?></span>
                    </div>
                </div>
                </article>
            <?php endforeach; ?>
        </div>
        </div>
    </main>
    <p class="status" id="update-status" role="status" aria-live="polite"></p>
    <a class="admin-link" href="admin.php">Dashboard panitia</a>
    <script>
        const barChart = document.getElementById('bar-chart');
        const totalElement = document.getElementById('total-votes');
        const updateStatus = document.getElementById('update-status');
        let previousTotalVotes = <?= $totalVotes ?>;

        function flashNumber(element) {
            element.classList.remove('flash-number');
            void element.offsetWidth;
            element.classList.add('flash-number');
        }

        function updateResults(payload) {
            if (!payload || !Number.isInteger(payload.total_votes) || !Array.isArray(payload.candidates)) {
                throw new Error('Format data hasil tidak valid.');
            }

            const rows = new Map(
                [...barChart.querySelectorAll('.bar-column')].map((column) => [Number(column.dataset.id), column])
            );
            if (payload.candidates.length !== rows.size || payload.candidates.some((candidate) => !rows.has(candidate.id))) {
                updateStatus.textContent = 'Daftar kandidat berubah. Muat ulang halaman untuk melihat daftar terbaru.';
                updateStatus.classList.add('error');
                return;
            }

            const maximumVotes = Math.max(0, ...payload.candidates.map((candidate) => candidate.total));
            payload.candidates.forEach((candidate, index) => {
                if (!Number.isInteger(candidate.id) || !Number.isInteger(candidate.total) ||
                    !Number.isInteger(candidate.no_urut) || typeof candidate.name !== 'string' ||
                    !Number.isFinite(candidate.pct) || candidate.pct < 0 || candidate.pct > 100) {
                    throw new Error('Nilai hasil kandidat tidak valid.');
                }

                const column = rows.get(candidate.id);
                const count = column.querySelector('.vote-count');
                const name = column.querySelector('.name-text');
                const number = column.querySelector('.candidate-number');
                const track = column.querySelector('.bar-track');
                const fill = column.querySelector('.bar-fill');
                const percentage = column.querySelector('.percentage');
                const height = maximumVotes > 0 ? (candidate.total / maximumVotes) * 100 : 0;
                const oldCount = Number(count.textContent.replace(/[^\d]/g, ''));

                if (oldCount !== candidate.total) {
                    count.textContent = candidate.total.toLocaleString('id-ID');
                    flashNumber(count);
                }
                if (name.textContent !== candidate.name) {
                    name.textContent = candidate.name;
                }
                number.textContent = `Nomor urut ${candidate.no_urut}`;
                percentage.textContent = `${candidate.pct.toFixed(1)}%`;
                track.setAttribute('aria-valuenow', candidate.pct.toFixed(1));
                fill.style.setProperty('--bar-height', `${height}%`);
                track.setAttribute('aria-valuenow', String(candidate.total));
                column.classList.toggle('winner', payload.total_votes > 0 && index === 0);
            });

            if (payload.total_votes !== previousTotalVotes) {
                totalElement.textContent = payload.total_votes.toLocaleString('id-ID');
                flashNumber(totalElement);
                previousTotalVotes = payload.total_votes;
            }
            updateStatus.textContent = `Diperbarui ${new Date().toLocaleTimeString('id-ID')}`;
            updateStatus.classList.remove('error');
        }

        async function refreshResults() {
            try {
                const response = await fetch('api_hasil.php', {
                    cache: 'no-store',
                    headers: {'Accept': 'application/json'}
                });
                if (!response.ok) {
                    throw new Error(`Server merespons ${response.status}.`);
                }
                updateResults(await response.json());
            } catch (error) {
                console.error('Pembaruan hasil gagal:', error);
                updateStatus.textContent = 'Pembaruan gagal. Sistem akan mencoba kembali otomatis.';
                updateStatus.classList.add('error');
            }
        }

        window.setInterval(refreshResults, 3000);
    </script>
</body>
</html>
