<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$browserId = trim($input['browser_id'] ?? '');

if ($browserId === '' || strlen($browserId) > 100) {
    json_response(['ok' => false, 'message' => 'browser_id ไม่ถูกต้อง'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, claim_code, status, redeemed_at
    FROM reward_claims
    WHERE browser_id = ?
    LIMIT 1
");
$stmt->execute([$browserId]);
$row = $stmt->fetch();

if (!$row) {
    do {
        $claimCode = strtoupper(substr(bin2hex(random_bytes(6)), 0, 6));
        $check = $pdo->prepare("SELECT id FROM reward_claims WHERE claim_code = ?");
        $check->execute([$claimCode]);
    } while ($check->fetch());

    $insert = $pdo->prepare("
        INSERT INTO reward_claims (browser_id, claim_code, status)
        VALUES (?, ?, 'pending')
    ");
    $insert->execute([$browserId, $claimCode]);

    json_response([
        'ok' => true,
        'status' => 'pending',
        'claim_code' => $claimCode
    ]);
}

json_response([
    'ok' => true,
    'status' => $row['status'],
    'claim_code' => $row['claim_code'],
    'redeemed_at' => $row['redeemed_at']
]);
