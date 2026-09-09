<?php
require_once __DIR__ . '/config.php';

$target = rtrim(BASE_URL, '/') . '/';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reward QR Link</title>
<style>
body{font-family:system-ui;background:#eef5ff;margin:0;padding:30px;text-align:center;color:#17324d}
.card{background:#fff;max-width:520px;margin:auto;border-radius:26px;padding:28px;box-shadow:0 15px 40px #0001}
a{display:inline-block;margin-top:15px;padding:12px 18px;border-radius:12px;background:#0b63ce;color:#fff;text-decoration:none;font-weight:800}
.url{font-size:13px;word-break:break-all;background:#f5f8fb;padding:10px;border-radius:10px}
</style>
</head>
<body>
<div class="card">
  <h1>🎁 AI CENTER REWARD</h1>
  <p>ลิงก์สำหรับนำไปสร้าง QR Code แบบไฟล์ PNG</p>
  <div class="url"><?=htmlspecialchars($target)?></div>
  <a href="<?=htmlspecialchars($target)?>">ทดสอบหน้ารับสิทธิ์</a>
</div>
</body>
</html>
