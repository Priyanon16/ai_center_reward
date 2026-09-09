<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'ai_center');
define('DB_USER', 'root');
define('DB_PASS', 'Took@66010912608');

define('BASE_URL', 'http://119.10.140.166/ai_center_reward');

define('STAFF_PASSWORD', '1611');

date_default_timezone_set('Asia/Bangkok');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST .
               ';dbname=' . DB_NAME .
               ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}