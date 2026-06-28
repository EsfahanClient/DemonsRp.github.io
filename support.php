<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$conn = new mysqli("localhost", "root", "", "Dsite3");
$user_id = $_SESSION['user_id'];

// بررسی تیکت فعال کاربر
$active_ticket_query = $conn->query("SELECT * FROM tickets WHERE user_id=$user_id AND status='open'");
$has_active = ($active_ticket_query->num_rows > 0);
$ticket = $active_ticket_query->fetch_assoc();

if (isset($_POST['create_ticket']) && !$has_active) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $first_msg = mysqli_real_escape_string($conn, $_POST['message']);
    if(!empty($first_msg)) {
        $conn->query("INSERT INTO tickets (user_id, subject, status) VALUES ($user_id, '$subject', 'open')");
        $ticket_id = $conn->insert_id;
        $conn->query("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES ($ticket_id, $user_id, '$first_msg')");
        header("Location: support.php");
        exit();
    }
}

if (isset($_POST['send_reply']) && $has_active) {
    $reply_msg = mysqli_real_escape_string($conn, $_POST['reply_message']);
    $t_id = $ticket['id'];
    if(!empty($reply_msg)) {
        $conn->query("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES ($t_id, $user_id, '$reply_msg')");
        header("Location: support.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پشتیبانی | Demons RP</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: #030712; color: #f3f4f6; padding: 60px 20px; display: flex; justify-content: center; background: radial-gradient(circle at center, #0b1524 0%, #030712 100%); }
        .support-container { width: 100%; max-width: 750px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(0, 240, 255, 0.15); border-radius: 20px; padding: 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.6); }
        h2 { color: #00f0ff; text-shadow: 0 0 15px rgba(0, 240, 255, 0.4); margin-bottom: 30px; text-align: center; font-size: 28px; }
        label { display: block; margin-bottom: 8px; color: #9ca3af; font-size: 14px; }
        select, textarea { width: 100%; padding: 14px; background: #090d16; border: 1px solid rgba(0, 240, 255, 0.2); border-radius: 10px; color: #fff; margin-bottom: 20px; outline: none; }
        select:focus, textarea:focus { border-color: #00f0ff; box-shadow: 0 0 15px rgba(0, 240, 255, 0.2); }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #0055ff, #00f0ff); border: none; border-radius: 10px; color: #fff; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; box-shadow: 0 4px 20px rgba(0, 240, 255, 0.2); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(0, 240, 255, 0.4); }
        .chat-box { background: #090d16; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 25px; max-height: 400px; overflow-y: auto; margin-bottom: 25px; display: flex; flex-direction: column; gap: 15px; }
        .msg-text { background: rgba(255,255,255,0.03); padding: 12px 16px; border-radius: 10px; line-height: 1.6; text-align: right; border-right: 3px solid #00f0ff; }
        .back { display: block; text-align: center; margin-top: 25px; color: #9ca3af; text-decoration: none; font-weight: bold; }
        .back:hover { color: #00f0ff; }
    </style>
</head>
<body>
    <div class="support-container">
        <h2>مرکز پشتیبانی تیکتینگ</h2>

        <?php if(!$has_active): ?>
            <form method="POST">
                <label>موضوع درخواست پشتیبانی:</label>
                <select name="subject">
                    <option value="خرید">خرید</option>
                    <option value="باگ و مشکلات سرور">باگ و مشکلات سرور</option>
                    <option value="شکایت از پلیر">شکایت از پلیر</option>
                    <option value="عضویت در استف / شکایت از استف">عضویت در استف / شکایت از استف</option>
                </select>
                <label>توضیحات کامل مشکل شما:</label>
                <textarea name="message" rows="6" placeholder="لطفاً مشکل خود را با جزئیات کامل بنویسید..." required></textarea>
                <button type="submit" name="create_ticket" class="btn">ثبت و ارسال تیکت جدید</button>
            </form>
        <?php else: ?>
            <div style="margin-bottom: 20px; background: rgba(0, 240, 255, 0.1); border: 1px solid #00f0ff; padding: 15px; border-radius: 10px; display:flex; justify-content:space-between;">
                <span>موضوع تیکت فعال: <strong><?= $ticket['subject'] ?></strong></span>
                <span style="color:#00ff88; font-weight:bold;">وضعیت: در انتظار پاسخ مدیریت</span>
            </div>
            <div class="chat-box">
                <?php
                $t_id = $ticket['id'];
                $msgs = $conn->query("SELECT m.*, u.username, u.admin_level FROM ticket_messages m JOIN users u ON m.sender_id = u.id WHERE m.ticket_id = $t_id ORDER BY m.created_at ASC");
                while($m = $msgs->fetch_assoc()) {
                    if($m['admin_level'] == 0) {
                        echo "<div><div style='font-size:12px; margin-bottom:4px; color:#9ca3af;'>کاربر: [".$m['username']."]</div><div class='msg-text'>".nl2br(htmlspecialchars($m['message']))."</div></div>";
                    } else {
                        $r_name = ($m['admin_level']==4)?'Director':(($m['admin_level']==3)?'Ceo':(($m['admin_level']==2)?'Junior':'Trial'));
                        echo "<div><div style='font-size:12px; margin-bottom:4px; color:#ef4444;'>پاسخ مدیریت: [$r_name]</div><div class='msg-text' style='border-right-color:#ef4444; background:rgba(239, 68, 68, 0.02);'>".nl2br(htmlspecialchars($m['message']))."</div></div>";
                    }
                }
                ?>
            </div>
            <form method="POST">
                <textarea name="reply_message" rows="3" placeholder="پاسخ خود را به ادمین بنویسید..." required></textarea>
                <button type="submit" name="send_reply" class="btn">ارسال پیام جدید</button>
            </form>
        <?php endif; ?>

        <a href="index.php" class="back">← بازگشت به خانه اصلی سرور</a>
    </div>
</body>
</html>