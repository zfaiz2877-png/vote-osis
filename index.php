<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Voting OSIS - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --text: #1A202C; --text-light: #718096; --border: #E2E8F0; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%); color: var(--text); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: #fff; padding: 50px 40px; border-radius: 24px; box-shadow: 0 20px 60px rgba(255, 73, 73, 0.15); width: 100%; max-width: 480px; text-align: center; border: 2px solid rgba(255, 73, 73, 0.1); }
        .logo-area h1 { color: var(--primary); font-size: 32px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 8px; }
        .logo-area p { color: var(--text-light); font-size: 14px; font-weight: 600; letter-spacing: 1px; }
        .step-badge { display: inline-block; background: var(--primary-light); color: var(--primary); font-size: 12px; font-weight: 800; padding: 8px 16px; border-radius: 20px; margin-bottom: 20px; border: 2px solid var(--primary); }
        h2 { font-size: 24px; margin-bottom: 12px; font-weight: 800; }
        .desc { color: var(--text-light); font-size: 15px; margin-bottom: 32px; line-height: 1.6; }
        .input-group { margin-bottom: 24px; text-align: left; }
        label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 10px; color: var(--text); }
        input[type="text"] { width: 100%; padding: 20px; border: 3px solid var(--border); border-radius: 16px; font-size: 24px; font-weight: 800; letter-spacing: 4px; text-align: center; text-transform: uppercase; transition: all 0.3s; background: #FAFAFA; }
        input[type="text"]:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 6px var(--primary-light); background: #fff; }
        .btn-primary { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 16px; font-size: 18px; font-weight: 800; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 8px 20px rgba(255, 73, 73, 0.3); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 700; text-align: center; background: #FEE2E2; color: #DC2626; border: 2px solid #FECACA; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-area"><h1>🗳️ OSKANER</h1><p>E-VOTING KETUA OSIS 2025/2026</p></div>
        <?php if (isset($_GET['err'])): ?><div class="alert">⚠️ Kode tidak ditemukan atau sudah digunakan!</div><?php endif; ?>
        <span class="step-badge">LANGKAH 1 DARI 3</span>
        <h2>Masukkan Kode Voucher</h2>
        <p class="desc">Ketik kode unik yang ada di kertas voucher Anda.</p>
        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="token">🎫 KODE VOUCHER</label>
                <input type="text" id="token" name="token" placeholder="OSIS-X7F9" required autofocus autocomplete="off">
            </div>
            <button type="submit" class="btn-primary">Verifikasi Kode →</button>
        </form>
    </div>
</body>
</html>