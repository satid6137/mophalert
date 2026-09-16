<?php
require_once __DIR__ . '/env.php';

try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST') . ";port=" . env('DB_PORT') . ";dbname=" . env('DB_NAME'),
        env('DB_USER'),
        env('DB_PASS'),
        [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
    );
} catch (Exception $e) {
    error_log("DB ERROR: " . $e->getMessage());
    die("DB ERROR: " . $e->getMessage());
    //die("Database connection failed");
}
