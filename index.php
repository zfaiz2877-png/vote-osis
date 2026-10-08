<?php
require 'config.php';

$error = '';
$candidates = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Sesi tidak valid. Muat ulang halaman dan coba kembali.';
    } elseif (isset($_POST['validate_token'])) {
        $token = isset($_POST['token']) && is_string($_POST['token'])
            ? strtoupper(trim($_POST['token']))
            : '';

        if (!preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $token)) {
            $error = 'Kode token harus terdiri dari 6 karakter yang valid.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM tokens WHERE token_code = ? AND status = 'tersedia'");
            $stmt->execute([$token]);
            if ($stmt->fetch()) {
                $_SESSION['current_token'] = $token;
            } else {
                $error = 'Kode token tidak ditemukan atau sudah digunakan.';
                unset($_SESSION['current_token']);
            }
        }
    } elseif (isset($_POST['candidate_id'])) {
        $candidateId = filter_var(
            is_string($_POST['candidate_id'] ?? null) ? $_POST['candidate_id'] : null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $token = $_SESSION['current_token'] ?? '';

        if ($candidateId === false || !is_string($token) || $token === '') {
            unset($_SESSION['current_token']);
            $error = 'Sesi pemilihan berakhir. Masukkan kembali kode token Anda.';
        } else {
            try {
                $pdo->beginTransaction();
                $tokenCheck = $pdo->prepare(
                    "SELECT id FROM tokens WHERE token_code = ? AND status = 'tersedia' FOR UPDATE"
                );
                $tokenCheck->execute([$token]);
                $availableToken = $tokenCheck->fetch();

                $candidateCheck = $pdo->prepare('SELECT id FROM candidates WHERE id = ?');
                $candidateCheck->execute([$candidateId]);
                $candidateExists = $candidateCheck->fetch();

                if (!$availableToken) {
                    $pdo->rollBack();
                    unset($_SESSION['current_token']);
                    $error = 'Token ini sudah digunakan. Silakan hubungi panitia.';
                } elseif (!$candidateExists) {
                    $pdo->rollBack();
                    $error = 'Kandidat tidak ditemukan. Silakan muat ulang halaman.';
                } else {
                    $insertVote = $pdo->prepare(
                        'INSERT INTO votes (candidate_id, token_used) VALUES (?, ?)'
                    );
                    $insertVote->execute([$candidateId, $token]);

                    $updateToken = $pdo->prepare(
                        "UPDATE tokens SET status = 'terpakai' WHERE id = ? AND status = 'tersedia'"
                    );
                    $updateToken->execute([$availableToken['id']]);
                    if ($updateToken->rowCount() !== 1) {
                        throw new RuntimeException('Token status changed during vote.');
                    }

                    $pdo->commit();
                    unset($_SESSION['current_token']);
                    header('Location: success.php', true, 303);
                    exit;
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Voting failed: ' . $exception->getMessage());
                http_response_code(500);
                $error = 'Suara gagal disimpan. Silakan hubungi panitia.';
            }
        }
    }
}

$hasValidatedToken = false;
if ($error === '' && isset($_SESSION['current_token'])) {
    $tokenCheck = $pdo->prepare(
        "SELECT id FROM tokens WHERE token_code = ? AND status = 'tersedia'"
    );
    $tokenCheck->execute([$_SESSION['current_token']]);
    $hasValidatedToken = (bool)$tokenCheck->fetch();
    if (!$hasValidatedToken) {
        unset($_SESSION['current_token']);
    }
}

if ($hasValidatedToken) {
    $candidates = $pdo->query('SELECT id, no_urut, name, photo_url, visi FROM candidates ORDER BY no_urut')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FF4949">
    <title>E-Voting OSIS — OSKANER SMKN 6 Jember</title>
    <style>
        :root { color-scheme: light; --primary: #FF4949; --ink: #182230; --muted: #667085; --line: #e7eaf0; --surface: #fff; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: var(--ink); background: radial-gradient(ellipse at 15% 5%, #ffe3df, transparent 42%), #f7f8fc; font-family: "Segoe UI", system-ui, sans-serif; }
        button, input { font: inherit; }
        .shell { width: min(100% - 32px, 1080px); margin: 0 auto; padding: 36px 0 56px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 46px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .mark { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 15px; color: #fff; background: var(--primary); box-shadow: 0 10px 24px #ff494933; font-size: 24px; }
        .brand strong { display: block; letter-spacing: .08em; }
        .brand span { display: block; color: var(--muted); font-size: 12px; margin-top: 2px; }
        .admin-link { color: var(--muted); font-size: 13px; text-decoration: none; }
        .hero { max-width: 610px; margin: 0 auto 32px; text-align: center; }
        .eyebrow { color: var(--primary); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 12px 0; font-size: clamp(30px, 6vw, 48px); line-height: 1.08; letter-spacing: -.04em; }
        .hero p { margin: 0; color: var(--muted); line-height: 1.7; }
        .entry { max-width: 470px; margin: 28px auto; padding: clamp(22px, 5vw, 34px); border: 1px solid #ffffff; border-radius: 24px; background: #ffffffdf; box-shadow: 0 20px 60px #18223012; }
        .entry label { display: block; margin-bottom: 11px; font-weight: 750; }
        .token-input { width: 100%; border: 2px solid var(--line); border-radius: 14px; padding: 17px; color: var(--ink); background: #fafbfc; text-align: center; font-size: 26px; font-weight: 850; letter-spacing: .3em; text-transform: uppercase; }
        .token-input:focus { outline: 3px solid #ff494922; border-color: var(--primary); }
        .hint { margin: 10px 0 18px; color: var(--muted); font-size: 12px; text-align: center; }
        .primary-button { width: 100%; border: 0; border-radius: 13px; padding: 16px 20px; color: #fff; background: var(--primary); box-shadow: 0 10px 22px #ff494933; cursor: pointer; font-weight: 850; transition: transform .2s, background .2s; }
        .primary-button:hover { transform: translateY(-2px); background: #e93d3d; }
        .alert { max-width: 620px; margin: 0 auto 18px; border: 1px solid #fecaca; border-radius: 12px; padding: 13px 16px; color: #991b1b; background: #fef2f2; font-weight: 650; text-align: center; }
        .pick-heading { margin: 32px 0 18px; text-align: center; }
        .pick-heading h2 { margin: 0 0 7px; font-size: 27px; }
        .pick-heading p { margin: 0; color: var(--muted); }
        .candidate-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 265px), 1fr)); gap: 18px; }
        .candidate { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--line); border-radius: 20px; background: var(--surface); box-shadow: 0 12px 30px #1822300b; }
        .photo { width: 100%; height: 240px; object-fit: cover; background: #f1f2f6; }
        .photo-empty { display: grid; place-items: center; color: #98a2b3; font-size: 48px; }
        .candidate-body { display: flex; flex: 1; flex-direction: column; padding: 20px; }
        .number { align-self: flex-start; margin-bottom: 9px; padding: 5px 10px; border-radius: 99px; color: #a92d2d; background: #fff0ed; font-size: 12px; font-weight: 800; }
        .candidate h3 { margin: 0 0 10px; font-size: 21px; }
        .vision { flex: 1; margin: 0 0 18px; color: var(--muted); font-size: 14px; line-height: 1.65; white-space: pre-line; }
        .vote-button { width: 100%; padding: 15px; border: 0; border-radius: 12px; color: white; background: var(--primary); font-weight: 850; cursor: pointer; }
        .vote-button:disabled { opacity: .65; cursor: wait; }
        .empty { max-width: 540px; margin: 0 auto; padding: 22px; border: 1px solid var(--line); border-radius: 16px; color: var(--muted); background: #fff; text-align: center; }
        footer { margin-top: 48px; color: var(--muted); font-size: 12px; text-align: center; }
        @media (max-width: 560px) { .shell { padding-top: 22px; } .top { margin-bottom: 38px; } .photo { height: 215px; } }
    </style>
</head>
<body>
    <main class="shell">
        <header class="top">
            <div class="brand">
                <div class="mark" aria-hidden="true">✦</div>
                <div><strong>OSKANER</strong><span>E-Voting OSIS · SMKN 6 Jember</span></div>
            </div>
            <a class="admin-link" href="admin.php">Admin</a>
        </header>

        <section class="hero">
            <div class="eyebrow">Pemilihan Ketua OSIS</div>
            <h1>Satu suara untuk<br>masa depan sekolah.</h1>
            <p>Masukkan token pemilihan untuk melihat kandidat dan memberikan suara secara rahasia.</p>
        </section>

        <?php if ($error !== ''): ?>
            <div class="alert" role="alert"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (!$hasValidatedToken): ?>
            <form class="entry" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                <label for="token">Kode token pemilihan</label>
                <input class="token-input" id="token" name="token" type="text" minlength="6" maxlength="6" pattern="[A-HJ-NP-Z2-9]{6}" inputmode="text" placeholder="ABC234" required autofocus>
                <p class="hint">6 karakter · Tidak menggunakan I, O, 0, atau 1</p>
                <button class="primary-button" type="submit" name="validate_token">Lanjut ke pemilihan</button>
            </form>
        <?php else: ?>
            <section class="pick-heading">
                <h2>Pilih kandidat Anda</h2>
                <p>Suara bersifat rahasia dan tidak dapat diubah setelah dikirim.</p>
            </section>
            <?php if (!$candidates): ?>
                <p class="empty">Belum ada kandidat yang tersedia. Silakan hubungi panitia.</p>
            <?php else: ?>
                <div class="candidate-grid">
                    <?php foreach ($candidates as $candidate): ?>
                        <article class="candidate">
                            <?php if (!empty($candidate['photo_url']) && $candidate['photo_url'] !== 'default.jpg'): ?>
                                <img class="photo" src="upload/<?= rawurlencode(basename($candidate['photo_url'])) ?>" alt="Foto <?= sanitize($candidate['name']) ?>">
                            <?php else: ?>
                                <div class="photo photo-empty" aria-label="Foto belum tersedia">◎</div>
                            <?php endif; ?>
                            <div class="candidate-body">
                                <span class="number">NOMOR URUT <?= (int)$candidate['no_urut'] ?></span>
                                <h3><?= sanitize($candidate['name']) ?></h3>
                                <p class="vision"><?= sanitize($candidate['visi']) ?></p>
                                <form method="post" data-candidate-name="<?= sanitize($candidate['name']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                                    <input type="hidden" name="candidate_id" value="<?= (int)$candidate['id'] ?>">
                                    <button class="vote-button" type="submit">Pilih kandidat ini</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <footer>Pemilihan OSIS OSKANER · SMKN 6 Jember</footer>
    </main>
    <script>
        const tokenInput = document.getElementById('token');
        if (tokenInput) {
            tokenInput.addEventListener('input', () => {
                tokenInput.value = tokenInput.value.toUpperCase().replace(/[^A-HJ-NP-Z2-9]/g, '').slice(0, 6);
            });
        }
        document.querySelectorAll('.candidate form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(`Yakin memilih ${form.dataset.candidateName}? Suara tidak dapat diubah setelah dikirim.`)) {
                    event.preventDefault();
                    return;
                }
                const button = form.querySelector('button');
                button.disabled = true;
                button.textContent = 'Menyimpan suara…';
            });
        });
    </script>
</body>
</html>
