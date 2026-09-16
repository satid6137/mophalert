<?php
require_once __DIR__ . '/config/db.php';
header("Content-Type: application/json");

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM notify_jobs WHERE id=?");
$stmt->execute([$id]);

echo json_encode(["message" => "ลบรายการสำเร็จ"]);
