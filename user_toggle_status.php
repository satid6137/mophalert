<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
$stmt->execute([$id]);
$status = $stmt->fetchColumn();

$newStatus = ($status === 'enable') ? 'disable' : 'enable';

$update = $pdo->prepare("UPDATE users SET status=? WHERE id=?");
$update->execute([$newStatus, $id]);

Logger::write("USER STATUS CHANGED", [
    "id" => $id,
    "new_status" => $newStatus
]);

header("Location: admin_users.php");
exit;
