<?php 
require 'config.php'; 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = strtoupper(trim($_POST['token']));
    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE token_code = ? AND status = 'tersedia'");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $_SESSION['current_token'] = $token;
    } else {
        header("Location: index.php?err=1"); exit;
    }
} elseif (!isset($_SESSION['current_token'])) {
    header("Location: index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrasi - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --text: #1A202C; --text-light: #718096; --border: #E2E8F0; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%); color: var(--text); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: #fff; padding: 50px 40px; border-radius: 24px; box-shadow: 0 20px 60px rgba(255, 73, 73, 0.15); width: 100%; max-width: 480px; text-align: center; border: 2px solid rgba(255, 73, 73, 0.1); }
        .logo-area h1 { color: var(--primary); font-size: 32px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 8px; }
        .step-badge { display: inline-block; background: var(--primary-light); color: var(--primary); font-size: 12px; font-weight: 800; padding: 8px 16px; border-radius: 20px; margin-bottom: 20px; border: 2px solid var(--primary); }
        h2 { font-size: 24px; margin-bottom: 12px; font-weight: 800; }
        .desc { color: var(--text-light); font-size: 15px; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 10px; color: var(--text); }
        input, select { width: 100%; padding: 18px; border: 3px solid var(--border); border-radius: 16px; font-size: 16px; font-weight: 600; transition: all 0.3s; background: #FAFAFA; }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 6px var(--primary-light); background: #fff; }
        .btn-primary { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 16px; font-size: 18px; font-weight: 800; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 8px 20px rgba(255, 73, 73, 0.3); transition: all 0.3s; margin-top: 10px; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-area"><h1>🗳️ OSKANER</h1></div>
        <span class="step-badge">LANGKAH 2 DARI 3</span>
        <h2>Verifikasi Data Diri</h2>
        <p class="desc">Pastikan data Anda benar sebelum masuk ke bilik suara.</p>
        <form action="vote.php" method="POST">
            <div class="input-group"><label>👤 NAMA LENGKAP</label><input type="text" name="name" required></div>
            <div class="input-group"><label>🏫 KELAS</label>
                <select name="class" required>
                    <option value="">-- Pilih Kelas --</option>
                    <option value="X RPL 1">X RPL 1</option><option value="X TKJ 1">X TKJ 1</option>
                    <option value="XI RPL 1">XI RPL 1</option><option value="XI TKJ 1">XI TKJ 1</option>
                    <option value="XII RPL 1">XII RPL 1</option><option value="XII TKJ 1">XII TKJ 1</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Lanjut ke Bilik Suara →</button>
        </form>
    </div>
</body>
</html>