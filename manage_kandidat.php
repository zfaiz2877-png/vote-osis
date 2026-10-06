<?php
require 'config.php';
checkAdmin();

// Tambah Kandidat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_candidate'])) {
    $no_urut = (int)$_POST['no_urut'];
    $name = sanitize($_POST['name']);
    $visi = sanitize($_POST['visi']);
    $misi = sanitize($_POST['misi']);
    $proker = sanitize($_POST['proker']);
    
    // Upload Foto
    $photo_url = 'default.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 2000000) {
            $photo_url = 'kandidat_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], 'upload/' . $photo_url);
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO candidates (no_urut, name, photo_url, visi, misi, proker) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$no_urut, $name, $photo_url, $visi, $misi, $proker]);
        $success = "Kandidat berhasil ditambahkan!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Hapus Kandidat
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT photo_url FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();
    
    if ($candidate && $candidate['photo_url'] != 'default.jpg') {
        @unlink('upload/' . $candidate['photo_url']);
    }
    
    $pdo->prepare("DELETE FROM candidates WHERE id = ?")->execute([$id]);
    header('Location: manage_kandidat.php');
    exit;
}

$candidates = $pdo->query("SELECT * FROM candidates ORDER BY no_urut")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kandidat - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --text: #1A202C; --text-light: #718096; --border: #E2E8F0; --success: #48BB78; --success-light: #C6F6D5; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #F7FAFC; color: var(--text); }
        .dashboard { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { color: var(--primary); font-size: 32px; font-weight: 900; }
        .nav-links a { padding: 10px 20px; background: white; color: var(--text); text-decoration: none; border-radius: 10px; font-weight: 600; margin-left: 10px; }
        .nav-links a:hover { background: var(--primary-light); color: var(--primary); }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h2 { color: var(--text); margin-bottom: 20px; font-size: 22px; font-weight: 800; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: 12px; font-size: 16px; transition: all 0.3s; font-family: inherit; }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
        textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 14px 24px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn:hover { background: var(--primary-dark); }
        .btn-danger { background: #DC2626; }
        .btn-danger:hover { background: #991B1B; }
        .alert { padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: var(--success-light); color: #22543D; border: 2px solid #9AE6B4; }
        .alert-error { background: #FEE2E2; color: #DC2626; border: 2px solid #FECACA; }
        .candidate-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px; }
        .candidate-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 2px solid var(--border); }
        .candidate-card img { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; background: var(--primary-light); }
        .candidate-card h3 { font-size: 20px; margin-bottom: 10px; }
        .candidate-card .no-urut { display: inline-block; background: var(--primary); color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 10px; }
        .candidate-card p { color: var(--text-light); font-size: 14px; margin-bottom: 15px; line-height: 1.6; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>👥 Kelola Kandidat</h1>
            <div class="nav-links">
                <a href="admin.php">📊 Dashboard</a>
                <a href="export_token.php"> Export Token</a>
            </div>
        </div>

        <div class="card">
            <h2>➕ Tambah Kandidat Baru</h2>
            <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if (isset($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nomor Urut</label>
                    <input type="number" name="no_urut" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label>Nama Kandidat</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Foto Kandidat (Max 2MB, JPG/PNG)</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Visi</label>
                    <textarea name="visi" required></textarea>
                </div>
                <div class="form-group">
                    <label>Misi (Pisahkan dengan enter)</label>
                    <textarea name="misi" required></textarea>
                </div>
                <div class="form-group">
                    <label>Program Kerja (Pisahkan dengan enter)</label>
                    <textarea name="proker" required></textarea>
                </div>
                <button type="submit" name="add_candidate" class="btn">Tambah Kandidat</button>
            </form>
        </div>

        <div class="card">
            <h2>📋 Daftar Kandidat</h2>
            <div class="candidate-list">
                <?php foreach ($candidates as $c): ?>
                <div class="candidate-card">
                    <img src="upload/<?= htmlspecialchars($c['photo_url']) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
                    <span class="no-urut">No. <?= $c['no_urut'] ?></span>
                    <h3><?= htmlspecialchars($c['name']) ?></h3>
                    <p><strong>Visi:</strong><br><?= nl2br(htmlspecialchars($c['visi'])) ?></p>
                    <div class="actions">
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus kandidat ini?')">Hapus</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>