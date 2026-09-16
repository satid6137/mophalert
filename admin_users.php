<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

session_start();

// ตรวจสิทธิ์
if (!isset($_SESSION['provider_id'])) {
    header("Location: index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้ที่ login
$stmt = $pdo->prepare("SELECT * FROM users WHERE provider_id=? LIMIT 1");
$stmt->execute([$_SESSION['provider_id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if ($me['role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าหน้านี้");
}

// Log การเข้า admin
Logger::write("PAGE: admin_users.php loaded", [
    "provider_id" => $me['provider_id'],
    "fullname" => $me['fullname'],
    "hoscode" => $me['hoscode'],
    "role" => $me['role']
]);

// ดึง user ทั้งหมด
$users = $pdo->query("SELECT * FROM users ORDER BY fullname ASC")->fetchAll(PDO::FETCH_ASSOC);

$hospitalName = env('NAME_HOSPCODES');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้งาน - <?php echo $hospitalName; ?></title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="left">
            ระบบส่ง MOPH Alert สำหรับเจ้าหน้าที่<?php echo $hospitalName; ?> : Admin
        </div>

        <div class="right">
            ผู้ใช้งาน: <?php echo $me['fullname']; ?> |
            หน่วยงาน: <?php echo $me['hoscode']; ?> |
            สิทธิ์: <?php echo strtoupper($me['role']); ?>

            <a href="dashboard.php" class="top-btn admin">Dashboard</a>
            <a href="logs/system.log" class="top-btn log" target="_blank">Log</a>
            <a href="logout.php" class="top-btn logout">ออกจากระบบ</a>
        </div>
    </div>

    <div class="container">
        <h1>จัดการผู้ใช้งาน</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>Provider ID</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>หน่วยงาน</th>
                    <th>สิทธิ์</th>
                    <th>สถานะ</th>
                    <th>เข้าใช้งานล่าสุด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $u): ?>

                    <?php
                    // แปลง role เป็นภาษาไทย
                    $role_th = ($u['role'] === 'admin') ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป';

                    // แปลง status เป็นภาษาไทย
                    $status_th = ($u['status'] === 'enable') ? 'เปิดใช้งาน' : 'ถูกปิด';

                    // ปุ่มจัดการสิทธิ์
                    $role_btn_text = ($u['role'] === 'admin') ? 'USER' : 'ADMIN';

                    // ปุ่มจัดการสถานะ
                    $status_btn_text = ($u['status'] === 'enable') ? 'ปิด' : 'เปิด';
                    ?>

                    <tr>
                        <td><?php echo $u['provider_id']; ?></td>
                        <td><?php echo $u['fullname']; ?></td>
                        <td><?php echo $u['hoscode']; ?></td>

                        <!-- สิทธิ์ (ภาษาไทย) -->
                        <td><?php echo $role_th; ?></td>

                        <!-- สถานะ (ภาษาไทย) -->
                        <td><?php echo $status_th; ?></td>

                        <td><?php echo $u['last_login']; ?></td>

                        <!-- ปุ่มจัดการ -->
                        <td>
                            <!-- ปุ่มสลับสิทธิ์ -->
                            <a href="user_toggle_role.php?id=<?php echo $u['id']; ?>"
                                class="btn-small role-<?php echo $u['role']; ?>">
                                <?php echo $role_btn_text; ?>
                            </a>

                            <!-- ปุ่มเปิด/ปิดสถานะ -->
                            <a href="user_toggle_status.php?id=<?php echo $u['id']; ?>"
                                class="btn-small status-<?php echo $u['status']; ?>">
                                <?php echo $status_btn_text; ?>
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

    <?php include __DIR__ . '/components/footer.php'; ?>

</body>

</html>