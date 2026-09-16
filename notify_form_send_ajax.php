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

// เตรียม payload ส่งเข้า API หมอพร้อม
$payload = [
    "cid" => $cid_list,
    "messages" => [
        [
            "message_title" => $message_title,
            "message_text" => $message_text,
            "message_html" => $message_html,
            "message_type" => $message_type,
            "text" => $message,
            "type" => "text"
        ]
    ]
];

$send_url = env('MOPH_API_URL') . "/alert/v3.1/messages";

$ch = curl_init($send_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "client-key: " . env('MOPH_CLIENT_KEY'),
    "secret-key: " . env('MOPH_SECRET_KEY')
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

// ตรวจ JSON response
function is_json($string)
{
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}

if (!is_json($response)) {
    $response = json_encode(["raw" => $response], JSON_UNESCAPED_UNICODE);
}

// JSON strict สำหรับ message_json
$message_json = json_encode(
    $payload['messages'],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

// บันทึกลงฐานข้อมูล
try {

    $stmt = $pdo->prepare("
        INSERT INTO notify_jobs
        (user_id, cid_list, message_json, message_title, message_text, message_html, message_type, message, status, response)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        intval($_SESSION['provider_id']),
        json_encode($cid_list),
        $message_json,
        $message_title,
        $message_text,
        $message_html,
        $message_type,
        $message,
        'success',
        $response
    ]);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
