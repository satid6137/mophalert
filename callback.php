<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';

require_once __DIR__ . '/src/Auth/HealthID.php';
require_once __DIR__ . '/src/Auth/ProviderID.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

session_start();

if (!isset($_GET['code'])) {
    die("Invalid callback");
}

$code = $_GET['code'];

// 1) Health ID Token
$health = HealthID::getAccessToken($code);
$healthToken = $health['data']['access_token'] ?? null;

if (!$healthToken) {
    Logger::write("HealthID: Token failed", $health);
    die("Health ID login failed");
}

// 2) Provider ID Token
$provider = ProviderID::getProviderToken($healthToken);
$providerToken = $provider['data']['access_token'] ?? null;

if (!$providerToken) {
    Logger::write("ProviderID: Token failed", $provider);
    die("You are not Provider");
}

// 3) Profile
$profile = ProviderID::getProfile($providerToken);

$provider_id = $profile['data']['provider_id'];
$fullname = $profile['data']['name_th'];
$hoscode = $profile['data']['organization'][0]['hcode'];

$allowed = explode(",", env('ALLOWED_HOSPCODES'));

if (!in_array($hoscode, $allowed)) {
    Logger::write("LOGIN BLOCKED: HOSCODE NOT ALLOWED", [
        "provider_id" => $provider_id,
        "hoscode" => $hoscode,
        "allowed" => $allowed
    ]);
    die("หน่วยงานของคุณไม่ได้รับอนุญาตให้ใช้งานระบบนี้");
}

// 4) DB
$stmt = $pdo->prepare("SELECT id FROM users WHERE provider_id=?");
$stmt->execute([$provider_id]);

if ($stmt->rowCount() == 0) {
    $insert = $pdo->prepare("INSERT INTO users(provider_id, fullname, hoscode, status, role, last_login) VALUES(?,?,?, 'enable', 'user', NOW())");
    $insert->execute([$provider_id, $fullname, $hoscode]);
    Logger::write("User created", ["provider_id" => $provider_id]);
} else {
    $update = $pdo->prepare("UPDATE users SET fullname=?, hoscode=?, last_login=NOW() WHERE provider_id=?");
    $update->execute([$fullname, $hoscode, $provider_id]);
    Logger::write("User updated", ["provider_id" => $provider_id]);
}

// ดึงข้อมูล user หลังจาก insert/update
$stmt = $pdo->prepare("SELECT * FROM users WHERE provider_id=? LIMIT 1");
$stmt->execute([$provider_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ตรวจสถานะผู้ใช้งาน
if ($user['status'] !== 'enable') {

    Logger::write("LOGIN BLOCKED - USER DISABLED", [
        "provider_id" => $provider_id,
        "fullname" => $user['fullname'],
        "hoscode" => $user['hoscode']
    ]);

    session_destroy();
    header("Location: index.php?error=disabled");
    exit;
}

$_SESSION['provider_id'] = $provider_id;

header("Location: dashboard.php");
exit;