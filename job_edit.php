<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if (!isset($_SESSION['provider_id'])) {
    header("Location: index.php");
    exit;
}

$provider_id = $_SESSION['provider_id'];
$role = $_SESSION['role'] ?? 'user';

$id = $_GET['id'] ?? null;
if (!$id)
    die("ไม่พบ ID");

$stmt = $pdo->prepare("SELECT * FROM notify_jobs WHERE id=?");
$stmt->execute([$id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job)
    die("ไม่พบงานในระบบ");

// ดึง role จากฐานข้อมูล
$stmtUser = $pdo->prepare("SELECT role FROM users WHERE provider_id=? LIMIT 1");
$stmtUser->execute([$provider_id]);
$role = strtolower($stmtUser->fetchColumn());

// ตรวจสิทธิ์
if ($role !== 'admin' && $job['user_id'] !== $provider_id) {
    echo "
    <div style='
        margin: 40px auto;
        width: 400px;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-family: Sarabun, sans-serif;
        text-align: center;
    '>
        <h3 style='color:#c0392b;'>คุณไม่มีสิทธิ์แก้ไขงานนี้</h3>
        <p>ระบบจะพาคุณกลับไปหน้า Dashboard</p>
        <a href='dashboard.php' 
           style='display:inline-block;margin-top:15px;padding:8px 16px;background:#3498db;color:#fff;border-radius:4px;text-decoration:none;'>
           กลับไป Dashboard
        </a>
    </div>

    <script>
        setTimeout(function(){
            window.location.href = 'dashboard.php';
        }, 3000);
    </script>
    ";
    exit;
}

// เตรียมข้อมูล
$cid = implode(",", json_decode($job['cid_list'], true));
$msg = json_decode($job['message_json'], true)[0];

$hospitalName = env('NAME_HOSPCODES');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อความส่งผ่านหมอพร้อม - <?php echo $hospitalName; ?></title>

    <!-- ไอคอนระบบ -->
    <link rel="icon" type="image/png" href="assets/icons/health48.png">

    <!-- ฟอนต์ Sarabun -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap">

    <!-- CSS หลัก -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="topbar">
        <div class="left">ระบบส่ง MOPH Alert สำหรับเจ้าหน้าที่<?php echo $hospitalName; ?> : แก้ไขข้อความ</div>
        <div class="right">
            <a href="dashboard.php" class="top-btn">กลับ</a>
            <a href="logout.php" class="top-btn logout">ออกจากระบบ</a>
        </div>
    </div>

    <div class="container">

        <h1>แก้ไขข้อความ ID :
            <?php echo $id; ?>
        </h1>

        <form id="editForm">

            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <label>CID ผู้รับ (คั่นด้วย ,)</label>
            <input type="text" name="cid" value="<?php echo $cid; ?>">

            <label>หัวข้อข้อความ (message_title)</label>
            <input type="text" name="message_title" value="<?php echo $msg['message_title']; ?>">

            <label>ชื่อรายการในกล่องข้อความ (message_text)</label>
            <input type="text" name="message_text" value="<?php echo $msg['message_text']; ?>">

            <label>ข้อความ HTML (message_html)</label>
            <textarea name="message_html"><?php echo $msg['message_html']; ?></textarea>

            <label>ประเภทข้อความ (message_type)</label>
            <input type="text" name="message_type" value="<?php echo $msg['message_type']; ?>">

            <label>ข้อความหลัก (text)</label>
            <textarea name="message"><?php echo $msg['text']; ?></textarea>

            <button type="button" onclick="saveEdit()">บันทึก</button>
            <button type="button" onclick="sendEdit()">ส่งข้อความ</button>
            <button type="button" onclick="location.href='dashboard.php'">ยกเลิก</button>

        </form>

    </div>

    <script>
        function getFormData() {
            return new FormData(document.getElementById("editForm"));
        }

        function saveEdit() {
            const fd = getFormData();
            fd.append("action", "save");

            fetch("job_edit_save_ajax.php", {
                method: "POST",
                body: fd
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                });
        }

        function sendEdit() {
            const fd = getFormData();
            fd.append("action", "send");

            fetch("job_edit_send_ajax.php", {
                method: "POST",
                body: fd
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                });
        }
    </script>
    <?php include __DIR__ . '/components/footer.php'; ?>
</body>

</html>