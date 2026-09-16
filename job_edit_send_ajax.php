<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

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

$payload = [
    "cid" => $cid_list,
    "messages" => json_decode($message_json, true)
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

$stmt = $pdo->prepare("
    UPDATE notify_jobs
    SET status='success', response=?
    WHERE id=?
");
$stmt->execute([$response, $id]);

echo json_encode(["message" => "ส่งข้อความสำเร็จ"]);
