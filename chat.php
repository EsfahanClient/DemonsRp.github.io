<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$conn = new mysqli("localhost", "root", "", "Dsite3");
$user_id = $_SESSION['user_id'];
$admin_lvl = $_SESSION['admin_level'];

// بررسی وضعیت بن یا محرومیت کاربر
$user_stmt = $conn->query("SELECT chat_banned, chat_muted_until FROM users WHERE id=$user_id");
$user_data = $user_stmt->fetch_assoc();

if ($user_data['chat_banned'] == 1) {
    die("<h2 style='color:red; text-align:center; margin-top:50px; font-family:sans-serif;'>شما به صورت دائمی از چت سرور محروم شده‌اید.</h2>");
}

$is_muted = false;
if ($user_data['chat_muted_until'] && strtotime($user_data['chat_muted_until']) > time()) {
    $is_muted = true;
    $mute_time = $user_data['chat_muted_until'];
}

// خواندن تنظیمات قفل چت دیتابیس
$chat_status = $conn->query("SELECT setting_value FROM chat_settings WHERE setting_key='chat_status'")->fetch_assoc()['setting_value'];
$min_lvl = intval($conn->query("SELECT setting_value FROM chat_settings WHERE setting_key='min_chat_level'")->fetch_assoc()['setting_value']);

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'public';

