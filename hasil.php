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
$chartColors = ['#60a5fa', '#c084fc', '#34d399', '#fb923c', '#f472b6', '#22d3ee'];
$maxVotes = 1;
foreach ($candidates as $candidate) {
    $maxVotes = max($maxVotes, (int)$candidate['total']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>Hasil Live — OSKANER</title>
    <style>
        :root { color-scheme: dark; --bg: #0f172a; --panel: #151f33; --muted: #94a3b8; --gold: #FFD700; --green: #34d399; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; color: #f8fafc; background: radial-gradient(ellipse at 50% -18%, #493c79 0, transparent 43%), var(--bg); font-family: "Segoe UI", system-ui, sans-serif; }
        .flash-screen { position: fixed; z-index: 20; inset: 0; pointer-events: none; opacity: 0; background: #a855f7; }
        .flash-screen.active { animation: screen-flash .85s ease-out; }
        @keyframes screen-flash { 0% { opacity: .34; } 100% { opacity: 0; } }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px clamp(16px, 4vw, 52px); border-bottom: 1px solid #ffffff12; background: #09111f88; backdrop-filter: blur(16px); }
        .brand { display: flex; align-items: center; gap: 13px; }
        .mark { width: 46px; height: 46px; display: grid; place-items: center; border: 1px solid #ffffff25; border-radius: 15px; background: #ffffff0c; font-size: 23px; }
        .brand h1 { margin: 0; color: #e9d5ff; font-size: 17px; letter-spacing: .04em; }
        .brand p { margin: 3px 0 0; color: var(--muted); font-size: 12px; }
        .live { display: flex; align-items: center; gap: 8px; color: #86efac; font-size: 11px; font-weight: 850; letter-spacing: .1em; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 13px #4ade80; animation: pulse 1.5s infinite; }
        @keyframes pulse { 50% { opacity: .4; } }
        .hero { padding: 38px 16px 26px; text-align: center; }
        .eyebrow { color: #c4b5fd; font-size: 11px; font-weight: 850; letter-spacing: .2em; text-transform: uppercase; }
        .hero h2 { margin: 8px 0 4px; font-size: clamp(27px, 5vw, 43px); letter-spacing: -.035em; }
        .hero p { margin: 0; color: var(--muted); }
        .total-card { display: inline-flex; align-items: center; gap: 12px; margin-top: 18px; padding: 11px 19px; border: 1px solid #ffffff1a; border-radius: 14px; background: #ffffff0a; backdrop-filter: blur(10px); }
        .total-label { color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-align: left; }
        .total-value { display: inline-block; min-width: 35px; color: #fff; font-size: 27px; font-weight: 900; font-variant-numeric: tabular-nums; }
        .layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(270px, .85fr); gap: 18px; width: min(100% - 32px, 1220px); margin: 0 auto 40px; }
        .panel { padding: clamp(16px, 2.4vw, 26px); border: 1px solid #ffffff12; border-radius: 22px; background: linear-gradient(145deg, #ffffff0c, #ffffff05); box-shadow: 0 25px 70px #00000019; backdrop-filter: blur(18px); }
        .panel-title { margin: 0 0 17px; color: #e2e8f0; font-size: 14px; font-weight: 850; letter-spacing: .05em; }
        .result-card { position: relative; display: grid; grid-template-columns: 36px 84px minmax(0, 1fr) auto; align-items: center; gap: 14px; min-height: 122px; margin-bottom: 11px; padding: 13px; overflow: hidden; border: 1px solid #ffffff14; border-left: 4px solid var(--candidate-color); border-radius: 17px; background: #ffffff09; opacity: 0; transform: translateY(42px); transition: border-color .35s, box-shadow .35s, background .35s; }
        .result-card:last-child { margin-bottom: 0; }
        .result-card.revealed { animation: rise-in .65s cubic-bezier(.2,.8,.2,1) forwards; }
        @keyframes rise-in { to { opacity: 1; transform: translateY(0); } }
        .result-card.winner { border-color: #ffd7008c; border-left-color: var(--gold); background: linear-gradient(110deg, #ffd70018, #ffffff08 70%); box-shadow: 0 0 25px #ffd70014, inset 0 0 0 1px #ffd7003c; }
        .rank { color: #cbd5e1; text-align: center; font-size: 21px; font-weight: 900; }
        .photo { width: 84px; height: 84px; border: 2px solid var(--candidate-color); border-radius: 14px; object-fit: cover; background: #243047; }
        .photo-empty { display: grid; place-items: center; color: #94a3b8; font-size: 24px; }
        .candidate-info { min-width: 0; }
        .candidate-name-row { display: flex; align-items: center; gap: 8px; }
        .candidate-name { overflow-wrap: anywhere; color: #f8fafc; font-size: 17px; font-weight: 850; }
        .crown { display: inline-block; opacity: 0; transform: translateY(8px) scale(.7); }
        .winner.revealed .crown { animation: crown-appear .75s cubic-bezier(.2,1.4,.5,1) forwards; }
        @keyframes crown-appear { to { opacity: 1; transform: translateY(0) scale(1); } }
        .candidate-number { margin-top: 3px; color: var(--muted); font-size: 11px; }
        .progress-track { height: 8px; margin-top: 12px; overflow: hidden; border-radius: 99px; background: #ffffff14; }
        .progress-bar { width: var(--pct); height: 100%; border-radius: inherit; background: var(--candidate-color); transition: width .9s cubic-bezier(.2,.8,.2,1); }
        .vote-stats { min-width: 62px; text-align: right; }
        .vote-count { display: inline-block; color: var(--candidate-color); font-size: 27px; font-weight: 900; font-variant-numeric: tabular-nums; transition: color .2s; }
        .percentage { display: block; margin-top: 1px; color: var(--muted); font-size: 12px; font-weight: 750; }
        .flash-number { animation: number-flash .9s ease; }
        @keyframes number-flash { 0%, 100% { background: transparent; } 35% { color: #bbf7d0; text-shadow: 0 0 22px #4ade80; } }
        .chart { display: flex; min-height: 315px; align-items: flex-end; gap: 12px; margin: 25px 0 0; padding: 22px 4px 0; border-bottom: 1px solid #ffffff24; background: repeating-linear-gradient(to bottom, transparent 0, transparent calc(25% - 1px), #ffffff0b calc(25% - 1px), #ffffff0b 25%); }
        .chart-column { display: flex; height: 100%; min-width: 35px; flex: 1; flex-direction: column; align-items: center; justify-content: flex-end; gap: 7px; }
        .chart-count { color: #dbeafe; font-size: 11px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .chart-bar { width: min(100%, 54px); height: var(--height); min-height: 0; border-radius: 9px 9px 3px 3px; background: var(--candidate-color); box-shadow: 0 0 18px color-mix(in srgb, var(--candidate-color) 35%, transparent); transition: height .9s cubic-bezier(.2,.8,.2,1); }
        .chart-label { width: 100%; overflow: hidden; color: #cbd5e1; font-size: 10px; font-weight: 750; text-align: center; text-overflow: ellipsis; white-space: nowrap; }
        .empty { padding: 35px 15px; color: var(--muted); text-align: center; }
        .status { min-height: 20px; margin: 17px 0 0; color: var(--muted); font-size: 11px; text-align: center; }
        .status.error { color: #fda4af; }
        .admin-link { display: block; width: fit-content; margin: 0 auto 30px; color: #a5b4fc; font-size: 12px; text-decoration: none; }
        @media (max-width: 850px) { .layout { grid-template-columns: 1fr; } .chart { min-height: 250px; } }
        @media (max-width: 540px) { .topbar { padding: 15px; } .brand h1 { font-size: 14px; } .brand p { font-size: 10px; } .result-card { grid-template-columns: 25px 62px minmax(0, 1fr) auto; gap: 8px; min-height: 95px; padding: 9px; } .photo { width: 62px; height: 62px; border-radius: 11px; } .candidate-name { font-size: 14px; } .vote-count { font-size: 22px; } .percentage { font-size: 10px; } .vote-stats { min-width: 48px; } .rank { font-size: 16px; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <div class="flash-screen" id="flash-screen" aria-hidden="true"></div>
    <header class="topbar">
        <div class="brand"><div class="mark" aria-hidden="true">✦</div><div><h1>OSKANER · HASIL LIVE</h1><p>Pemilihan Ketua OSIS · SMKN 6 Jember</p></div></div>
        <div class="live"><span class="live-dot"></span> LANGSUNG</div>
    </header>
    <section class="hero">
        <div class="eyebrow">Hasil sementara pemilihan</div>
        <h2>Suara Anda, masa depan kita.</h2>
        <p>Perolehan suara diperbarui otomatis setiap 3 detik.</p>
        <div class="total-card"><span class="total-label">TOTAL<br>SUARA</span><strong class="total-value" id="total-votes"><?= number_format($totalVotes) ?></strong></div>
    </section>
    <main class="layout">
        <section class="panel">
            <h3 class="panel-title">🏆 PERINGKAT KANDIDAT</h3>
            <div id="results-list">
                <?php if (!$candidates): ?><div class="empty">Belum ada kandidat terdaftar.</div><?php endif; ?>
                <?php foreach ($candidates as $index => $candidate): ?>
                    <?php
                    $id = (int)$candidate['id'];
                    $total = (int)$candidate['total'];
                    $percentage = $totalVotes > 0 ? round(($total / $totalVotes) * 100, 1) : 0;
                    $color = $chartColors[$index % count($chartColors)];
                    $photo = !empty($candidate['photo_url']) && $candidate['photo_url'] !== 'default.jpg'
                        ? 'upload/' . rawurlencode(basename($candidate['photo_url']))
                        : '';
                    ?>
                    <article class="result-card <?= $index === 0 ? 'winner' : '' ?>" data-id="<?= $id ?>" style="--candidate-color: <?= $color ?>; --pct: <?= $percentage ?>%">
                        <div class="rank"><?= $index + 1 ?></div>
                        <?php if ($photo !== ''): ?><img class="photo" src="<?= sanitize($photo) ?>" alt="Foto <?= sanitize($candidate['name']) ?>"><?php else: ?><div class="photo photo-empty" aria-label="Foto belum tersedia">◎</div><?php endif; ?>
                        <div class="candidate-info">
                            <div class="candidate-name-row"><span class="candidate-name"><?= sanitize($candidate['name']) ?></span><span class="crown" aria-hidden="true">👑</span></div>
                            <div class="candidate-number">Nomor urut <?= (int)$candidate['no_urut'] ?></div>
                            <div class="progress-track" role="progressbar" aria-label="Persentase suara <?= sanitize($candidate['name']) ?>" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar"></div></div>
                        </div>
                        <div class="vote-stats"><span class="vote-count"><?= number_format($total) ?></span><span class="percentage"><?= number_format($percentage, 1) ?>%</span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel">
            <h3 class="panel-title">📊 PERBANDINGAN SUARA</h3>
            <div class="chart" id="vote-chart">
                <?php foreach ($candidates as $index => $candidate): ?>
                    <?php
                    $total = (int)$candidate['total'];
                    $height = $maxVotes > 0 ? round(($total / $maxVotes) * 100, 1) : 0;
                    $color = $chartColors[$index % count($chartColors)];
                    ?>
                    <div class="chart-column" data-id="<?= (int)$candidate['id'] ?>" style="--candidate-color: <?= $color ?>">
                        <span class="chart-count"><?= $total ?></span>
                        <div class="chart-bar" style="--height: <?= $height ?>%"></div>
                        <span class="chart-label" title="<?= sanitize($candidate['name']) ?>"><?= sanitize($candidate['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <p class="status" id="update-status" role="status" aria-live="polite"></p>
    <a class="admin-link" href="admin.php">Dashboard panitia</a>
    <script>
        const resultsList = document.getElementById('results-list');
        const chart = document.getElementById('vote-chart');
        const totalElement = document.getElementById('total-votes');
        const updateStatus = document.getElementById('update-status');
        const flashScreen = document.getElementById('flash-screen');
        let previousTotalVotes = <?= $totalVotes ?>;
        const resultCards = [...resultsList.querySelectorAll('.result-card')];

        for (let index = resultCards.length - 1; index >= 0; index--) {
            const card = resultCards[index];
            const delay = (resultCards.length - 1 - index) * 1500;
            window.setTimeout(() => {
                card.classList.add('revealed');
                if (index === 0) {
                    card.classList.add('winner');
                    flashScreen.classList.add('active');
                    window.setTimeout(() => flashScreen.classList.remove('active'), 900);
                }
            }, delay);
        }
        function flashNumber(element) {
            element.classList.remove('flash-number');
            void element.offsetWidth;
            element.classList.add('flash-number');
        }

        function updateResults(payload) {
            if (!payload || !Number.isInteger(payload.total_votes) || !Array.isArray(payload.candidates)) {
                throw new Error('Format data hasil tidak valid.');
            }
            const cardsById = new Map([...resultsList.querySelectorAll('.result-card')].map((card) => [Number(card.dataset.id), card]));
            const columnsById = new Map([...chart.querySelectorAll('.chart-column')].map((column) => [Number(column.dataset.id), column]));
            if (payload.candidates.length !== cardsById.size || payload.candidates.some((candidate) => !cardsById.has(candidate.id))) {
                updateStatus.textContent = 'Daftar kandidat berubah. Muat ulang halaman hasil untuk menyegarkan daftar.';
                updateStatus.classList.add('error');
                return;
            }

            const maximum = Math.max(1, ...payload.candidates.map((candidate) => candidate.total));
            payload.candidates.forEach((candidate, index) => {
                const card = cardsById.get(candidate.id);
                const column = columnsById.get(candidate.id);
                if (!card || !column) {
                    throw new Error('Data kandidat hasil tidak lengkap.');
                }
                const count = card.querySelector('.vote-count');
                const percentage = card.querySelector('.percentage');
                const progress = card.querySelector('.progress-track');
                const chartCount = column.querySelector('.chart-count');
                const chartBar = column.querySelector('.chart-bar');
                const pct = Number(candidate.pct);

                if (!Number.isFinite(pct) || !Number.isInteger(candidate.total)) {
                    throw new Error('Nilai suara hasil tidak valid.');
                }
                if (Number(count.textContent.replace(/[^\d]/g, '')) !== candidate.total) {
                    count.textContent = candidate.total.toLocaleString('id-ID');
                    flashNumber(count);
                }
                percentage.textContent = `${pct.toFixed(1)}%`;
                progress.setAttribute('aria-valuenow', pct.toFixed(1));
                card.style.setProperty('--pct', `${pct}%`);
                chartCount.textContent = candidate.total.toLocaleString('id-ID');
                chartBar.style.setProperty('--height', `${(candidate.total / maximum) * 100}%`);
                card.querySelector('.rank').textContent = String(index + 1);
                resultsList.appendChild(card);
                chart.appendChild(column);
                card.classList.toggle('winner', index === 0);
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
                const response = await fetch('api_hasil.php', {cache: 'no-store', headers: {'Accept': 'application/json'}});
                if (!response.ok) {
                    throw new Error(`Server merespons ${response.status}.`);
                }
                updateResults(await response.json());
            } catch (error) {
                console.error('Pembaruan hasil gagal:', error);
                updateStatus.textContent = 'Pembaruan gagal. Memeriksa kembali otomatis.';
                updateStatus.classList.add('error');
            }
        }
        window.setInterval(refreshResults, 3000);
    </script>
</body>
</html>
