<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['provider_id'])) {
    echo json_encode(["status" => "error", "message" => "Session หมดอายุ"]);
    exit;
}

// รับข้อมูลจาก AJAX
$cid_raw = trim($_POST['cid']);
$cid_list = array_map('trim', explode(',', $cid_raw));

$message_title = trim($_POST['message_title']);
$message_text = trim($_POST['message_text']);
$message_html = trim($_POST['message_html']);
$message_type = trim($_POST['message_type']);
$message = trim($_POST['message']);

// JSON strict
$message_json = json_encode(
    [
        [
            "message_title" => $message_title,
            "message_text" => $message_text,
            "message_html" => $message_html,
            "message_type" => $message_type,
            "text" => $message,
            "type" => "text"
        ]
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

try {

    $stmt = $pdo->prepare("
        INSERT INTO notify_jobs
        (user_id, cid_list, message_json, message_title, message_text, message_html, message_type, message, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['provider_id'],
        json_encode($cid_list),
        $message_json,
        $message_title,
        $message_text,
        $message_html,
        $message_type,
        $message,
        'pending'
    ]);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
