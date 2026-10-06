<?php
require 'config.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Login Handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    checkRateLimit('admin_login', 5, 900);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}

// Generate Token Handler
$latest_tokens = [];
if (isset($_POST['generate_token']) && isset($_SESSION['is_admin'])) {
    $qty = (int)$_POST['qty'];
    if ($qty > 0 && $qty <= 1000) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $generated = 0;
        for ($i = 0; $i < $qty; $i++) {
            $token = 'OSIS-' . substr(str_shuffle($chars), 0, 8);
            try {
                $stmt = $pdo->prepare("INSERT INTO tokens (token_code) VALUES (?)");
                $stmt->execute([$token]);
                $generated++;
            } catch (Exception $e) { /* Skip duplikat */ }
        }
        $success = "✅ Berhasil generate {$generated} token baru!";
        
        // Ambil token terbaru untuk ditampilkan langsung
        $stmt = $pdo->query("SELECT token_code, status, created_at FROM tokens ORDER BY id DESC LIMIT " . (int)$qty);
        $latest_tokens = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --text: #1A202C; --text-light: #718096; --border: #E2E8F0; --success: #48BB78; --success-light: #C6F6D5; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #F7FAFC; color: var(--text); }
        .login-container { max-width: 400px; margin: 100px auto; padding: 40px; background: white; border-radius: 24px; box-shadow: 0 20px 60px rgba(255, 73, 73, 0.15); }
        .login-container h2 { color: var(--primary); text-align: center; margin-bottom: 30px; font-size: 28px; font-weight: 900; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; }
        input[type="text"], input[type="password"], input[type="number"] { width: 100%; padding: 14px; border: 2px solid var(--border); border-radius: 12px; font-size: 16px; transition: all 0.3s; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
        .btn { padding: 14px 24px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; text-align: center;}
        .btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-full { width: 100%; }
        .btn-export { background: var(--success); width: 100%; font-size: 18px; padding: 18px; margin-top: 20px;}
        .btn-export:hover { background: #38A169; }
        .alert { padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .alert-error { background: #FEE2E2; color: #DC2626; border: 2px solid #FECACA; }
        .alert-success { background: var(--success-light); color: #22543D; border: 2px solid #9AE6B4; }
        .dashboard { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 15px;}
        .header h1 { color: var(--primary); font-size: 32px; font-weight: 900; }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap;}
        .nav-links a { padding: 10px 20px; background: white; color: var(--text); text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s; border: 1px solid var(--border);}
        .nav-links a:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-box { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; }
        .stat-box h3 { font-size: 48px; color: var(--primary); margin: 10px 0; font-weight: 900; }
        .stat-box p { color: var(--text-light); font-weight: 600; font-size: 14px; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h2 { color: var(--text); margin-bottom: 20px; font-size: 22px; font-weight: 800; }
        .form-row { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;}
        .form-row input { flex: 1; min-width: 200px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--primary-light); color: var(--primary); font-weight: 800; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-success { background: var(--success-light); color: #22543D; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }
        .token-code { font-family: monospace; font-size: 16px; font-weight: bold; letter-spacing: 1px;}
    </style>
</head>
<body>
<?php if (!isset($_SESSION['is_admin'])): ?>
    <div class="login-container">
        <h2>🔒 Admin Login</h2>
        <?php if (isset($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required autofocus></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" name="login" class="btn btn-full">Masuk Dashboard</button>
        </form>
        <p style="text-align:center; margin-top:20px; color:var(--text-light); font-size:13px;">Default: admin / admin123</p>
    </div>
<?php else: ?>
    <div class="dashboard">
        <div class="header">
            <h1>📊 Admin Dashboard</h1>
            <div class="nav-links">
                <a href="manage_kandidat.php">👥 Kelola Kandidat</a>
                <a href="?logout=1">🚪 Logout</a>
            </div>
        </div>

        <div class="stats">
            <?php
            $total = $pdo->query("SELECT COUNT(*) FROM tokens")->fetchColumn();
            $used = $pdo->query("SELECT COUNT(*) FROM tokens WHERE status='terpakai'")->fetchColumn();
            $votes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
            ?>
            <div class="stat-box"><p>Total Token</p><h3><?= $total ?></h3></div>
            <div class="stat-box"><p>Sudah Dipakai</p><h3><?= $used ?></h3></div>
            <div class="stat-box"><p>Total Suara Masuk</p><h3><?= $votes ?></h3></div>
        </div>

        <!-- GENERATE & EXPORT SECTION -->
        <div class="card">
            <h2> Generate & Export Token</h2>
            <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            
            <form method="POST" class="form-row">
                <input type="number" name="qty" placeholder="Jumlah token yang mau dibuat (misal: 50)" min="1" max="1000" required>
                <button type="submit" name="generate_token" class="btn">⚡ Generate Token</button>
            </form>

            <!-- TABEL LANGSUNG MUNCUL SETELAH GENERATE -->
            <?php if (!empty($latest_tokens)): ?>
                <h3 style="margin-top: 30px; color: var(--text);">📋 Token Baru Saja Dibuat:</h3>
                <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Token</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_tokens as $index => $t): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td class="token-code"><?= $t['token_code'] ?></td>
                            <td><span class="badge badge-success">TERSEDIA</span></td>
                            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <!-- TOMBOL EXPORT BESAR -->
                <a href="export_token.php" class="btn btn-export">⬇️ DOWNLOAD SEMUA TOKEN (EXCEL/CSV)</a>
                <p style="text-align:center; margin-top:10px; color:var(--text-light); font-size:13px;">File CSV bisa langsung dibuka di Microsoft Excel atau Google Sheets.</p>
            <?php endif; ?>
        </div>

        <!-- HASIL VOTING -->
        <div class="card">
            <h2>📈 Perolehan Suara Sementara</h2>
            <table>
                <thead><tr><th>No</th><th>Kandidat</th><th>Jumlah Suara</th><th>Persentase</th></tr></thead>
                <tbody>
                    <?php
                    $results = $pdo->query("SELECT c.no_urut, c.name, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON c.id = v.candidate_id GROUP BY c.id ORDER BY c.no_urut")->fetchAll();
                    $maxVotes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn() ?: 1;
                    foreach ($results as $row) {
                        $pct = round(($row['total'] / $maxVotes) * 100, 1);
                        echo "<tr>
                            <td><strong>{$row['no_urut']}</strong></td>
                            <td>{$row['name']}</td>
                            <td>{$row['total']} Suara</td>
                            <td><span class='badge badge-success'>{$pct}%</span></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</body>
</html>