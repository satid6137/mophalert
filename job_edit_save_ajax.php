<?php
require_once __DIR__ . '/config/db.php';
header("Content-Type: application/json");

$id = $_POST['id'];

$cid_list = array_map('trim', explode(',', $_POST['cid']));
$message_title = $_POST['message_title'];
$message_text = $_POST['message_text'];
$message_html = $_POST['message_html'];
$message_type = $_POST['message_type'];
$message = $_POST['message'];

$message_json = json_encode([
    [
        "message_title" => $message_title,
        "message_text" => $message_text,
        "message_html" => $message_html,
        "message_type" => $message_type,
        "text" => $message,
        "type" => "text"
    ]
], JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare("
    UPDATE notify_jobs
    SET cid_list=?, message_json=?, message_title=?, message_text=?, message_html=?, message_type=?, message=?, status='pending'
    WHERE id=?
");

$stmt->execute([
    json_encode($cid_list),
    $message_json,
    $message_title,
    $message_text,
    $message_html,
    $message_type,
    $message,
    $id
]);

echo json_encode(["message" => "บันทึกสำเร็จ"]);
