<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

session_start();

// ตรวจ session
if (!isset($_SESSION['provider_id'])) {
    header("Location: index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users WHERE provider_id=? LIMIT 1");
$stmt->execute([$_SESSION['provider_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'enable') {
    session_destroy();
    header("Location: index.php?error=disabled");
    exit;
}

// Log
Logger::write("PAGE: notify_form.php loaded", [
    "provider_id" => $user['provider_id'],
    "fullname" => $user['fullname'],
    "hoscode" => $user['hoscode'],
    "role" => $user['role']
]);

$hospitalName = env('NAME_HOSPCODES');

$cid_value = isset($_GET['cid']) ? $_GET['cid'] : '';
$message_title_val = isset($_GET['message_title']) ? $_GET['message_title'] : '';
$message_text_val = isset($_GET['message_text']) ? $_GET['message_text'] : '';
$message_html_val = isset($_GET['message_html']) ? $_GET['message_html'] : '';
$message_type_val = isset($_GET['message_type']) ? $_GET['message_type'] : '';
$message_val = isset($_GET['message']) ? $_GET['message'] : '';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สร้างข้อความส่งผ่านหมอพร้อม - <?php echo $hospitalName; ?></title>

    <!-- ฟอนต์ Sarabun -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- ไอคอน -->
    <link rel="icon" type="image/png" href="assets/icons/health48.png">
</head>

<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="left">
            ระบบส่ง MOPH Alert สำหรับเจ้าหน้าที่<?php echo $hospitalName; ?> : สร้างข้อความ
        </div>

        <div class="right">
            ผู้ใช้งาน: <?php echo $user['fullname']; ?> |
            หน่วยงาน: <?php echo $user['hoscode']; ?> |
            สิทธิ์: <?php echo strtoupper($user['role']); ?>

            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin_users.php" class="top-btn admin">Admin</a>
            <?php endif; ?>

            <a href="dashboard.php" class="top-btn dashboard">กลับ</a>
            <a href="logs/system.log" class="top-btn log" target="_blank">Log</a>
            <a href="logout.php" class="top-btn logout">ออกจากระบบ</a>
        </div>
    </div>

    <div class="container">

        <h1>ส่งข้อความผ่านหมอพร้อม</h1>

        <form action="notify_form_save.php" method="POST">

            <label>CID ผู้รับ (คั่นด้วย ,)</label>
            <input type="text" name="cid" required value="<?php echo htmlspecialchars($cid_value); ?>">

            <label>หัวข้อข้อความ (message_title)</label>
            <input type="text" name="message_title" value="<?php echo htmlspecialchars($message_title_val); ?>">

            <label>ชื่อรายการในกล่องข้อความ (message_text)</label>
            <input type="text" name="message_text" value="<?php echo htmlspecialchars($message_text_val); ?>">

            <label>ข้อความ HTML (message_html)</label>
            <textarea name="message_html" rows="4"><?php echo htmlspecialchars($message_html_val); ?></textarea>

            <label>ประเภทข้อความ (message_type)</label>
            <input type="text" name="message_type" value="<?php echo htmlspecialchars($message_type_val); ?>">

            <label>ข้อความหลัก (text)</label>
            <textarea name="message" rows="5" required><?php echo htmlspecialchars($message_val); ?></textarea>

            <button type="button" onclick="saveDraft()">บันทึก</button>
            <button type="button" onclick="sendMessage()">ส่งข้อความ</button>
            <button type="button" onclick="location.href='dashboard.php'">ยกเลิก</button>

        </form>

    </div>

    <script>
        function saveDraft() {

            // เก็บข้อมูลจากฟอร์ม
            const formData = new FormData();
            formData.append("cid", document.querySelector('input[name="cid"]').value);
            formData.append("message_title", document.querySelector('input[name="message_title"]').value);
            formData.append("message_text", document.querySelector('input[name="message_text"]').value);
            formData.append("message_html", document.querySelector('textarea[name="message_html"]').value);
            formData.append("message_type", document.querySelector('input[name="message_type"]').value);
            formData.append("message", document.querySelector('textarea[name="message"]').value);

            // ส่ง AJAX ไป notify_form_save_ajax.php
            fetch("notify_form_save_ajax.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(result => {

                    if (result.status === "success") {
                        alert("บันทึกข้อมูลสำเร็จ");
                    } else {
                        alert("บันทึกไม่สำเร็จ: " + result.message);
                    }

                })
                .catch(error => {
                    alert("เกิดข้อผิดพลาดในการบันทึก");
                    console.error(error);
                });
        }
    </script>

    <script>
        function sendMessage() {

            const formData = new FormData();
            formData.append("cid", document.querySelector('input[name="cid"]').value);
            formData.append("message_title", document.querySelector('input[name="message_title"]').value);
            formData.append("message_text", document.querySelector('input[name="message_text"]').value);
            formData.append("message_html", document.querySelector('textarea[name="message_html"]').value);
            formData.append("message_type", document.querySelector('input[name="message_type"]').value);
            formData.append("message", document.querySelector('textarea[name="message"]').value);

            fetch("notify_form_send_ajax.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(result => {

                    if (result.status === "success") {
                        alert("ส่งข้อความสำเร็จ");
                    } else {
                        alert("ส่งข้อความไม่สำเร็จ: " + result.message);
                    }

                })
                .catch(error => {
                    alert("เกิดข้อผิดพลาดในการส่งข้อความ");
                    console.error(error);
                });
        }
    </script>

    <?php include __DIR__ . '/components/footer.php'; ?>
</body>

</html>