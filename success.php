<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Terima Kasih - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --success: #48BB78; --success-light: #C6F6D5; --text: #1A202C; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, var(--success-light) 0%, var(--primary-light) 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; text-align: center; }
        .container { max-width: 600px; padding: 50px 40px; background: white; border-radius: 32px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .icon { font-size: 100px; margin-bottom: 25px; animation: bounce 1s ease; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        h1 { color: var(--success); font-size: 36px; margin-bottom: 15px; font-weight: 900; letter-spacing: 2px; }
        p { color: var(--text); font-size: 18px; margin-bottom: 40px; line-height: 1.6; }
        .btn-reset { display: inline-block; padding: 24px 50px; background: var(--primary); color: white; text-decoration: none; border-radius: 20px; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 10px 30px rgba(255, 73, 73, 0.3); transition: all 0.3s; }
        .btn-reset:hover { background: var(--primary-dark); transform: translateY(-3px); }
        .thank-note { margin-top: 30px; padding: 20px; background: var(--primary-light); border-radius: 16px; }
        .thank-note p { font-size: 14px; margin: 0; color: var(--text); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅</div>
        <h1>SUARA ANDA TERCATAT!</h1>
        <p>Terima kasih telah berpartisipasi dalam<br><strong>Pemilihan Ketua OSIS OSKANER</strong><br>SMKN 6 Jember 2025/2026</p>
        <a href="reset.php" class="btn-reset">Selesai / Siswa Berikutnya →</a>
        <div class="thank-note"><p>🎯 Hak pilih Anda telah digunakan. Silakan informasikan kepada panitia.</p></div>
    </div>
</body>
</html>