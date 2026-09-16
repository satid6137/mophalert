<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$id]);
$role = $stmt->fetchColumn();

$newRole = ($role === 'admin') ? 'user' : 'admin';

$update = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
$update->execute([$newRole, $id]);

Logger::write("USER ROLE CHANGED", [
    "id" => $id,
    "new_role" => $newRole
]);

header("Location: admin_users.php");
exit;
