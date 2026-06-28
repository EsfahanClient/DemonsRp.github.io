<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['admin_level'] < 1) { header("Location: index.php"); exit(); }

$conn = new mysqli("localhost", "root", "", "Dsite3");
if ($conn->connect_error) { die("اتصال ناموفق"); }

$admin_id = $_SESSION['user_id'];
$admin_lvl = $_SESSION['admin_level'];
$admin_username = $_SESSION['username'];

$rank_name = ($admin_lvl==4)?'Director':(($admin_lvl==3)?'Ceo':(($admin_lvl==2)?'Junior':'Trial'));
$msg = "";

// تعیین موضوعات مجاز تیکت بر اساس لول ادمین
$allowed_subjects = [];
if ($admin_lvl == 4) { $allowed_subjects = ['خرید', 'باگ و مشکلات سرور', 'شکایت از پلیر', 'عضویت در استف / شکایت از استف']; }
elseif ($admin_lvl == 3) { $allowed_subjects = ['باگ و مشکلات سرور', 'شکایت از پلیر', 'عضویت در استف / شکایت از استف']; }
elseif ($admin_lvl == 2) { $allowed_subjects = ['باگ و مشکلات سرور', 'شکایت از پلیر']; }
elseif ($admin_lvl == 1) { $allowed_subjects = ['شکایت از پلیر']; }
$subject_string = "'" . implode("','", $allowed_subjects) . "'";

// بستن تیکت
if (isset($_GET['close_ticket'])) {
    $close_id = intval($_GET['close_ticket']);
    $conn->query("UPDATE tickets SET status='closed' WHERE id=$close_id");
    header("Location: admin.php?tab=tickets");
    exit();
}

// ارسال پاسخ ادمین به تیکت
if (isset($_POST['admin_reply'])) {
    $t_id = intval($_POST['ticket_id']);
    $reply = mysqli_real_escape_string($conn, $_POST['reply_message']);
    if (!empty($reply)) {
        $conn->query("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES ($t_id, $admin_id, '$reply')");
        header("Location: admin.php?view_ticket=$t_id");
        exit();
    }
}

// قفل و دسترسی چت (فقط دایرکتور)
if (isset($_POST['update_chat_rules']) && $admin_lvl == 4) {
    $status = $_POST['chat_status'];
    $min_lvl = intval($_POST['min_chat_level']);
    $conn->query("UPDATE chat_settings SET setting_value='$status' WHERE setting_key='chat_status'");
    $conn->query("UPDATE chat_settings SET setting_value='$min_lvl' WHERE setting_key='min_chat_level'");
    $msg = "تنظیمات چت با موفقیت بروزرسانی شد.";
}

