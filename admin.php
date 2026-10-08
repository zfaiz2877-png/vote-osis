<?php
require 'config.php';

$error = '';
$success = '';
$activeTab = 'candidates';
$recentTokens = [];
$latestTokens = [];

function saveCandidatePhoto($file)
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Foto gagal diunggah. Silakan coba kembali.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Foto harus berupa JPG, PNG, atau WebP yang valid.');
    }

    $filename = 'kandidat_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'upload';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload tidak dapat disiapkan.');
    }
    $path = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Foto tidak dapat disimpan. Periksa izin folder upload.');
    }

    return ['filename' => $filename, 'path' => $path];
}

function removeCandidatePhoto($filename)
{
    $filename = basename((string)$filename);
    if ($filename === '' || $filename === 'default.jpg') {
        return;
    }
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path) && !unlink($path)) {
        error_log('Failed to remove candidate photo: ' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!checkRateLimit('admin_login', 5, 900)) {
        $error = 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.';
    } else {
        $statement = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $statement->execute([$username]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            unset($_SESSION['rate_admin_login_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown')]);
            header('Location: admin.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}

if (($_SESSION['is_admin'] ?? false) === true && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'], $_POST['logout'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
    $activeTab = in_array($_POST['active_tab'] ?? '', ['candidates', 'tokens', 'print'], true)
        ? $_POST['active_tab']
        : 'candidates';

    if (isset($_POST['save_candidate'])) {
        $candidateIdInput = is_string($_POST['candidate_id'] ?? null) ? trim($_POST['candidate_id']) : '';
        $isEdit = $candidateIdInput !== '';
        $id = filter_var(
            $candidateIdInput,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $number = filter_var(
            is_string($_POST['no_urut'] ?? null) ? $_POST['no_urut'] : null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 255]]
        );
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $visi = is_string($_POST['visi'] ?? null) ? trim($_POST['visi']) : '';
        $misi = is_string($_POST['misi'] ?? null) ? trim($_POST['misi']) : '';
        $proker = is_string($_POST['proker'] ?? null) ? trim($_POST['proker']) : '';
        $nameLength = preg_match_all('/./us', $name);
        $uploadedPhoto = null;

        if (($isEdit && $id === false) || $number === false || $name === '' || $nameLength === false || $nameLength > 100 || $visi === '' || $misi === '' || $proker === '') {
            $error = 'Nomor urut, nama, visi, misi, dan program kerja wajib diisi dengan benar.';
        } else {
            try {
                $uploadedPhoto = saveCandidatePhoto($_FILES['photo'] ?? null);
                if (!$isEdit) {
                    $photoName = $uploadedPhoto['filename'] ?? 'default.jpg';
                    $statement = $pdo->prepare(
                        'INSERT INTO candidates (no_urut, name, photo_url, visi, misi, proker) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $statement->execute([$number, $name, $photoName, $visi, $misi, $proker]);
                    $success = 'Kandidat berhasil ditambahkan.';
                    $activeTab = 'candidates';
                } else {
                    $oldStatement = $pdo->prepare('SELECT photo_url FROM candidates WHERE id = ?');
                    $oldStatement->execute([$id]);
                    $existing = $oldStatement->fetch();
                    if (!$existing) {
                        throw new RuntimeException('Kandidat yang akan diubah tidak ditemukan.');
                    }

                    if ($uploadedPhoto !== null) {
                        $statement = $pdo->prepare(
                            'UPDATE candidates SET no_urut = ?, name = ?, photo_url = ?, visi = ?, misi = ?, proker = ? WHERE id = ?'
                        );
                        $statement->execute([$number, $name, $uploadedPhoto['filename'], $visi, $misi, $proker, $id]);
                    } else {
                        $statement = $pdo->prepare(
                            'UPDATE candidates SET no_urut = ?, name = ?, visi = ?, misi = ?, proker = ? WHERE id = ?'
                        );
                        $statement->execute([$number, $name, $visi, $misi, $proker, $id]);
                    }
                    if ($uploadedPhoto !== null) {
                        removeCandidatePhoto($existing['photo_url']);
                    }
                    $success = 'Perubahan kandidat berhasil disimpan.';
                    $activeTab = 'candidates';
                }
            } catch (RuntimeException $exception) {
                if ($uploadedPhoto !== null && is_file($uploadedPhoto['path']) && !unlink($uploadedPhoto['path'])) {
                    error_log('Failed to remove candidate photo after validation error: ' . $uploadedPhoto['path']);
                }
                $error = $exception->getMessage();
            } catch (PDOException $exception) {
                if ($uploadedPhoto !== null && is_file($uploadedPhoto['path']) && !unlink($uploadedPhoto['path'])) {
                    error_log('Failed to remove candidate photo after database error: ' . $uploadedPhoto['path']);
                }
                error_log('Candidate save failed: ' . $exception->getMessage());
                $error = 'Kandidat gagal disimpan. Pastikan nomor urut belum digunakan.';
            }
        }
    } elseif (isset($_POST['delete_candidate'])) {
        $id = filter_var(
            is_string($_POST['candidate_id'] ?? null) ? $_POST['candidate_id'] : null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $activeTab = 'candidates';
        if ($id === false) {
            $error = 'Kandidat yang dipilih tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();
                $statement = $pdo->prepare('SELECT photo_url FROM candidates WHERE id = ? FOR UPDATE');
                $statement->execute([$id]);
                $candidate = $statement->fetch();
                if (!$candidate) {
                    $pdo->rollBack();
                    $error = 'Kandidat tidak ditemukan.';
                } else {
                    $voteCheck = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE candidate_id = ?');
                    $voteCheck->execute([$id]);
                    if ((int)$voteCheck->fetchColumn() > 0) {
                        $pdo->rollBack();
                        $error = 'Kandidat dengan suara masuk tidak dapat dihapus.';
                    } else {
                        $delete = $pdo->prepare('DELETE FROM candidates WHERE id = ?');
                        $delete->execute([$id]);
                        $pdo->commit();
                        removeCandidatePhoto($candidate['photo_url']);
                        $success = 'Kandidat berhasil dihapus.';
                    }
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Candidate deletion failed: ' . $exception->getMessage());
                $error = 'Kandidat gagal dihapus. Silakan periksa data pemilihan.';
            }
        }
    } elseif (isset($_POST['generate_tokens'])) {
        $quantity = filter_var(
            is_string($_POST['quantity'] ?? null) ? $_POST['quantity'] : null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 2000]]
        );
        $activeTab = 'tokens';
        if ($quantity === false) {
            $error = 'Jumlah token harus antara 1 dan 2000.';
        } else {
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $generated = 0;
            $attempts = 0;
            $codes = [];
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO tokens (token_code) VALUES (?)');
                while ($generated < $quantity && $attempts < $quantity * 20) {
                    $attempts++;
                    $code = '';
                    for ($i = 0; $i < 6; $i++) {
                        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                    }
                    try {
                        $insert->execute([$code]);
                        $codes[] = $code;
                        $generated++;
                    } catch (PDOException $exception) {
                        if (($exception->errorInfo[1] ?? null) !== 1062) {
                            throw $exception;
                        }
                    }
                }
                if ($generated !== $quantity) {
                    throw new RuntimeException('Token unik tidak cukup untuk jumlah yang diminta.');
                }
                $pdo->commit();
                $success = "{$generated} token berhasil dibuat.";
                $latestTokens = $codes;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Token generation failed: ' . $exception->getMessage());
                $error = 'Token gagal dibuat. Silakan coba kembali.';
            }
        }
    } elseif (isset($_POST['delete_token'])) {
        $tokenId = filter_var(
            is_string($_POST['token_id'] ?? null) ? $_POST['token_id'] : null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $activeTab = 'tokens';
        if ($tokenId === false) {
            $error = 'Token yang dipilih tidak valid.';
        } else {
            $delete = $pdo->prepare("DELETE FROM tokens WHERE id = ? AND status = 'tersedia'");
            $delete->execute([$tokenId]);
            $success = $delete->rowCount() === 1
                ? 'Token tersedia berhasil dihapus.'
                : 'Token tidak tersedia atau sudah pernah digunakan.';
            if ($delete->rowCount() !== 1) {
                $error = $success;
                $success = '';
            }
        }
    } elseif (isset($_POST['delete_available_tokens'])) {
        $activeTab = 'tokens';
        $deleted = $pdo->exec("DELETE FROM tokens WHERE status = 'tersedia'");
        $success = number_format($deleted) . ' token tersedia berhasil dihapus. Token terpakai tetap tercatat.';
    } elseif (isset($_POST['reset_total'])) {
        $activeTab = 'tokens';
        try {
            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM votes');
            $pdo->exec('DELETE FROM tokens');
            $pdo->commit();
            $success = 'Reset berhasil. Seluruh token dan suara telah dihapus; kandidat tetap tersimpan.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Election reset failed: ' . $exception->getMessage());
            $error = 'Reset gagal dilakukan. Data pemilihan tidak diubah.';
        }
    }
}

$loggedIn = ($_SESSION['is_admin'] ?? false) === true;
$candidates = [];
$stats = ['candidates' => 0, 'tokens' => 0, 'available' => 0, 'used' => 0, 'votes' => 0];
if ($loggedIn) {
    $candidates = $pdo->query('SELECT id, no_urut, name, photo_url, visi, misi, proker FROM candidates ORDER BY no_urut')->fetchAll();
    $stats['candidates'] = count($candidates);
    $stats['tokens'] = (int)$pdo->query('SELECT COUNT(*) FROM tokens')->fetchColumn();
    $stats['available'] = (int)$pdo->query("SELECT COUNT(*) FROM tokens WHERE status = 'tersedia'")->fetchColumn();
    $stats['used'] = (int)$pdo->query("SELECT COUNT(*) FROM tokens WHERE status = 'terpakai'")->fetchColumn();
    $stats['votes'] = (int)$pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
    $recentTokens = $pdo->query(
        'SELECT id, token_code, status, created_at FROM tokens ORDER BY id DESC LIMIT 50'
    )->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin — OSKANER</title>
    <style>
        :root { --primary: #FF4949; --ink: #182230; --muted: #667085; --line: #e5e8ef; --surface: #fff; --bg: #f5f6fa; --green: #138a5b; --red: #b42318; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: "Segoe UI", system-ui, sans-serif; }
        input, textarea, button { font: inherit; }
        .login { width: min(100% - 32px, 420px); margin: 10vh auto; padding: 32px; border: 1px solid var(--line); border-radius: 22px; background: var(--surface); box-shadow: 0 18px 50px #18223012; }
        .login h1 { margin: 0 0 8px; color: var(--primary); }
        .login p { margin: 0 0 25px; color: var(--muted); }
        .dashboard { width: min(100% - 36px, 1240px); margin: 0 auto; padding: 30px 0 60px; }
        .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 26px; }
        .header p { margin: 5px 0 0; color: var(--muted); font-size: 13px; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 20px 0; }
        .stat { padding: 18px; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
        .stat span { display: block; color: var(--muted); font-size: 12px; font-weight: 700; }
        .stat strong { display: block; margin-top: 7px; font-size: 27px; }
        .tabs { display: flex; gap: 8px; margin: 24px 0 16px; overflow-x: auto; }
        .tab-button { padding: 11px 16px; border: 1px solid var(--line); border-radius: 10px; color: #475467; background: white; cursor: pointer; font-weight: 750; white-space: nowrap; }
        .tab-button[aria-selected="true"] { border-color: var(--primary); color: #fff; background: var(--primary); }
        .panel { display: none; padding: 24px; border: 1px solid var(--line); border-radius: 18px; background: #fff; box-shadow: 0 7px 24px #18223008; }
        .panel.active { display: block; }
        .panel h2 { margin: 0 0 18px; font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: flex; flex-direction: column; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 750; }
        input[type="text"], input[type="password"], input[type="number"], input[type="file"], textarea { width: 100%; padding: 12px; border: 1px solid #d0d5dd; border-radius: 10px; color: var(--ink); background: #fff; }
        textarea { min-height: 96px; resize: vertical; }
        input:focus, textarea:focus { outline: 3px solid #ff494922; border-color: var(--primary); }
        .button { display: inline-flex; justify-content: center; align-items: center; gap: 8px; padding: 11px 15px; border: 0; border-radius: 10px; color: #fff; background: var(--primary); cursor: pointer; font-weight: 800; text-decoration: none; }
        .button:hover { filter: brightness(.95); }
        .button.secondary { color: #344054; background: #eef0f4; }
        .button.danger { background: #c83232; }
        .button.small { padding: 8px 10px; font-size: 12px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .alert { margin: 14px 0; padding: 12px 14px; border-radius: 10px; font-weight: 650; }
        .alert.error { border: 1px solid #fecdca; color: #912018; background: #fef3f2; }
        .alert.success { border: 1px solid #abefc6; color: #05603a; background: #ecfdf3; }
        .candidate-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 14px; margin-top: 20px; }
        .candidate-card { overflow: hidden; border: 1px solid var(--line); border-radius: 14px; background: white; }
        .candidate-card img, .photo-empty { display: grid; width: 100%; height: 185px; object-fit: cover; background: #f1f2f6; }
        .photo-empty { place-items: center; color: #98a2b3; font-size: 40px; }
        .candidate-content { padding: 16px; }
        .candidate-content h3 { margin: 7px 0; }
        .number { color: var(--primary); font-size: 12px; font-weight: 850; }
        .candidate-content p { max-height: 56px; overflow: hidden; color: var(--muted); font-size: 13px; line-height: 1.5; }
        .table-wrap { margin-top: 18px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--line); text-align: left; white-space: nowrap; }
        th { color: #475467; background: #f8f9fb; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .token { font-family: ui-monospace, monospace; font-size: 15px; font-weight: 850; letter-spacing: .12em; }
        .badge { display: inline-block; padding: 5px 8px; border-radius: 999px; font-size: 11px; font-weight: 800; }
        .badge.available { color: #067647; background: #ecfdf3; }
        .badge.used { color: #b42318; background: #fef3f2; }
        .muted { color: var(--muted); font-size: 13px; }
        .danger-zone { margin-top: 28px; padding: 18px; border: 1px solid #fecdca; border-radius: 14px; background: #fff8f7; }
        .danger-zone h3 { margin: 0 0 8px; color: #912018; }
        .latest { margin: 14px 0; padding: 14px; border-radius: 10px; background: #f8f9fb; }
        .latest code { display: inline-block; margin: 5px 6px 0 0; padding: 5px 7px; border-radius: 5px; background: #fff; font-weight: 800; letter-spacing: .1em; }
        .logout { border: 0; }
        @media (max-width: 700px) { .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .header { align-items: flex-start; flex-direction: column; } .form-grid { grid-template-columns: 1fr; } .field.full { grid-column: auto; } .panel { padding: 17px; } }
    </style>
</head>
<body>
<?php if (!$loggedIn): ?>
    <main class="login">
        <h1>OSKANER Admin</h1>
        <p>Masuk untuk mengelola pemilihan OSIS SMKN 6 Jember.</p>
        <?php if ($error !== ''): ?><div class="alert error" role="alert"><?= sanitize($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
            <div class="field"><label for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" required></div>
            <div class="field" style="margin-top:14px"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="button" style="width:100%;margin-top:20px" name="login" type="submit">Masuk</button>
        </form>
    </main>
<?php else: ?>
    <main class="dashboard">
        <header class="header">
            <div><h1>Dashboard Pemilihan</h1><p>OSKANER · SMKN 6 Jember</p></div>
            <div class="actions">
                <a class="button secondary" href="hasil.php" target="_blank" rel="noopener">Lihat hasil live</a>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <button class="button secondary logout" name="logout" type="submit">Keluar</button>
                </form>
            </div>
        </header>

        <?php if ($error !== ''): ?><div class="alert error" role="alert"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="alert success" role="status"><?= sanitize($success) ?></div><?php endif; ?>

        <section class="stats" aria-label="Ringkasan">
            <div class="stat"><span>Kandidat</span><strong><?= $stats['candidates'] ?></strong></div>
            <div class="stat"><span>Total token</span><strong><?= number_format($stats['tokens']) ?></strong></div>
            <div class="stat"><span>Token tersedia</span><strong><?= number_format($stats['available']) ?></strong></div>
            <div class="stat"><span>Suara masuk</span><strong><?= number_format($stats['votes']) ?></strong></div>
        </section>

        <nav class="tabs" role="tablist" aria-label="Bagian dashboard">
            <button class="tab-button" id="tab-candidates" type="button" role="tab" aria-controls="panel-candidates" aria-selected="false" data-tab="candidates">Kandidat</button>
            <button class="tab-button" id="tab-tokens" type="button" role="tab" aria-controls="panel-tokens" aria-selected="false" data-tab="tokens">Token</button>
            <button class="tab-button" id="tab-print" type="button" role="tab" aria-controls="panel-print" aria-selected="false" data-tab="print">Cetak token</button>
        </nav>

        <section class="panel" id="panel-candidates" role="tabpanel" aria-labelledby="tab-candidates">
            <h2 id="candidate-form-title">Tambah kandidat</h2>
            <form method="post" enctype="multipart/form-data" id="candidate-form">
                <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                <input type="hidden" name="active_tab" value="candidates">
                <input type="hidden" name="candidate_id" id="candidate-id">
                <div class="form-grid">
                    <div class="field"><label for="no-urut">Nomor urut</label><input id="no-urut" name="no_urut" type="number" min="1" max="255" required></div>
                    <div class="field"><label for="name">Nama kandidat</label><input id="name" name="name" type="text" maxlength="100" required></div>
                    <div class="field full"><label for="photo">Foto kandidat (JPG/PNG/WebP, maksimal 2 MB)</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="field full"><label for="visi">Visi</label><textarea id="visi" name="visi" required></textarea></div>
                    <div class="field"><label for="misi">Misi</label><textarea id="misi" name="misi" required></textarea></div>
                    <div class="field"><label for="proker">Program kerja</label><textarea id="proker" name="proker" required></textarea></div>
                    <div class="field full actions">
                        <button class="button" type="submit" name="save_candidate" id="candidate-submit">Simpan kandidat</button>
                        <button class="button secondary" type="button" id="candidate-cancel" hidden>Batal ubah</button>
                    </div>
                </div>
            </form>
            <div class="candidate-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <?php
                    $photo = !empty($candidate['photo_url']) && $candidate['photo_url'] !== 'default.jpg'
                        ? 'upload/' . rawurlencode(basename($candidate['photo_url']))
                        : '';
                    ?>
                    <article class="candidate-card">
                        <?php if ($photo !== ''): ?><img src="<?= sanitize($photo) ?>" alt="Foto <?= sanitize($candidate['name']) ?>"><?php else: ?><div class="photo-empty" aria-label="Foto belum tersedia">◎</div><?php endif; ?>
                        <div class="candidate-content">
                            <span class="number">NOMOR <?= (int)$candidate['no_urut'] ?></span>
                            <h3><?= sanitize($candidate['name']) ?></h3>
                            <p><?= sanitize($candidate['visi']) ?></p>
                            <div class="actions">
                                <button class="button secondary small edit-candidate" type="button"
                                    data-id="<?= (int)$candidate['id'] ?>"
                                    data-number="<?= (int)$candidate['no_urut'] ?>"
                                    data-name="<?= sanitize($candidate['name']) ?>"
                                    data-visi="<?= sanitize($candidate['visi']) ?>"
                                    data-misi="<?= sanitize($candidate['misi']) ?>"
                                    data-proker="<?= sanitize($candidate['proker']) ?>">Ubah</button>
                                <form method="post" onsubmit="return confirm('Hapus kandidat ini? Kandidat yang sudah menerima suara tidak dapat dihapus.');">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                                    <input type="hidden" name="active_tab" value="candidates">
                                    <input type="hidden" name="candidate_id" value="<?= (int)$candidate['id'] ?>">
                                    <button class="button danger small" name="delete_candidate" type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$candidates): ?><p class="muted">Belum ada kandidat. Tambahkan kandidat melalui formulir di atas.</p><?php endif; ?>
        </section>

        <section class="panel" id="panel-tokens" role="tabpanel" aria-labelledby="tab-tokens">
            <h2>Kelola token pemilihan</h2>
            <form method="post" class="actions">
                <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                <input type="hidden" name="active_tab" value="tokens">
                <div class="field" style="min-width:min(100%,260px)"><label for="quantity">Jumlah token (maksimal 2000 per proses)</label><input id="quantity" name="quantity" type="number" min="1" max="2000" required></div>
                <button class="button" name="generate_tokens" type="submit">Buat token</button>
            </form>
            <?php if ($latestTokens): ?>
                <div class="latest"><strong>Token yang baru dibuat</strong><div><?php foreach ($latestTokens as $code): ?><code><?= sanitize($code) ?></code><?php endforeach; ?></div></div>
            <?php endif; ?>
            <div class="actions" style="margin-top:20px">
                <button class="button secondary" type="button" id="open-print">Cetak token tersedia</button>
                <form method="post" onsubmit="return confirm('Hapus semua token yang masih tersedia? Token yang pernah dipakai dan suara tidak akan dihapus.');">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="active_tab" value="tokens">
                    <button class="button danger" name="delete_available_tokens" type="submit">Hapus token tersedia</button>
                </form>
            </div>
            <div class="table-wrap">
                <h3>Riwayat 50 token terakhir</h3>
                <table>
                    <thead><tr><th>Kode</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentTokens as $token): ?>
                            <tr>
                                <td class="token"><?= sanitize($token['token_code']) ?></td>
                                <td><span class="badge <?= $token['status'] === 'tersedia' ? 'available' : 'used' ?>"><?= $token['status'] === 'tersedia' ? 'TERSEDIA' : 'TERPAKAI' ?></span></td>
                                <td><?= sanitize(date('d/m/Y H:i', strtotime($token['created_at']))) ?></td>
                                <td>
                                    <?php if ($token['status'] === 'tersedia'): ?>
                                        <form method="post" onsubmit="return confirm('Hapus token ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                                            <input type="hidden" name="active_tab" value="tokens">
                                            <input type="hidden" name="token_id" value="<?= (int)$token['id'] ?>">
                                            <button class="button danger small" name="delete_token" type="submit">Hapus</button>
                                        </form>
                                    <?php else: ?><span class="muted">Tersimpan</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentTokens): ?><tr><td colspan="4" class="muted">Belum ada token.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="danger-zone">
                <h3>Reset total token & suara</h3>
                <p class="muted">Menghapus semua token (termasuk riwayat token terpakai) dan semua suara. Daftar kandidat tetap disimpan.</p>
                <form method="post" onsubmit="return confirm('PERINGATAN: seluruh token, riwayat token, dan suara akan dihapus permanen. Lanjutkan?');">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="active_tab" value="tokens">
                    <button class="button danger" name="reset_total" type="submit">Reset total</button>
                </form>
            </div>
        </section>

        <section class="panel" id="panel-print" role="tabpanel" aria-labelledby="tab-print">
            <h2>Cetak token</h2>
            <p class="muted">Token yang dicetak hanya yang masih tersedia. Format cetak menggunakan kertas A4 dengan 4 kolom per baris.</p>
            <p><strong><?= number_format($stats['available']) ?></strong> token tersedia untuk dicetak.</p>
            <button class="button" type="button" id="open-print-tab">Buka layout cetak</button>
        </section>
    </main>
    <script>
        const tabButtons = [...document.querySelectorAll('.tab-button')];
        const panels = [...document.querySelectorAll('.panel')];
        function selectTab(name) {
            tabButtons.forEach((button) => {
                const selected = button.dataset.tab === name;
                button.setAttribute('aria-selected', String(selected));
                button.tabIndex = selected ? 0 : -1;
            });
            panels.forEach((panel) => panel.classList.toggle('active', panel.id === `panel-${name}`));
            sessionStorage.setItem('admin-active-tab', name);
        }
        const initialTab = <?= json_encode($activeTab, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        selectTab(initialTab);
        tabButtons.forEach((button) => button.addEventListener('click', () => selectTab(button.dataset.tab)));

        document.querySelectorAll('.edit-candidate').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('candidate-form-title').textContent = 'Ubah kandidat';
                document.getElementById('candidate-id').value = button.dataset.id;
                document.getElementById('no-urut').value = button.dataset.number;
                document.getElementById('name').value = button.dataset.name;
                document.getElementById('visi').value = button.dataset.visi;
                document.getElementById('misi').value = button.dataset.misi;
                document.getElementById('proker').value = button.dataset.proker;
                document.getElementById('candidate-submit').textContent = 'Simpan perubahan';
                document.getElementById('candidate-cancel').hidden = false;
                document.getElementById('candidate-form').scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        });
        document.getElementById('candidate-cancel').addEventListener('click', () => {
            document.getElementById('candidate-form').reset();
            document.getElementById('candidate-id').value = '';
            document.getElementById('candidate-form-title').textContent = 'Tambah kandidat';
            document.getElementById('candidate-submit').textContent = 'Simpan kandidat';
            document.getElementById('candidate-cancel').hidden = true;
        });

        function openPrintWindow() {
            const printWindow = window.open('print_tokens.php', 'oskaner-print-tokens', 'width=1000,height=800');
            if (!printWindow) {
                alert('Jendela cetak diblokir browser. Izinkan pop-up untuk situs ini lalu coba kembali.');
            }
        }
        document.getElementById('open-print').addEventListener('click', openPrintWindow);
        document.getElementById('open-print-tab').addEventListener('click', openPrintWindow);
    </script>
<?php endif; ?>
</body>
</html>
