<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Logger.php';

session_start();

if (!isset($_SESSION['provider_id'])) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE provider_id=? LIMIT 1");
$stmt->execute([$_SESSION['provider_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'enable') {
    session_destroy();
    header("Location: index.php?error=disabled");
    exit;
}

function statusTH($status)
{
    switch ($status) {
        case 'success':
            return 'ส่งสำเร็จ';
        case 'pending':
            return 'บันทึก';
        case 'fail':
            return 'ส่งไม่สำเร็จ';
        default:
            return $status;
    }
}

$hospitalName = env('NAME_HOSPCODES');

$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT nj.*, u.fullname AS creator_name
    FROM notify_jobs nj
    LEFT JOIN users u ON nj.user_id = u.provider_id
    ORDER BY nj.id DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->query("SELECT COUNT(*) FROM notify_jobs")->fetchColumn();
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard -
        <?php echo $hospitalName; ?>
    </title>

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
        <div class="left">ระบบส่ง MOPH Alert -
            <?php echo $hospitalName; ?>
        </div>
        <div class="right">
            ผู้ใช้งาน:
            <?php echo $user['fullname']; ?> |
            หน่วยงาน:
            <?php echo $user['hoscode']; ?> |
            สิทธิ์:
            <?php echo strtoupper($user['role']); ?>

            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin_users.php" class="top-btn admin">Admin</a>
                <a href="logs/system.log" class="top-btn log" target="_blank">Log</a>
            <?php endif; ?>

            <a href="logout.php" class="top-btn logout">ออกจากระบบ</a>
        </div>
    </div>

    <div class="container">

        <h1>Dashboard</h1>
        <a href="notify_form.php" class="top-btn">สร้างข้อความ</a>
        <button class="btn danger" onclick="deleteMulti()">ลบรายการที่เลือก</button>

        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th>ลำดับ</th>
                    <th>ประเภท</th>
                    <th>จำนวน</th>
                    <th>สถานะ</th>
                    <th>สร้างเมื่อ</th>
                    <th>ผู้สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>

            <tbody>
                <?php $rownum = ($page - 1) * $limit + 1; ?>
                <?php foreach ($jobs as $j): ?>
                    <?php
                    $canManage = ($user['role'] === 'admin' || $j['user_id'] == $user['provider_id']);


                    echo "<!-- DEBUG: job_user_id={$j['user_id']} session_provider_id={$_SESSION['provider_id']} -->";



                    $cid_array = json_decode($j['cid_list'], true);
                    $count = is_array($cid_array) ? count($cid_array) : 0;
                    ?>
                    <tr>

                        <td>
                            <?php if ($canManage): ?>
                                <input type="checkbox" name="job_ids[]" value="<?php echo $j['id']; ?>">
                            <?php endif; ?>
                        </td>

                        <td>
                            <center><?php echo $rownum++; ?></center>
                        </td>
                        <td>
                            <?php echo $j['message_type']; ?>
                        </td>
                        <td>
                            <center><?php echo $count; ?></center>
                        </td>
                        <td>
                            <center><?php echo statusTH($j['status']); ?></center>
                        </td>

                        <td>
                            <?php echo $j['created_at']; ?>
                        </td>
                        <td>
                            <?php echo $j['creator_name']; ?>
                        </td>

                        <td>
                            <center>
                                <?php if ($canManage): ?>
                                    <div class="dropdown">
                                        <button class="dropdown-btn" onclick="toggleDropdown(this)">จัดการ ▾</button>
                                        <div class="dropdown-content">
                                            <a href="job_edit.php?id=<?php echo $j['id']; ?>">แก้ไข</a>
                                            <a href="#" onclick="deleteJob(<?php echo $j['id']; ?>)">ลบ</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#aaa;">ไม่มีสิทธิ์</span>
                                <?php endif; ?>
                        </td>
                        </center>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <!-- Modal แก้ไขงาน -->
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h2>แก้ไขงาน</h2>
            <form id="editForm">
                <input type="hidden" name="id" id="edit_id">

                <label>CID ผู้รับ</label>
                <input type="text" name="cid" id="edit_cid">

                <label>หัวข้อข้อความ</label>
                <input type="text" name="message_title" id="edit_title">

                <label>ชื่อรายการ</label>
                <input type="text" name="message_text" id="edit_text">

                <label>HTML</label>
                <textarea name="message_html" id="edit_html"></textarea>

                <label>ประเภทข้อความ</label>
                <input type="text" name="message_type" id="edit_type">

                <label>ข้อความหลัก</label>
                <textarea name="message" id="edit_message"></textarea>

                <button type="button" onclick="saveEdit()">บันทึก</button>
                <button type="button" onclick="sendEdit()">ส่งข้อความ</button>
                <button type="button" onclick="closeModal()">ปิด</button>
            </form>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('input[name="job_ids[]"]').forEach(cb => cb.checked = source.checked);
        }

        function toggleDropdown(btn) {
            const menu = btn.nextElementSibling;
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        document.addEventListener("click", function (e) {
            document.querySelectorAll(".dropdown-content").forEach(menu => {
                if (!menu.parentElement.contains(e.target)) menu.style.display = "none";
            });
        });

        function editJob(id) {
            fetch("job_edit_ajax.php?id=" + id)
                .then(r => r.json())
                .then(d => {
                    document.getElementById("edit_id").value = d.id;
                    document.getElementById("edit_cid").value = d.cid;
                    document.getElementById("edit_title").value = d.message_title;
                    document.getElementById("edit_text").value = d.message_text;
                    document.getElementById("edit_html").value = d.message_html;
                    document.getElementById("edit_type").value = d.message_type;
                    document.getElementById("edit_message").value = d.message;

                    document.getElementById("editModal").style.display = "block";
                });
        }

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
        }

        function saveEdit() {
            const formData = new FormData(document.getElementById("editForm"));
            formData.append("action", "save");

            fetch("job_update_ajax.php", {
                method: "POST",
                body: formData
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    location.reload();
                });
        }

        function sendEdit() {
            const formData = new FormData(document.getElementById("editForm"));
            formData.append("action", "send");

            fetch("job_update_ajax.php", {
                method: "POST",
                body: formData
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    location.reload();
                });
        }

        function deleteJob(id) {
            if (!confirm("ต้องการลบรายการนี้?")) return;

            fetch("job_delete_ajax.php?id=" + id)
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    location.reload();
                });
        }

        function deleteMulti() {
            const all = document.querySelectorAll('input[name="job_ids[]"]:checked');
            if (all.length === 0) {
                alert("กรุณาเลือกรายการ");
                return;
            }

            // ส่งเฉพาะรายการที่มีสิทธิ์ลบ (admin = ทั้งหมด)
            const allowed = [];
            all.forEach(cb => {
                const row = cb.closest("tr");
                const manageCell = row.querySelector(".dropdown-btn");

                if (manageCell || "<?php echo $user['role']; ?>" === "admin") {
                    allowed.push(cb.value);
                }
            });

            if (allowed.length === 0) {
                alert("คุณไม่มีสิทธิ์ลบรายการที่เลือก");
                return;
            }

            if (!confirm("ต้องการลบรายการที่เลือก?")) return;

            fetch("job_delete_multi_ajax.php", {
                method: "POST",
                body: new URLSearchParams({ ids: JSON.stringify(allowed) })
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    location.reload();
                });
        }

    </script>
    <?php include __DIR__ . '/components/footer.php'; ?>
</body>

</html>