// تغییر رنک ادمین‌ها
if (isset($_POST['submit_admin']) && $admin_lvl >= 3) {
    $target_user = mysqli_real_escape_string($conn, $_POST['target_user']);
    $assign_lvl = intval($_POST['assign_lvl']);
    if ($assign_lvl >= $admin_lvl && $admin_lvl != 4) {
        $msg = "خطا: سطح دسترسی غیرمجاز.";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE username='$target_user'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE users SET admin_level=$assign_lvl WHERE username='$target_user'");
            $msg = "رنک کاربر مورد نظر تغییر کرد.";
        } else { $msg = "کاربر یافت نشد."; }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پنل مدیریت | Demons RP</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: #030712; color: #f3f4f6; display: flex; min-height: 100vh; }
        
        /* سایدبار شیشه‌ای نئون */
        .sidebar { width: 300px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border-left: 1px solid rgba(0, 240, 255, 0.15); padding: 40px 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar h2 { font-size: 24px; color: #00f0ff; text-shadow: 0 0 15px rgba(0, 240, 255, 0.5); margin-bottom: 5px; }
        .sidebar .rank { font-size: 13px; color: #9ca3af; margin-bottom: 30px; display: block; }
        
        .menu-item { display: block; padding: 14px 18px; color: #9ca3af; text-decoration: none; border-radius: 10px; cursor: pointer; transition: 0.3s all ease; font-weight: bold; }
        .menu-item:hover, .menu-item.active { background: linear-gradient(90deg, rgba(0, 240, 255, 0.15), transparent); color: #fff; border-right: 4px solid #00f0ff; box-shadow: -5px 0 15px rgba(0, 240, 255, 0.05); }
        
        /* باکس محتوا */
        .content { flex: 1; padding: 50px; background: radial-gradient(circle at top left, rgba(0, 85, 255, 0.05), transparent); }
        .panel-section { display: none; }
        .panel-section.active { display: block; animation: fadeIn 0.4s ease; }
        
        h3 { font-size: 26px; margin-bottom: 25px; color: #00f0ff; text-shadow: 0 0 10px rgba(0, 240, 255, 0.2); border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; }
        
        /* جداول شیک مدرن */
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 15px; }
        table th { background: #0f172a; color: #00f0ff; padding: 16px; font-weight: bold; border-bottom: 2px solid rgba(0, 240, 255, 0.2); }
        table td { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(5px); padding: 16px; text-align: center; color: #e5e7eb; transition: 0.2s; }
        table tr:hover td { background: rgba(30, 41, 59, 0.8); color: #fff; }
        table tr td:first-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        table tr td:last-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        
        /* المان‌های فرم */
        .form-group { margin-bottom: 25px; max-width: 500px; }
        .form-group label { display: block; margin-bottom: 10px; color: #9ca3af; font-size: 14px; }
        .form-group input, .form-group select, textarea { width: 100%; padding: 14px; background: #090d16; border: 1px solid rgba(0, 240, 255, 0.2); border-radius: 10px; color: #fff; outline: none; transition: 0.3s; }
        .form-group input:focus, .form-group select:focus, textarea:focus { border-color: #00f0ff; box-shadow: 0 0 15px rgba(0, 240, 255, 0.2); }
        
        .btn { padding: 12px 28px; background: linear-gradient(135deg, #0055ff, #00f0ff); border: none; border-radius: 10px; color: #fff; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; box-shadow: 0 4px 20px rgba(0, 240, 255, 0.2); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(0, 240, 255, 0.4); }
        .btn-close { background: #ef4444; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); margin-right: 10px; }
        .btn-close:hover { background: #dc2626; box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5); }
        
        /* چت تیکت */
        .chat-view { background: #090d16; border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; max-height: 450px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; }
        .msg-text { background: rgba(255,255,255,0.03); padding: 14px; border-radius: 10px; border-right: 4px solid #00f0ff; margin-top: 5px; line-height: 1.7; }
        .admin-msg-box .msg-text { border-right-color: #ef4444; background: rgba(239, 68, 68, 0.02); }
        
        .alert { background: rgba(0, 240, 255, 0.1); border: 1px solid #00f0ff; color: #00f0ff; padding: 15px; border-radius: 10px; margin-bottom: 25px; font-weight: bold; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Demons Admin</h2>
        <span class="rank">مقام شما: <?= $rank_name ?></span>
        
        <div class="menu-item <?= isset($_GET['view_ticket']) || (isset($_GET['tab']) && $_GET['tab']=='tickets') ? 'active' : '' ?>" onclick="location.href='admin.php?tab=tickets'">🎫 تیکت‌های پشتیبانی</div>
        <div class="menu-item <?= !isset($_GET['view_ticket']) && (!isset($_GET['tab']) || $_GET['tab']=='admins') ? 'active' : '' ?>" onclick="location.href='admin.php?tab=admins'">👥 لیست تیم مدیریت</div>
        
        <?php if($admin_lvl == 4): ?>
            <div class="menu-item <?= (isset($_GET['tab']) && $_GET['tab']=='chat-manage') ? 'active' : '' ?>" onclick="location.href='admin.php?tab=chat-manage'">⚙️ مدیریت قفل چت</div>
        <?php endif; ?>
        
        <?php if($admin_lvl >= 3): ?>
            <div class="menu-item <?= (isset($_GET['tab']) && $_GET['tab']=='make-admin') ? 'active' : '' ?>" onclick="location.href='admin.php?tab=make-admin'">👑 مدیریت مقامات ادمینی</div>
        <?php endif; ?>
        
        <a href="index.php" style="color: #6b7280; text-decoration: none; margin-top: auto; font-weight: bold; text-align: center;">بازگشت به سایت ←</a>
    </div>

    <div class="content">
        <?php if($msg): ?> <div class="alert"><?= $msg ?></div> <?php endif; ?>

        <!-- بخش مشاهده و پاسخ زنده به یک تیکت -->
        <?php if (isset($_GET['view_ticket'])): 
            $t_id = intval($_GET['view_ticket']);
            $t_check = $conn->query("SELECT * FROM tickets WHERE id=$t_id AND subject IN ($subject_string) AND status='open'");
            if($t_check->num_rows == 0) { echo "<h3>تیکت یافت نشد یا دسترسی رنک شما مجاز نیست.</h3>"; } else {
                $ticket_info = $t_check->fetch_assoc();
            ?>
                <h3>پاسخ به تیکت #<?= $t_id ?> <span style="font-size:16px; color:#9ca3af;">(موضوع: <?= $ticket_info['subject'] ?>)</span></h3>
                <div class="chat-view">
                    <?php
                    $msgs = $conn->query("SELECT m.*, u.username, u.admin_level FROM ticket_messages m JOIN users u ON m.sender_id = u.id WHERE m.ticket_id = $t_id ORDER BY m.created_at ASC");
                    while($m = $msgs->fetch_assoc()) {
                        if($m['admin_level'] == 0) {
                            echo "<div><strong style='color:#00f0ff;'>Player [".$m['username']."] :</strong><div class='msg-text'>".nl2br(htmlspecialchars($m['message']))."</div></div>";
                        } else {
                            $r_n = ($m['admin_level']==4)?'Director':(($m['admin_level']==3)?'Ceo':(($m['admin_level']==2)?'Junior':'Trial'));
                            echo "<div class='admin-msg-box'><strong style='color:#ef4444;'>[$r_n] [".$m['username']."] :</strong><div class='msg-text'>".nl2br(htmlspecialchars($m['message']))."</div></div>";
                        }
                    }
                    ?>
                </div>
                <form method="POST">
                    <input type="hidden" name="ticket_id" value="<?= $t_id ?>">
                    <textarea name="reply_message" rows="4" placeholder="متن پاسخ خود را بنویسید..." required style="margin-bottom:20px;"></textarea>
                    <button type="submit" name="admin_reply" class="btn">ارسال پاسخ ادمین</button>
                    <a href="admin.php?close_ticket=<?= $t_id ?>" class="btn btn-close" onclick="return confirm('آیا از بستن و حل این تیکت اطمینان دارید؟')">بستن تیکت</a>
                </form>
            <?php } ?>

        <?php else: 
            $tab = isset($_GET['tab']) ? $_GET['tab'] : 'tickets';
        ?>
            <!-- تب تیکت ها (منیج تیکت های مجاز هر رنک) -->
            <div class="panel-section <?= $tab=='tickets'?'active':'' ?>">
                <h3>تیکت‌های در انتظار بررسی (رنک شما)</h3>
                <table>
                    <thead><tr><th>شناسه تیکت</th><th>کاربر ارسال‌کننده</th><th>موضوع درخواست</th><th>عملیات بررسی</th></tr></thead>
                    <tbody>
                        <?php
                        $tickets_query = $conn->query("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.status='open' AND t.subject IN ($subject_string) ORDER BY t.created_at DESC");
                        if($tickets_query->num_rows == 0) { echo "<tr><td colspan='4' style='color:#9ca3af;'>هیچ تیکت بازی در بخش‌های مجاز شما وجود ندارد.</td></tr>"; }
                        while($t = $tickets_query->fetch_assoc()) {
                            echo "<tr>
                                    <td>#".$t['id']."</td>
                                    <td>".htmlspecialchars($t['username'])."</td>
                                    <td><span style='color:#00f0ff; font-weight:bold;'>".$t['subject']."</span></td>
                                    <td><a href='admin.php?view_ticket=".$t['id']."' class='btn' style='padding:6px 16px; font-size:13px;'>بررسی و پاسخ</a></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- تب لیست ادمین‌ها -->
            <div class="panel-section <?= $tab=='admins'?'active':'' ?>">
                <h3>لیست کادر مدیریتی Demons RP</h3>
                <table>
                    <thead><tr><th>نام کاربری ادمین</th><th>مقام و رنک رسمی</th></tr></thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT username, admin_level FROM users WHERE admin_level > 0 ORDER BY admin_level DESC");
                        while($row = $res->fetch_assoc()) {
                            $r = ($row['admin_level']==4)?'Director':(($row['admin_level']==3)?'Ceo':(($row['admin_level']==2)?'Junior':'Trial'));
                            echo "<tr><td>".htmlspecialchars($row['username'])."</td><td><span style='color:#ef4444; font-weight:bold;'>$r</span> (Level ".$row['admin_level'].")</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- تب مدیریت قفل چت دایرکتور -->
            <?php if($admin_lvl == 4 && $tab == 'chat-manage'): 
                $curr_status = $conn->query("SELECT setting_value FROM chat_settings WHERE setting_key='chat_status'")->fetch_assoc()['setting_value'];
                $curr_min = $conn->query("SELECT setting_value FROM chat_settings WHERE setting_key='min_chat_level'")->fetch_assoc()['setting_value'];
            ?>
            <div class="panel-section active">
                <h3>مدیریت وضعیت چت عمومی و اطلاعیه‌ها</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>وضعیت قفل چت:</label>
                        <select name="chat_status">
                            <option value="open" <?= $curr_status=='open'?'selected':'' ?>>🔓 چت آزاد (همه پلیرها می‌توانند چت کنند)</option>
                            <option value="locked" <?= $curr_status=='locked'?'selected':'' ?>>🔒 چت قفل شده (اعمال محدودیت رنک)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>در صورت قفل بودن، چه رنک‌هایی حق چت دارند؟</label>
                        <select name="min_chat_level">
                            <option value="0" <?= $curr_min=='0'?'selected':'' ?>>همه کاربران</option>
                            <option value="1" <?= $curr_min=='1'?'selected':'' ?>>Trial Moderator به بالا</option>
                            <option value="2" <?= $curr_min=='2'?'selected':'' ?>>Junior Moderator به بالا</option>
                            <option value="3" <?= $curr_min=='3'?'selected':'' ?>>Ceo به بالا</option>
                            <option value="4" <?= $curr_min=='4'?'selected':'' ?>>فقط خود دایرکتور (Lvl 4)</option>
                        </select>
                    </div>
                    <button type="submit" name="update_chat_rules" class="btn">اعمال و ذخیره قوانین چت</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- تب رنک دهی ادمین‌ها -->
            <?php if($admin_lvl >= 3 && $tab == 'make-admin'): ?>
            <div class="panel-section active">
                <h3>سیستم اعطای رنک و مدیریت مقامات</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>نام کاربری اکانت هدف:</label>
                        <input type="text" name="target_user" required placeholder="نام کاربری دقیق پلیر را وارد کنید">
                    </div>
                    <div class="form-group">
                        <label>انتخاب رنک مدیریتی:</label>
                        <select name="assign_lvl">
                            <option value="0">سلب تمام دسترسی‌ها (پلیر عادی)</option>
                            <option value="1">Trial Moderator (LVL 1)</option>
                            <option value="2">Junior Moderator (LVL 2)</option><?php if($admin_lvl == 4): ?>
                                <option value="3">Ceo (LVL 3)</option>
                                <option value="4">Director (LVL 4)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" name="submit_admin" class="btn">ثبت و تغییر مقام</button>
                </form>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>