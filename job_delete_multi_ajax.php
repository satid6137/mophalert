<?php
require_once __DIR__ . '/config/db.php';
header("Content-Type: application/json");

$ids = json_decode($_POST['ids'], true);

$in = str_repeat('?,', count($ids) - 1) . '?';

$stmt = $pdo->prepare("DELETE FROM notify_jobs WHERE id IN ($in)");
$stmt->execute($ids);

echo json_encode(["message" => "ลบหลายรายการสำเร็จ"]);
