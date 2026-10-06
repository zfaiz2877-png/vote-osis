<?php 
require 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nis'])) {
    $_SESSION['voter_nis'] = sanitize($_POST['nis']);
    $_SESSION['voter_name'] = sanitize($_POST['name']);
    $_SESSION['voter_class'] = sanitize($_POST['class']);
}

if (isset($_POST['candidate_id'])) {
    try {
        $pdo->beginTransaction();
        $stmtVote = $pdo->prepare("INSERT INTO votes (candidate_id, voter_nis, voter_name, voter_class, token_used) VALUES (?, ?, ?, ?, ?)");
        $stmtVote->execute([$_POST['candidate_id'], $_SESSION['voter_nis'], $_SESSION['voter_name'], $_SESSION['voter_class'], $_SESSION['current_token']]);
        
        $stmtToken = $pdo->prepare("UPDATE tokens SET status = 'terpakai' WHERE token_code = ? AND status = 'tersedia'");
        $stmtToken->execute([$_SESSION['current_token']]);

        if ($stmtToken->rowCount() > 0) {
            $pdo->commit();
            session_destroy();
            header('Location: success.php'); exit;
        } else {
            $pdo->rollBack();
            die("⚠️ Maaf, kode voucher ini baru saja digunakan. Hubungi panitia.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error Sistem. Hubungi Admin.");
    }
}

$candidates = $pdo->query("SELECT * FROM candidates ORDER BY no_urut")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bilik Suara - OSKANER</title>
    <style>
        :root { --primary: #FF4949; --primary-dark: #E63E3E; --primary-light: #FFE5E5; --text: #1A202C; --text-light: #718096; --border: #E2E8F0; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%); color: var(--text); padding: 30px 20px; min-height: 100vh; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: var(--primary); font-size: 28px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px; }
        .step-badge { display: inline-block; background: var(--primary-light); color: var(--primary); font-size: 12px; font-weight: 800; padding: 8px 16px; border-radius: 20px; margin-bottom: 15px; border: 2px solid var(--primary); }
        .header p { color: var(--text-light); font-size: 15px; line-height: 1.6; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 40px rgba(255, 73, 73, 0.1); border: 3px solid transparent; transition: all 0.3s; }
        .card:hover { border-color: var(--primary); transform: translateY(-8px); box-shadow: 0 20px 60px rgba(255, 73, 73, 0.2); }
        .card-number { position: absolute; top: 15px; left: 15px; background: var(--primary); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 24px; z-index: 10; }
        .card-img { width: 100%; height: 280px; object-fit: cover; background: linear-gradient(135deg, var(--primary-light) 0%, #fff 100%); }
        .card-body { padding: 30px 25px; }
        .card-body h3 { font-size: 24px; margin-bottom: 20px; color: var(--text); font-weight: 800; }
        
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .section-content { font-size: 14px; color: var(--text-light); line-height: 1.6; white-space: pre-line; }
        
        .btn-vote { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 16px; font-size: 18px; font-weight: 800; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 8px 20px rgba(255, 73, 73, 0.3); transition: all 0.3s; margin-top: 10px; }
        .btn-vote:hover { background: var(--primary-dark); transform: scale(1.02); }
        
        .warning-box { max-width: 800px; margin: 0 auto 40px; padding: 20px; background: #FFF5F5; border: 2px solid var(--primary); border-radius: 16px; text-align: center; }
        .warning-box p { color: var(--text); font-weight: 600; font-size: 14px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-content { background: white; padding: 40px; border-radius: 24px; max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto; }
        .modal-content h2 { color: var(--primary); margin-bottom: 20px; }
        .modal-content .close { float: right; font-size: 28px; cursor: pointer; color: var(--text-light); }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗳️ OSKANER</h1>
        <span class="step-badge">LANGKAH 3 DARI 3: BILIK SUARA</span>
        <p>Pilih satu kandidat dengan hati-hati. Pilihan Anda bersifat rahasia dan <strong>tidak dapat diubah</strong>.</p>
    </div>

    <div class="warning-box"><p>️ Pastikan Anda memilih kandidat yang tepat. Setelah memilih, suara tidak dapat dibatalkan!</p></div>

    <div class="grid">
        <?php foreach ($candidates as $kandidat): ?>
        <div class="card" style="position: relative;">
            <div class="card-number"><?= $kandidat['no_urut'] ?></div>
            <img src="upload/<?= htmlspecialchars($kandidat['photo_url']) ?>" class="card-img" alt="<?= htmlspecialchars($kandidat['name']) ?>">
            <div class="card-body">
                <h3><?= htmlspecialchars($kandidat['name']) ?></h3>
                
                <div class="section">
                    <div class="section-title">🎯 Visi</div>
                    <div class="section-content"><?= htmlspecialchars($kandidat['visi']) ?></div>
                </div>
                
                <div class="section">
                    <div class="section-title">📋 Misi</div>
                    <div class="section-content"><?= htmlspecialchars($kandidat['misi']) ?></div>
                </div>
                
                <button onclick="showProker(<?= $kandidat['id'] ?>)" class="btn" style="width:100%; padding:12px; background:var(--primary-light); color:var(--primary); border:none; border-radius:12px; font-weight:700; cursor:pointer; margin-bottom:10px;"> Lihat Program Kerja</button>
                
                <form method="POST" onsubmit="return confirm('⚠️ YAKIN memilih kandidat No. <?= $kandidat['no_urut'] ?> - <?= htmlspecialchars($kandidat['name']) ?>?\n\nSuara TIDAK dapat diubah!');">
                    <input type="hidden" name="candidate_id" value="<?= $kandidat['id'] ?>">
                    <button type="submit" class="btn-vote">🗳️ COBLOS</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal Program Kerja -->
    <div id="prokerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Program Kerja</h2>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        const prokerData = <?= json_encode(array_column($candidates, 'proker', 'id')) ?>;
        const nameData = <?= json_encode(array_column($candidates, 'name', 'id')) ?>;
        
        function showProker(id) {
            document.getElementById('modalTitle').innerText = 'Program Kerja - ' + nameData[id];
            document.getElementById('modalContent').innerHTML = '<div style="white-space: pre-line; line-height: 1.8; font-size: 16px;">' + prokerData[id] + '</div>';
            document.getElementById('prokerModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('prokerModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('prokerModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>