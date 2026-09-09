<?php

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    json_response([
        'ok' => false,
        'message' => 'Method not allowed'
    ], 405);
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$browserId = trim(
    $input['browser_id'] ?? ''
);

if (
    $browserId === '' ||
    strlen($browserId) > 100
) {

    json_response([
        'ok' => false,
        'message' => 'browser_id ไม่ถูกต้อง'
    ], 422);
}

$pdo = db();

/*
|--------------------------------------------------------------------------
| ตรวจสิทธิ์ของ Browser นี้เฉพาะ "วันนี้"
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        claim_code,
        claim_date,
        status,
        redeemed_at
    FROM reward_claims
    WHERE browser_id = ?
      AND claim_date = CURDATE()
    LIMIT 1
");

$stmt->execute([
    $browserId
]);

$row = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| ถ้ายังไม่มีสิทธิ์ของวันนี้
|--------------------------------------------------------------------------
*/

if (!$row) {

    do {

        $claimCode =
            strtoupper(
                substr(
                    bin2hex(
                        random_bytes(6)
                    ),
                    0,
                    6
                )
            );

        $check =
            $pdo->prepare("
                SELECT id
                FROM reward_claims
                WHERE claim_code = ?
                LIMIT 1
            ");

        $check->execute([
            $claimCode
        ]);

    } while (
        $check->fetch()
    );

    $insert =
        $pdo->prepare("
            INSERT INTO reward_claims
            (
                browser_id,
                claim_code,
                claim_date,
                status
            )
            VALUES
            (
                ?,
                ?,
                CURDATE(),
                'pending'
            )
        ");

    $insert->execute([
        $browserId,
        $claimCode
    ]);

    json_response([
        'ok' => true,
        'status' => 'pending',
        'claim_code' => $claimCode,
        'claim_date' => date('Y-m-d')
    ]);
}

/*
|--------------------------------------------------------------------------
| ถ้ามีสิทธิ์ของวันนี้อยู่แล้ว
|--------------------------------------------------------------------------
*/

json_response([
    'ok' => true,
    'status' => $row['status'],
    'claim_code' => $row['claim_code'],
    'claim_date' => $row['claim_date'],
    'redeemed_at' => $row['redeemed_at']
]);