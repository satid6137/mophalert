<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/src/Helpers/Logger.php';
require_once __DIR__ . '/src/Auth/HealthID.php';

// Log การเข้าใช้งานหน้าแรก
Logger::write("PAGE: index.php loaded");

// ดึงชื่อโรงพยาบาลจาก .env
$hospitalName = env('NAME_HOSPCODES');

// URL สำหรับ Login Health ID
$loginUrl = HealthID::loginUrl();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title><?php echo $hospitalName; ?> - MOPH Alert</title>

    <!-- ไอคอนระบบ -->
    <link rel="icon" type="image/png" href="assets/icons/health48.png">

    <!-- ฟอนต์ Sarabun -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap">

    <!-- CSS หลัก -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<?php if (isset($_GET['error']) && $_GET['error'] === 'disabled'): ?>
    <div id="disabled-alert" class="disabled-alert">
        บัญชีนี้ถูกปิดการใช้งาน กรุณาติดต่อผู้ดูแลระบบ
    </div>

    <script>
        setTimeout(() => {
            const box = document.getElementById('disabled-alert');
            if (box) {
                box.style.opacity = "0";
                box.style.transition = "opacity 1s";
                setTimeout(() => box.remove(), 1000);
            }
        }, 3000); // 3 วินาที
    </script>
<?php endif; ?>

<body>
    <div class="container" style="text-align:center;">

        <!-- ไอคอนโรงพยาบาล -->
        <img src="assets/icons/logo-MOPH.png" width="300" alt="Hospital Icon">

        <!-- ชื่อโรงพยาบาล -->
        <h1 style="margin-top:20px;">
            <?php echo $hospitalName; ?>
        </h1>

        <!-- ปุ่ม Login Provider -->
        <a href="<?php echo $loginUrl; ?>" class="provider-login-btn">
            <span>เข้าสู่ระบบด้วย ProviderID</span>
            <img src="assets/icons/images-provider.png" alt="ProviderID">
        </a>
    </div>

    <?php include __DIR__ . '/components/footer.php'; ?>
</body>

</html>