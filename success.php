<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fc">
    <title>Suara Tercatat — OSKANER</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; place-items: center; margin: 0; padding: 20px; color: #182230; background: radial-gradient(ellipse at 15% 5%, #d9f8e9, transparent 40%), #f7f8fc; font-family: "Segoe UI", system-ui, sans-serif; }
        .card { width: min(100%, 520px); padding: clamp(28px, 7vw, 52px); border: 1px solid #ffffff; border-radius: 26px; background: #ffffffed; box-shadow: 0 24px 70px #18223014; text-align: center; }
        .check { display: grid; width: 84px; height: 84px; place-items: center; margin: 0 auto 22px; border-radius: 50%; color: #087443; background: #dcfce7; box-shadow: 0 0 0 12px #dcfce755; font-size: 42px; font-weight: 900; }
        h1 { margin: 0 0 12px; font-size: clamp(26px, 5vw, 34px); letter-spacing: -.03em; }
        p { margin: 0 auto 27px; color: #667085; line-height: 1.7; }
        .primary { display: block; padding: 15px 20px; border-radius: 12px; color: #fff; background: #FF4949; font-weight: 850; text-decoration: none; transition: transform .2s, background .2s; }
        .primary:hover { transform: translateY(-2px); background: #e83e3e; }
        .secondary { display: inline-block; margin-top: 17px; color: #667085; font-size: 13px; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <main class="card">
        <div class="check" aria-hidden="true">✓</div>
        <h1>Suara Anda tercatat!</h1>
        <p>Terima kasih telah berpartisipasi dalam pemilihan Ketua OSIS OSKANER SMKN 6 Jember. Pilihan Anda tersimpan secara rahasia.</p>
        <a class="primary" href="index.php">Kembali ke awal</a>
        <a class="secondary" href="hasil.php">Lihat hasil sementara</a>
    </main>
</body>
</html>
