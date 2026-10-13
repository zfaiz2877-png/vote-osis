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
    'linear-gradient(90deg, #2563eb, #60a5fa)',
    'linear-gradient(90deg, #7c3aed, #c084fc)',
    'linear-gradient(90deg, #047857, #34d399)',
    'linear-gradient(90deg, #c2410c, #fb923c)',
    'linear-gradient(90deg, #be185d, #f472b6)',
    'linear-gradient(90deg, #0e7490, #22d3ee)',
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
        :root { color-scheme: dark; --bg: #0f172a; --muted: #94a3b8; --gold: #FFD700; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; color: #f8fafc; background: radial-gradient(ellipse at 50% -18%, #493c79 0, transparent 43%), var(--bg); font-family: system-ui, sans-serif; }
        .topbar { height: 76px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; padding: 8px clamp(18px, 3vw, 48px); border-bottom: 1px solid #ffffff12; background: #09111fbb; }
        .logo-box { display: grid; justify-items: start; gap: 2px; min-width: 58px; color: var(--muted); font-size: 9px; font-weight: 750; }
        .logo-box.logo-right { justify-items: end; }
        .logo-smk, .logo-osis { width: 48px; height: 48px; object-fit: contain; }
        .logo-fallback { display: none; width: 48px; height: 48px; place-items: center; border: 1px solid #ffffff22; border-radius: 13px; color: #e9d5ff; background: #ffffff0a; font-size: 11px; font-weight: 900; }
        .brand { text-align: center; }
        .brand h1 { margin: 0; color: #e9d5ff; font-size: clamp(14px, 1.6vw, 20px); letter-spacing: .04em; }
        .brand p { margin: 3px 0 0; color: var(--muted); font-size: 11px; }
        .live { display: flex; align-items: center; justify-self: end; gap: 8px; color: #86efac; font-size: 10px; font-weight: 850; letter-spacing: .1em; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 13px #4ade80; animation: pulse 1.5s infinite; }
        @keyframes pulse { 50% { opacity: .4; } }
        .hero { height: 142px; display: grid; align-content: center; justify-items: center; gap: 4px; padding: 8px 14px; text-align: center; }
        .eyebrow { color: #c4b5fd; font-size: 10px; font-weight: 850; letter-spacing: .2em; text-transform: uppercase; }
        .hero h2 { margin: 2px 0; font-size: clamp(23px, 3vw, 34px); letter-spacing: -.035em; }
        .hero p { margin: 0; color: var(--muted); font-size: 12px; }
        .total-card { display: flex; align-items: center; gap: 9px; margin-top: 7px; padding: 5px 10px; border: 1px solid #ffffff1a; border-radius: 12px; background: #ffffff0a; }
        .total-label { color: var(--muted); font-size: 9px; font-weight: 800; letter-spacing: .08em; text-align: left; }
        .total-value { display: inline-block; min-width: 25px; color: #fff; font-size: 21px; font-weight: 900; font-variant-numeric: tabular-nums; }
        .results-layout { height: calc(100svh - 244px); min-height: 390px; display: grid; grid-template-columns: minmax(360px, .92fr) minmax(440px, 1.25fr); gap: 16px; width: min(100% - 36px, 1600px); margin: 0 auto; }
        .panel { min-width: 0; min-height: 0; display: flex; flex-direction: column; padding: clamp(16px, 2vw, 26px); overflow: hidden; border: 1px solid #ffffff12; border-radius: 20px; background: linear-gradient(145deg, #ffffff0c, #ffffff05); box-shadow: 0 20px 60px #00000019; }
        .panel-title { flex: 0 0 auto; margin: 0 0 14px; color: #e2e8f0; font-size: clamp(12px, 1.15vw, 16px); font-weight: 850; letter-spacing: .05em; }
        .ranking-list { flex: 1; min-height: 0; display: grid; grid-template-rows: repeat(<?= max(1, count($candidates)) ?>, minmax(0, 1fr)); gap: clamp(8px, 1vh, 13px); }
        .ranking-card { min-height: 0; display: grid; grid-template-columns: minmax(66px, auto) clamp(96px, 13vh, 144px) minmax(0, 1fr); align-items: center; gap: clamp(9px, 1.2vw, 16px); padding: clamp(9px, 1.25vh, 15px); border: 1px solid #ffffff14; border-radius: 17px; background: #ffffff08; transition: border-color .35s, background .35s, box-shadow .35s, transform .55s cubic-bezier(.2,.8,.2,1); }
        .ranking-card.winner { border-color: #ffd70096; background: linear-gradient(100deg, #ffd70018, #ffffff08 70%); box-shadow: 0 0 23px #ffd70012, inset 0 0 0 1px #ffd70028; }
        .candidate-photo { width: clamp(96px, 13vh, 144px); height: clamp(96px, 13vh, 144px); border: 2px solid #ffffff24; border-radius: 14px; object-fit: cover; background: #243047; }
        .photo-fallback { display: grid; place-items: center; color: #cbd5e1; font-size: 27px; font-weight: 800; }
        .candidate-info { min-width: 0; }
        .candidate-name { display: block; overflow: hidden; color: #e2e8f0; font-size: clamp(15px, 1.55vw, 22px); font-weight: 850; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
        .winner .candidate-name { color: #fff2b3; }
        .candidate-number { display: block; margin-top: 6px; color: var(--muted); font-size: clamp(10px, .9vw, 13px); font-weight: 650; }
        .rank-stats { min-width: 0; text-align: left; font-variant-numeric: tabular-nums; }
        .rank-count { color: #f8fafc; font-size: clamp(17px, 1.6vw, 23px); font-weight: 900; }
        .rank-pct { display: block; margin-top: 4px; color: var(--muted); font-size: 11px; }
        .chart-area { position: relative; flex: 1; min-height: 0; display: flex; align-items: stretch; padding: 5px 4px 0; }
        .chart-gridlines { position: absolute; inset: 12px 4px 42px; pointer-events: none; background: repeating-linear-gradient(to top, transparent 0, transparent calc(25% - 1px), #ffffff12 calc(25% - 1px), #ffffff12 25%); }
        .chart-columns { position: relative; z-index: 1; flex: 1; min-width: 0; display: grid; grid-template-columns: repeat(<?= max(1, count($candidates)) ?>, minmax(0, 1fr)); gap: clamp(12px, 2.3vw, 30px); border-bottom: 1px solid #ffffff45; }
        .chart-column { min-width: 0; display: grid; grid-template-rows: 34px minmax(0, 1fr) 38px; justify-items: center; gap: 8px; transition: transform .55s cubic-bezier(.2,.8,.2,1); }
        .bar-values { align-self: end; text-align: center; font-variant-numeric: tabular-nums; }
        .vote-count { display: inline-block; color: #f8fafc; font-size: clamp(16px, 1.7vw, 24px); font-weight: 900; }
        .percentage { display: block; margin-top: 1px; color: var(--muted); font-size: clamp(10px, .9vw, 12px); font-weight: 750; }
        .bar-track { width: min(74%, 112px); height: 100%; display: flex; align-items: flex-end; justify-content: center; overflow: hidden; border-right: 1px solid #ffffff0a; border-left: 1px solid #ffffff0a; background: #ffffff05; }
        .bar-fill { width: 100%; height: var(--bar-height, 0%); min-height: 0; border: 1px solid #ffffff21; border-bottom: 0; border-radius: 12px 12px 0 0; background: var(--bar-gradient); transition: height .7s cubic-bezier(.2,.8,.2,1), background .35s ease, box-shadow .35s ease; }
        .chart-column.winner .bar-fill { background: linear-gradient(0deg, #a87900, #FFD700 65%, #fff0a0); box-shadow: 0 0 25px #ffd70057; }
        .chart-label { width: 100%; align-self: center; overflow: hidden; color: #dbe4f0; font-size: clamp(10px, .95vw, 13px); font-weight: 750; text-align: center; text-overflow: ellipsis; white-space: nowrap; }
        .chart-column.winner .chart-label { color: #fff2b3; }
        .flash-number { animation: number-flash .85s ease; }
        @keyframes number-flash { 35% { color: #bbf7d0; text-shadow: 0 0 18px #4ade80; } }
        .empty { grid-column: 1 / -1; align-self: center; padding: 32px 15px; color: var(--muted); text-align: center; }
        .status { min-height: 18px; margin: 5px 0 4px; color: var(--muted); font-size: 10px; text-align: center; }
        .status.error { color: #fda4af; }
        .admin-link { position: fixed; right: 10px; bottom: 6px; color: #a5b4fc66; font-size: 9px; text-decoration: none; }
        @media (max-width: 900px) {
            body { min-height: 100vh; overflow-y: auto; }
            .topbar { height: 68px; }
            .hero { height: auto; min-height: 142px; padding: 17px 14px 18px; }
            .total-card { width: fit-content; margin: 8px auto 0; }
            .results-layout { height: auto; min-height: 0; grid-template-columns: minmax(0, 1fr); }
            .panel { min-height: 290px; }
            .ranking-list { grid-template-rows: repeat(<?= max(1, count($candidates)) ?>, minmax(104px, auto)); }
            .chart-columns { min-height: 270px; }
        }
        @media (max-width: 520px) {
            .topbar { gap: 8px; padding: 8px 12px; }
            .logo-smk, .logo-osis, .logo-fallback { width: 38px; height: 38px; }
            .logo-box { min-width: 40px; }
            .brand h1 { font-size: 12px; }
            .brand p { font-size: 9px; }
            .live { gap: 5px; font-size: 8px; }
            .results-layout { width: min(100% - 20px, 600px); gap: 12px; }
            .panel { padding: 14px; }
            .ranking-card { grid-template-columns: 62px 76px minmax(0, 1fr); gap: 8px; padding: 9px; }
            .candidate-photo { width: 76px; height: 76px; }
            .candidate-name { font-size: 14px; }
            .candidate-number { margin-top: 4px; font-size: 10px; }
            .rank-count { font-size: 15px; }
            .rank-pct { font-size: 9px; }
            .chart-columns { gap: 7px; }
            .chart-column { grid-template-rows: 32px minmax(0, 1fr) 34px; gap: 5px; }
            .bar-track { width: 82%; }
            .vote-count { font-size: 15px; }
            .percentage { font-size: 10px; }
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

    <main class="results-layout">
        <section class="panel">
            <h3 class="panel-title">PERINGKAT KANDIDAT</h3>
            <div class="ranking-list" id="ranking-list">
                <?php if (!$candidates): ?><div class="empty">Belum ada kandidat terdaftar.</div><?php endif; ?>
                <?php foreach ($candidates as $index => $candidate): ?>
                    <?php
                    $total = (int)$candidate['total'];
                    $percentage = $totalVotes > 0 ? round(($total / $totalVotes) * 100, 1) : 0;
                    $photo = !empty($candidate['photo_url']) && $candidate['photo_url'] !== 'default.jpg'
                        ? 'upload/' . rawurlencode(basename($candidate['photo_url']))
                        : '';
                    ?>
                    <article class="ranking-card<?= $highestVotes > 0 && $index === 0 ? ' winner' : '' ?>" data-id="<?= (int)$candidate['id'] ?>" aria-label="Peringkat <?= $index + 1 ?>, <?= sanitize($candidate['name']) ?>, <?= number_format($total) ?> suara">
                        <div class="rank-stats"><span class="rank-count"><?= number_format($total) ?></span><span class="rank-pct"><?= number_format($percentage, 1) ?>%</span></div>
                        <?php if ($photo !== ''): ?>
                            <img class="candidate-photo" src="<?= sanitize($photo) ?>" alt="Foto <?= sanitize($candidate['name']) ?>" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                            <span class="candidate-photo photo-fallback" aria-hidden="true" hidden>◎</span>
                        <?php else: ?>
                            <span class="candidate-photo photo-fallback" aria-label="Foto belum tersedia">◎</span>
                        <?php endif; ?>
                        <div class="candidate-info">
                            <span class="candidate-name"><span class="name-text"><?= sanitize($candidate['name']) ?></span></span>
                            <span class="candidate-number">Nomor urut <?= (int)$candidate['no_urut'] ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel">
            <h3 class="panel-title">DIAGRAM BATANG SUARA</h3>
            <div class="chart-area">
                <div class="chart-gridlines" aria-hidden="true"></div>
                <div class="chart-columns" id="chart-columns">
                    <?php if (!$candidates): ?><div class="empty">Belum ada data kandidat.</div><?php endif; ?>
                    <?php foreach ($candidates as $index => $candidate): ?>
                        <?php
                        $total = (int)$candidate['total'];
                        $percentage = $totalVotes > 0 ? round(($total / $totalVotes) * 100, 1) : 0;
                        $height = $highestVotes > 0 ? round(($total / $highestVotes) * 100, 1) : 0;
                        $gradient = $barGradients[$index % count($barGradients)];
                        ?>
                        <article class="chart-column<?= $highestVotes > 0 && $index === 0 ? ' winner' : '' ?>" data-id="<?= (int)$candidate['id'] ?>">
                            <div class="bar-values">
                                <span class="vote-count"><?= number_format($total) ?></span>
                                <span class="percentage"><?= number_format($percentage, 1) ?>%</span>
                            </div>
                            <div class="bar-track" role="progressbar" aria-label="Jumlah suara <?= sanitize($candidate['name']) ?>" aria-valuenow="<?= $total ?>" aria-valuemin="0">
                                <div class="bar-fill" style="--bar-gradient: <?= $gradient ?>; --bar-height: <?= $height ?>%"></div>
                            </div>
                            <span class="bar-label" title="<?= sanitize($candidate['name']) ?>"><?= sanitize($candidate['name']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
    <p class="status" id="update-status" role="status" aria-live="polite"></p>
    <a class="admin-link" href="admin.php">Dashboard panitia</a>
    <script>
        const rankingList = document.getElementById('ranking-list');
        const chartColumns = document.getElementById('chart-columns');
        const totalElement = document.getElementById('total-votes');
        const updateStatus = document.getElementById('update-status');
        let previousTotalVotes = <?= $totalVotes ?>;

        function flashNumber(element) {
            element.classList.remove('flash-number');
            void element.offsetWidth;
            element.classList.add('flash-number');
        }

        function reorderWithAnimation(container, orderedElements) {
            const positions = new Map(
                orderedElements.map((element) => [element, element.getBoundingClientRect()])
            );

            orderedElements.forEach((element) => container.appendChild(element));
            orderedElements.forEach((element) => {
                const previous = positions.get(element);
                const current = element.getBoundingClientRect();
                const deltaX = previous.left - current.left;
                const deltaY = previous.top - current.top;
                if (deltaX !== 0 || deltaY !== 0) {
                    element.style.transition = 'none';
                    element.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
                    element.getBoundingClientRect();
                    requestAnimationFrame(() => {
                        element.style.transition = '';
                        element.style.transform = '';
                    });
                }
            });
        }

        function updateResults(payload) {
            if (!payload || !Number.isInteger(payload.total_votes) || !Array.isArray(payload.candidates)) {
                throw new Error('Format data hasil tidak valid.');
            }

            const rankingCards = new Map(
                [...rankingList.querySelectorAll('.ranking-card')].map((card) => [Number(card.dataset.id), card])
            );
            const chartItems = new Map(
                [...chartColumns.querySelectorAll('.chart-column')].map((column) => [Number(column.dataset.id), column])
            );
            if (payload.candidates.length !== rankingCards.size || payload.candidates.length !== chartItems.size ||
                payload.candidates.some((candidate) => !rankingCards.has(candidate.id) || !chartItems.has(candidate.id))) {
                updateStatus.textContent = 'Daftar kandidat berubah. Muat ulang halaman untuk melihat daftar terbaru.';
                updateStatus.classList.add('error');
                return;
            }

            const highest = Math.max(0, ...payload.candidates.map((candidate) => candidate.total));
            const orderedCards = [];
            const orderedColumns = [];
            payload.candidates.forEach((candidate, index) => {
                if (!Number.isInteger(candidate.id) || !Number.isInteger(candidate.total) ||
                    !Number.isInteger(candidate.no_urut) || typeof candidate.name !== 'string' ||
                    !Number.isFinite(candidate.pct) || candidate.pct < 0 || candidate.pct > 100) {
                    throw new Error('Nilai hasil kandidat tidak valid.');
                }

                const card = rankingCards.get(candidate.id);
                const column = chartItems.get(candidate.id);
                const cardCount = card.querySelector('.rank-count');
                const columnCount = column.querySelector('.vote-count');
                if (Number(cardCount.textContent.replace(/[^\d]/g, '')) !== candidate.total) {
                    cardCount.textContent = candidate.total.toLocaleString('id-ID');
                    flashNumber(cardCount);
                }
                if (Number(columnCount.textContent.replace(/[^\d]/g, '')) !== candidate.total) {
                    columnCount.textContent = candidate.total.toLocaleString('id-ID');
                    flashNumber(columnCount);
                }

                card.querySelector('.rank-pct').textContent = `${candidate.pct.toFixed(1)}%`;
                card.querySelector('.name-text').textContent = candidate.name;
                card.querySelector('.candidate-number').textContent = `Nomor urut ${candidate.no_urut}`;
                card.setAttribute(
                    'aria-label',
                    `Peringkat ${index + 1}, ${candidate.name}, ${candidate.total.toLocaleString('id-ID')} suara`
                );
                column.querySelector('.percentage').textContent = `${candidate.pct.toFixed(1)}%`;
                column.querySelector('.bar-track').setAttribute('aria-label', `Jumlah suara ${candidate.name}`);
                column.querySelector('.bar-track').setAttribute('aria-valuenow', String(candidate.total));
                column.querySelector('.bar-fill').style.setProperty(
                    '--bar-height',
                    `${highest > 0 ? (candidate.total / highest) * 100 : 0}%`
                );
                column.querySelector('.bar-label').textContent = candidate.name;
                column.querySelector('.bar-label').title = candidate.name;
                const isWinner = payload.total_votes > 0 && index === 0;
                card.classList.toggle('winner', isWinner);
                column.classList.toggle('winner', isWinner);
                orderedCards.push(card);
                orderedColumns.push(column);
            });

            reorderWithAnimation(rankingList, orderedCards);
            reorderWithAnimation(chartColumns, orderedColumns);
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