// پردازش ارسال پیام
if (isset($_POST['send_msg'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    if (!empty($msg)) {
        if ($active_tab == 'announcement' && $admin_lvl < 4) {
            // فقط لول 4 میتونه اطلاعیه بزنه
        } elseif ($is_muted) {
            // محروم است
        } elseif ($chat_status == 'locked' && $admin_lvl < $min_lvl) {
            // چت قفل است و سطحش نمیرسه
        } else {
            $conn->query("INSERT INTO global_chat (user_id, message, type) VALUES ($user_id, '$msg', '$active_tab')");
            header("Location: chat.php?tab=" . $active_tab);
            exit();
        }
    }
}

// اقدامات ادمینی زنده (حذف پیام، بن، محرومیت، اخطار)
if ($admin_lvl >= 1 && isset($_GET['action'])) {
    $action = $_GET['action'];
    $target_msg_id = intval($_GET['msg_id']);
    
    // گرفتن اطلاعات فرستنده پیام هدف
    $target_info = $conn->query("SELECT user_id FROM global_chat WHERE id=$target_msg_id")->fetch_assoc();
    $target_uid = $target_info['user_id'];

    if ($action == 'delete') {
        $conn->query("DELETE FROM global_chat WHERE id=$target_msg_id");
    }
    if ($action == 'warn') {
        $warn_txt = mysqli_real_escape_string($conn, $_GET['txt']);
        $conn->query("INSERT INTO global_chat (user_id, message, type) VALUES ($user_id, '⚠️ اخطار ادمینی به کاربر: $warn_txt', '$active_tab')");
    }
    if ($action == 'mute5' && $admin_lvl >= 2) {
        $conn->query("UPDATE users SET chat_muted_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=$target_uid");
    }
    if ($action == 'mute_custom' && $admin_lvl >= 2) {
        $mins = intval($_GET['min']);
        if($admin_lvl >= 3 || ($admin_lvl == 2 && $mins <= 5)) {
            $conn->query("UPDATE users SET chat_muted_until = DATE_ADD(NOW(), INTERVAL $mins MINUTE) WHERE id=$target_uid");
        }
    }
    if ($action == 'ban' && $admin_lvl >= 4) {
        $conn->query("UPDATE users SET chat_banned = 1 WHERE id=$target_uid");
    }
    if ($action == 'ban_lvl3' && $admin_lvl == 3) {
        // لول 3 بن دائمی نداره (طبق دستور)
    }

    header("Location: chat.php?tab=" . $active_tab);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>اتاق گفتگو | Demons RolePlay</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background: #060b13; color: #e2e8f0; padding: 30px 10px; display: flex; justify-content: center; }
        .chat-container { width: 100%; max-width: 800px; background: #0b1524; border: 1px solid #1e293b; border-radius: 20px; display: flex; flex-direction: column; height: 85vh; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .chat-tabs { display: flex; background: #090f1a; border-bottom: 1px solid #1e2  93b; }
        .tab-btn { flex: 1; padding: 15px; text-align: center; color: #64748b; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .tab-btn.active { color: #0095ff; background: rgba(0, 149, 255, 0.05); border-bottom: 3px solid #0095ff; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #070d17; }
        
        /* استایل حبابی پیام‌ها */
        .msg-bubble { max-width: 75%; padding: 12px 16px; border-radius: 14px; position: relative; font-size: 15px; line-height: 1.6; animation: fadeIn 0.2s ease; word-wrap: break-word; }
        .msg-left { align-self: flex-start; background: #101f38; border-bottom-left-radius: 3px; border-left: 3px solid #0095ff; }
        .msg-right { align-self: flex-end; background: #1e293b; border-bottom-right-radius: 3px; }
        .msg-username { font-size: 12px; font-weight: 700; margin-bottom: 4px; display: block; color: #94a3b8; }
        .mod-title { color: #ff3b3b !important; font-weight: 900; }
        
        .chat-input-area { padding: 20px; background: #090f1a; border-top: 1px solid #1e293b; display: flex; flex-direction: column; gap: 10px; }
        .input-row { display: flex; gap: 10px; }
        textarea { flex: 1; padding: 12px; background: #060b13; border: 1px solid #1e293b; border-radius: 10px; color: #fff; resize: none; outline: none; height: 50px; }
        textarea:focus { border-color: #0095ff; }
        .send-btn { padding: 0 25px; background: #0095ff; border: none; border-radius: 10px; color: #fff; font-weight: bold; cursor: pointer; }
        .send-btn:disabled { background: #334155; color: #64748b; cursor: not-allowed; }
        
        .emoji-bar { display: flex; gap: 8px; font-size: 20px; }
        .emoji-bar span { cursor: pointer; transition: 0.2s; }
        .emoji-bar span:hover { transform: scale(1.3); }

        /* منوی راست کلیک سفارشی ادمین */
        #context-menu { position: fixed; background: #0b1524; border: 1px solid #ff3b3b; border-radius: 8px; z-index: 10000; display: none; width: 180px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); overflow: hidden; }
        #context-menu div { padding: 10px 15px; font-size: 13px; color: #e2e8f0; cursor: pointer; text-align: right; transition: 0.2s; }
        #context-menu div:hover { background: rgba(255, 59, 59, 0.15); color: #ff3b3b; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-tabs">
            <a href="chat.php?tab=public" class="tab-btn <?= $active_tab=='public'?'active':'' ?>">💬 چت عمومی</a>
            <a href="chat.php?tab=announcement" class="tab-btn <?= $active_tab=='announcement'?'active':'' ?>">📢 اطلاعیه‌ها</a>
        </div>

        <div class="chat-messages" id="chatBox">
            <?php
            $msgs = $conn->query("SELECT m.*, u.username, u.admin_level FROM global_chat m JOIN users u ON m.user_id = u.id WHERE m.type='$active_tab' ORDER BY m.created_at ASC LIMIT 100");
            if($msgs->num_rows == 0) {
                echo "<p style='color:#64748b; text-align:center; margin-top:20px;'>هنوز پیامی فرستاده نشده است.</p>";
            }
            while($row = $msgs->fetch_assoc()) {
                $is_me = ($row['user_id'] == $user_id);
                $side_class = $is_me ? 'msg-right' : 'msg-left';
                
                // برچسب ادمین
                $display_tag = htmlspecialchars($row['username']);
                if($row['admin_level'] > 0) {
                    $display_tag = "<span class='mod-title'>Moderator [".htmlspecialchars($row['username'])."]</span>";
                }
                
                echo "<div class='msg-bubble $side_class' data-msgid='".$row['id']."' data-sender-lvl='".$row['admin_level']."'>
                        <span class='msg-username'>$display_tag</span>
                        <div>".nl2br(htmlspecialchars($row['message']))."</div></div>";
            }
            ?>
        </div>

        <div class="chat-input-area">
            <div class="emoji-bar">
                <span onclick="addEmoji('😂')">😂</span><span onclick="addEmoji('🔥')">🔥</span>
                <span onclick="addEmoji('❤️')">❤️</span><span onclick="addEmoji('👍')">👍</span>
                <span onclick="addEmoji('👑')">👑</span><span onclick="addEmoji('☠️')">☠️</span>
            </div>
            
            <form method="POST" class="input-row">
                <?php 
                $disabled = ""; 
                $placeholder = "پیام خود را بنویسید...";
                
                if ($active_tab == 'announcement' && $admin_lvl < 4) {
                    $disabled = "disabled"; $placeholder = "فقط دایرکتور (اوتاریتی لول ۴) امکان ارسال اطلاعیه دارد.";
                } elseif ($is_muted) {
                    $disabled = "disabled"; $placeholder = "شما تا ساعت $mute_time محروم هستید.";
                } elseif ($chat_status == 'locked' && $admin_lvl < $min_lvl) {
                    $disabled = "disabled"; $placeholder = "چت توسط مدیریت قفل شده است.";
                }
                ?>
                <textarea id="msgInput" name="message" placeholder="<?= $placeholder ?>" <?= $disabled ?> required></textarea>
                <button type="submit" name="send_msg" class="send-btn" <?= $disabled ?>>ارسال</button>
            </form>
            <a href="index.php" style="color: #64748b; text-align: center; font-size: 13px; text-decoration: none; margin-top: 5px;">بازگشت به صفحه اصلی</a>
        </div>
    </div>

    <?php if($admin_lvl >= 1): ?>
    <div id="context-menu">
        <div onclick="adminAction('delete')">❌ پاک کردن پیام</div>
        <div onclick="promptWarn()">⚠️ اخطار ادمینی</div>
        <?php if($admin_lvl >= 2): ?>
            <div onclick="adminAction('mute5')">⏳ محرومیت ۵ دقیقه</div>
            <?php if($admin_lvl >= 3): ?>
                <div onclick="promptMuteCustom()">⏳ محرومیت دلخواه (دقیقه)</div>
                <?php if($admin_lvl >= 4): ?>
                    <div onclick="adminAction('ban')">🚫 بن دائمی از چت</div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        let currentMsgId = null;
        const contextMenu = document.getElementById('context-menu');
        const adminLvl = <?= $admin_lvl ?>;

        document.querySelectorAll('.msg-bubble').forEach(bubble => {
            // پشتیبانی از راست کلیک دسکتاپ و نگه‌داشتن لمسی گوشی (Long Press)
            const openMenu = (e) => {
                e.preventDefault();
                currentMsgId = bubble.getAttribute('data-msgid');
                contextMenu.style.display = 'block';
                contextMenu.style.top = e.pageY + 'px';
                contextMenu.style.left = e.pageX + 'px';
            };
            bubble.addEventListener('contextmenu', openMenu);
            
            let pressTimer;
            bubble.addEventListener('touchstart', (e) => {
                pressTimer = window.setTimeout(() => { openMenu(e.touches[0]); }, 800);
            });
            bubble.addEventListener('touchend', () => { clearTimeout(pressTimer); });
        });

        document.addEventListener('click', () => { contextMenu.style.display = 'none'; });

        function adminAction(action, extra = '') {
            if(currentMsgId) {
                window.location.href = `chat.php?tab=<?= $active_tab ?>&action=${action}&msg_id=${currentMsgId}${extra}`;
            }
        }
        function promptWarn() {
            let reason = prompt("متن اخطار ادمینی را وارد کنید:");
            if(reason) adminAction('warn', `&txt=${encodeURIComponent(reason)}`);
        }
        function promptMuteCustom() {
            let min = prompt("چند دقیقه محروم شود؟");
            if(min) adminAction('mute_custom', `&min=${parseInt(min)}`);
        }
    </script>
    <?php endif; ?>

    <script>
        function addEmoji(emoji) {
            const input = document.getElementById('msgInput');
            if(!input.disabled) input.value += emoji;
        }
        // اسکرول اتوماتیک چت به انتها
        const cb = document.getElementById('chatBox');
        cb.scrollTop = cb.scrollHeight;
    </script>
</body>
</html>