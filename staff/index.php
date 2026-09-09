<?php

session_start();

require_once __DIR__ . '/../config.php';

$error = '';

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

if (isset($_POST['logout'])) {

    session_destroy();

    header(
        'Location: index.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if (
    !(
        $_SESSION['staff_logged_in']
        ?? false
    )
) {

    if (
        $_SERVER['REQUEST_METHOD']
        === 'POST'
    ) {

        $password =
            $_POST['password']
            ?? '';

        if (
            hash_equals(
                STAFF_PASSWORD,
                $password
            )
        ) {

            $_SESSION[
                'staff_logged_in'
            ] = true;

            header(
                'Location: index.php'
            );

            exit;
        }

        $error =
            'รหัสผ่านไม่ถูกต้อง';
    }

?>
<!doctype html>
<html lang="th">

<head>

<meta charset="utf-8">

<meta
name="viewport"
content="width=device-width,initial-scale=1"
>

<title>Staff Login</title>

<style>

body{
    font-family:system-ui;
    background:#eef5ff;
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:20px;
}

.box{
    background:#fff;
    padding:28px;
    border-radius:24px;
    max-width:420px;
    width:100%;
    box-shadow:0 15px 40px #0001;
}

h1{
    margin-top:0;
}

input,
button{
    width:100%;
    padding:14px;
    border-radius:12px;
    font-size:16px;
    box-sizing:border-box;
}

input{
    border:1px solid #ccd9e8;
    margin:10px 0;
}

button{
    border:0;
    background:#0b63ce;
    color:white;
    font-weight:800;
    cursor:pointer;
}

.err{
    color:#c22;
    margin-bottom:8px;
}

</style>

</head>

<body>

<form
class="box"
method="post"
>

<h1>
🔐 เจ้าหน้าที่ AI Center
</h1>

<p>
เข้าสู่ระบบเพื่อยืนยันการมอบรางวัล
</p>

<?php
if ($error):
?>

<div class="err">
<?=htmlspecialchars($error)?>
</div>

<?php
endif;
?>

<input
type="password"
name="password"
placeholder="รหัสผ่านเจ้าหน้าที่"
required
autofocus
>

<button type="submit">
เข้าสู่ระบบ
</button>

</form>

</body>

</html>

<?php

    exit;
}

$pdo = db();

/*
|--------------------------------------------------------------------------
| ยืนยันการแจกของรางวัล
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
    &&
    isset(
        $_POST['redeem_code']
    )
) {

    $code =
        strtoupper(
            trim(
                $_POST[
                    'redeem_code'
                ]
            )
        );

    $staffName =
        trim(
            $_POST[
                'staff_name'
            ]
            ?? 'Staff'
        );

    $stmt =
        $pdo->prepare("
            UPDATE reward_claims

            SET
                status='redeemed',
                redeemed_at=NOW(),
                redeemed_by=?

            WHERE claim_code=?
              AND claim_date=CURDATE()
              AND status='pending'
        ");

    $stmt->execute([
        $staffName ?: 'Staff',
        $code
    ]);

    header(
        'Location: index.php?result=' .
        (
            $stmt->rowCount()
            ? 'success'
            : 'notfound'
        )
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| สถิติวันนี้
|--------------------------------------------------------------------------
*/

$todayRedeemed =
    (int)$pdo->query("
        SELECT COUNT(*)

        FROM reward_claims

        WHERE status='redeemed'
          AND claim_date=CURDATE()
    ")
    ->fetchColumn();

$pendingCount =
    (int)$pdo->query("
        SELECT COUNT(*)

        FROM reward_claims

        WHERE status='pending'
          AND claim_date=CURDATE()
    ")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| สถิติรวม
|--------------------------------------------------------------------------
*/

$totalRedeemed =
    (int)$pdo->query("
        SELECT COUNT(*)

        FROM reward_claims

        WHERE status='redeemed'
    ")
    ->fetchColumn();

$totalClaims =
    (int)$pdo->query("
        SELECT COUNT(*)

        FROM reward_claims
    ")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| รายการรอยืนยันวันนี้
|--------------------------------------------------------------------------
*/

$pending =
    $pdo->query("
        SELECT
            claim_code,
            created_at

        FROM reward_claims

        WHERE status='pending'
          AND claim_date=CURDATE()

        ORDER BY
            created_at DESC

        LIMIT 30
    ")
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| ประวัติการรับ
|--------------------------------------------------------------------------
*/

$history =
    $pdo->query("
        SELECT
            claim_code,
            claim_date,
            redeemed_at,
            redeemed_by

        FROM reward_claims

        WHERE status='redeemed'

        ORDER BY
            redeemed_at DESC

        LIMIT 50
    ")
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| เจ้าหน้าที่ที่แจกวันนี้
|--------------------------------------------------------------------------
*/

$staffSummary =
    $pdo->query("
        SELECT
            COALESCE(
                NULLIF(
                    redeemed_by,
                    ''
                ),
                'Staff'
            ) AS staff_name,

            COUNT(*) AS total

        FROM reward_claims

        WHERE status='redeemed'
          AND claim_date=CURDATE()

        GROUP BY
            staff_name

        ORDER BY
            total DESC
    ")
    ->fetchAll();

?>
<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta
name="viewport"
content="width=device-width,initial-scale=1"
>

<title>
AI Center Reward Dashboard
</title>

<style>

:root{
  --blue:#0b63ce;
  --bg:#f3f7fb;
  --line:#dbe5ef;
  --text:#17324d;
  --muted:#718399;
  --green:#12855a;
  --orange:#d07a12;
}

*{
  box-sizing:border-box;
}

body{
  margin:0;
  background:var(--bg);
  font-family:
  system-ui,
  -apple-system,
  "Segoe UI",
  Tahoma,
  sans-serif;
  color:var(--text);
}

.wrap{
  max-width:1180px;
  margin:auto;
  padding:20px;
}

.top,
.card,
.stat-card{
  background:#fff;
  border-radius:20px;
  box-shadow:
  0 8px 30px #1d4d7a12;
}

.top{
  padding:20px;
  margin-bottom:16px;
  display:flex;
  gap:14px;
  justify-content:space-between;
  align-items:center;
  flex-wrap:wrap;
}

h1,
h2{
  margin:0;
}

.sub{
  color:var(--muted);
  margin-top:5px;
}

button{
  border:0;
  border-radius:12px;
  padding:12px 16px;
  background:var(--blue);
  color:#fff;
  font-weight:800;
  cursor:pointer;
}

button.secondary{
  background:#64748b;
}

.stats{
  display:grid;
  grid-template-columns:
  repeat(4,1fr);
  gap:14px;
  margin-bottom:16px;
}

.stat-card{
  padding:20px;
}

.stat-label{
  font-size:14px;
  color:var(--muted);
  margin-bottom:7px;
}

.stat-number{
  font-size:32px;
  font-weight:900;
}

.green{
  color:var(--green);
}

.orange{
  color:var(--orange);
}

.blue{
  color:var(--blue);
}

.grid{
  display:grid;
  grid-template-columns:
  1.05fr .95fr;
  gap:16px;
  margin-bottom:16px;
}

.card{
  padding:20px;
}

.card-head{
  display:flex;
  justify-content:
  space-between;
  align-items:center;
  gap:10px;
  margin-bottom:14px;
}

form.inline{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

input{
  padding:13px;
  border:
  1px solid var(--line);
  border-radius:12px;
  font-size:16px;
}

.msg{
  padding:12px 15px;
  border-radius:12px;
  margin-bottom:14px;
}

.ok{
  background:#eaf9f1;
  color:#107044;
}

.bad{
  background:#fff0f0;
  color:#b72b2b;
}

.row{
  display:flex;
  align-items:center;
  justify-content:
  space-between;
  gap:12px;
  padding:13px 0;
  border-bottom:
  1px solid var(--line);
}

.row:last-child{
  border-bottom:0;
}

.code{
  font-size:22px;
  font-weight:900;
  letter-spacing:2px;
  color:var(--blue);
}

.time{
  font-size:13px;
  color:var(--muted);
  margin-top:3px;
}

.table-wrap{
  overflow:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:680px;
}

th,
td{
  padding:12px 10px;
  border-bottom:
  1px solid var(--line);
  text-align:left;
}

th{
  font-size:13px;
  color:var(--muted);
  background:#f8fafc;
}

.badge{
  display:inline-block;
  padding:5px 9px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  background:#eaf9f1;
  color:#107044;
}

.staff-item{
  display:flex;
  justify-content:
  space-between;
  gap:10px;
  padding:10px 0;
  border-bottom:
  1px solid var(--line);
}

.staff-item:last-child{
  border-bottom:0;
}

.empty{
  color:var(--muted);
  padding:10px 0;
}

.refresh{
  text-decoration:none;
  color:var(--blue);
  font-weight:800;
  font-size:14px;
}

@media(
max-width:900px
){

  .stats{
    grid-template-columns:
    repeat(2,1fr);
  }

  .grid{
    grid-template-columns:
    1fr;
  }
}

@media(
max-width:560px
){

  .stats{
    grid-template-columns:
    1fr;
  }

  .row{
    align-items:flex-start;
    flex-direction:column;
  }

  .row form,
  .row button{
    width:100%;
  }
}

</style>

</head>

<body>

<div class="wrap">

<div class="top">

<div>

<h1>
🎁 AI Center Reward Dashboard
</h1>

<div class="sub">
1 Browser / 1 สิทธิ์ / วัน
</div>

</div>

<div
style="
display:flex;
gap:10px;
align-items:center;
flex-wrap:wrap;
"
>

<a
class="refresh"
href="index.php"
>
↻ รีเฟรชข้อมูล
</a>

<form method="post">

<button
class="secondary"
name="logout"
value="1"
>
ออกจากระบบ
</button>

</form>

</div>

</div>

<?php
if (
    ($_GET['result'] ?? '')
    === 'success'
):
?>

<div class="msg ok">
✅ ยืนยันการมอบรางวัลเรียบร้อย
</div>

<?php
elseif (
    ($_GET['result'] ?? '')
    === 'notfound'
):
?>

<div class="msg bad">
❌ ไม่พบรหัสของวันนี้ หรือรหัสถูกใช้ไปแล้ว
</div>

<?php
endif;
?>

<div class="stats">

<div class="stat-card">

<div class="stat-label">
แจกวันนี้
</div>

<div class="stat-number green">
<?=number_format($todayRedeemed)?>
</div>

<div class="time">
รายการ
</div>

</div>

<div class="stat-card">

<div class="stat-label">
กำลังรอยืนยันวันนี้
</div>

<div class="stat-number orange">
<?=number_format($pendingCount)?>
</div>

<div class="time">
รายการ
</div>

</div>

<div class="stat-card">

<div class="stat-label">
แจกแล้วทั้งหมด
</div>

<div class="stat-number blue">
<?=number_format($totalRedeemed)?>
</div>

<div class="time">
รายการ
</div>

</div>

<div class="stat-card">

<div class="stat-label">
สิทธิ์ที่สร้างทั้งหมด
</div>

<div class="stat-number">
<?=number_format($totalClaims)?>
</div>

<div class="time">
ทุกวันรวมกัน
</div>

</div>

</div>

<div class="grid">

<div class="card">

<div class="card-head">

<div>

<h2>
ยืนยันรหัสรับของรางวัล
</h2>

<div class="sub">
ใช้ได้เฉพาะรหัสที่สร้างวันนี้
</div>

</div>

</div>

<form
class="inline"
method="post"
>

<input
type="text"
name="redeem_code"
maxlength="12"
placeholder="เช่น A1B2C3"
required
autofocus
>

<input
type="text"
name="staff_name"
maxlength="100"
placeholder="ชื่อเจ้าหน้าที่"
>

<button type="submit">
ยืนยันมอบรางวัล
</button>

</form>

</div>

<div class="card">

<div class="card-head">

<div>

<h2>
เจ้าหน้าที่ที่แจกวันนี้
</h2>

<div class="sub">
สรุปจำนวนตามชื่อผู้ยืนยัน
</div>

</div>

</div>

<?php
if (!$staffSummary):
?>

<div class="empty">
ยังไม่มีการแจกของรางวัลวันนี้
</div>

<?php
else:
?>

<?php
foreach (
    $staffSummary
    as $s
):
?>

<div class="staff-item">

<strong>
<?=htmlspecialchars(
    $s['staff_name']
)?>
</strong>

<span>
<?=number_format(
    (int)$s['total']
)?>
รายการ
</span>

</div>

<?php
endforeach;
?>

<?php
endif;
?>

</div>

</div>

<div class="grid">

<div class="card">

<div class="card-head">

<div>

<h2>
รอยืนยันวันนี้
</h2>

<div class="sub">
แสดงสูงสุด 30 รายการ
</div>

</div>

</div>

<?php
if (!$pending):
?>

<div class="empty">
ไม่มีผู้รอรับรางวัลวันนี้
</div>

<?php
else:
?>

<?php
foreach (
    $pending
    as $item
):
?>

<div class="row">

<div>

<div class="code">
<?=htmlspecialchars(
    $item['claim_code']
)?>
</div>

<div class="time">
สร้างสิทธิ์:
<?=htmlspecialchars(
    $item['created_at']
)?>
</div>

</div>

<form method="post">

<input
type="hidden"
name="redeem_code"
value="<?=htmlspecialchars(
    $item['claim_code']
)?>"
>

<button type="submit">
ยืนยันมอบรางวัล
</button>

</form>

</div>

<?php
endforeach;
?>

<?php
endif;
?>

</div>

<div class="card">

<div class="card-head">

<div>

<h2>
ประวัติการรับล่าสุด
</h2>

<div class="sub">
รวมทุกวัน สูงสุด 50 รายการ
</div>

</div>

</div>

<?php
if (!$history):
?>

<div class="empty">
ยังไม่มีประวัติการรับของรางวัล
</div>

<?php
else:
?>

<div class="table-wrap">

<table>

<thead>

<tr>

<th>
รหัส
</th>

<th>
วันที่สิทธิ์
</th>

<th>
เวลารับ
</th>

<th>
เจ้าหน้าที่
</th>

</tr>

</thead>

<tbody>

<?php
foreach (
    $history
    as $h
):
?>

<tr>

<td>

<strong>
<?=htmlspecialchars(
    $h['claim_code']
)?>
</strong>

</td>

<td>
<?=htmlspecialchars(
    $h['claim_date']
)?>
</td>

<td>
<?=htmlspecialchars(
    $h['redeemed_at']
    ?? '-'
)?>
</td>

<td>
<?=htmlspecialchars(
    $h['redeemed_by']
    ?: 'Staff'
)?>
</td>

</tr>

<?php
endforeach;
?>

</tbody>

</table>

</div>

<?php
endif;
?>

</div>

</div>

</div>

</body>

</html>