<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AI Center Reward</title>
<style>
:root{
  --bg:#eef5ff;--card:#fff;--primary:#0b63ce;--primary2:#084b9a;
  --text:#17324d;--muted:#6c7f92;--line:#dce8f6;
}
*{box-sizing:border-box}
body{
  margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
  padding:20px;font-family:system-ui,-apple-system,"Segoe UI",Tahoma,sans-serif;
  background:linear-gradient(135deg,#eaf4ff,#f7fbff);color:var(--text)
}
.card{
  width:min(520px,100%);background:var(--card);border:1px solid var(--line);
  border-radius:28px;padding:30px 24px;box-shadow:0 18px 50px rgba(20,71,120,.12);
  text-align:center
}
.logo{
  width:78px;height:78px;border-radius:24px;margin:0 auto 16px;display:grid;
  place-items:center;background:linear-gradient(135deg,var(--primary),#4f9df5);
  color:white;font-size:36px
}
h1{margin:0 0 8px;font-size:30px}
.sub{color:var(--muted);margin-bottom:22px}
.status{border-radius:20px;padding:20px;margin:18px 0;border:1px solid var(--line);background:#f9fcff}
.status.ok{background:#eefbf5;border-color:#bdebd5}
.status.bad{background:#fff2f2;border-color:#f0caca}
.big{font-size:24px;font-weight:800;margin-bottom:8px}
.code{font-size:34px;letter-spacing:4px;font-weight:900;color:var(--primary2);margin:12px 0 4px}
.note{font-size:14px;color:var(--muted);line-height:1.6}
.hidden{display:none}
.footer{margin-top:18px;font-size:12px;color:#8a9aac}
</style>
</head>
<body>
<div class="card">
  <div class="logo">🎁</div>
  <h1>AI CENTER REWARD</h1>
  <div class="sub">สแกนเพื่อตรวจสอบสิทธิ์รับของรางวัล</div>

  <div id="loading" class="status">
    <div class="big">กำลังตรวจสอบสิทธิ์...</div>
    <div class="note">กรุณารอสักครู่</div>
  </div>

  <div id="available" class="status ok hidden">
    <div class="big">✅ พร้อมรับของรางวัล</div>
    <div>กรุณาแสดงหน้านี้ให้เจ้าหน้าที่</div>
    <div id="claimCode" class="code"></div>
    <div class="note">เจ้าหน้าที่จะเป็นผู้ยืนยันการมอบรางวัล</div>
  </div>

  <div id="redeemed" class="status bad hidden">
    <div class="big">❌ ใช้สิทธิ์แล้ว</div>
    <div>อุปกรณ์นี้ได้รับของรางวัลไปแล้ว</div>
    <div id="redeemedTime" class="note"></div>
  </div>

  <div id="error" class="status bad hidden">
    <div class="big">เกิดข้อผิดพลาด</div>
    <div id="errorText" class="note"></div>
  </div>

  <div class="footer">1 Browser / 1 สิทธิ์ • AI Center</div>
</div>

<script>
function getBrowserId(){
  let id = localStorage.getItem('ai_center_reward_browser_id');
  if(!id){
    if(window.crypto && crypto.randomUUID){
      id = crypto.randomUUID();
    }else{
      id = 'b-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    }
    localStorage.setItem('ai_center_reward_browser_id', id);
  }
  return id;
}

function show(id){
  ['loading','available','redeemed','error'].forEach(x=>{
    document.getElementById(x).classList.toggle('hidden', x !== id);
  });
}

async function checkReward(){
  try{
    const res = await fetch('api/check.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({browser_id:getBrowserId()})
    });

    const data = await res.json();

    if(!res.ok || !data.ok){
      throw new Error(data.message || 'ไม่สามารถตรวจสอบสิทธิ์ได้');
    }

    if(data.status === 'redeemed'){
      document.getElementById('redeemedTime').textContent =
        data.redeemed_at ? 'รับเมื่อ ' + data.redeemed_at : '';
      show('redeemed');
    }else{
      document.getElementById('claimCode').textContent = data.claim_code;
      show('available');
    }
  }catch(err){
    document.getElementById('errorText').textContent = err.message;
    show('error');
  }
}

checkReward();
</script>
</body>
</html>
