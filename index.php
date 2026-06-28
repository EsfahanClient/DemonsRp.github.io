<?php
session_start();
$conn = new mysqli("localhost", "root", "", "Dsite3");
if ($conn->connect_error) { die("اتصال به دیتابیس ناموفق بود"); }

$error = ""; $success = "";

if (isset($_POST['register'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];
    $repass = $_POST['re-password'];

    if ($pass !== $repass) { $error = "رمزهای عبور همخوانی ندارند."; }
    else {
        $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
        $check = $conn->query("SELECT id FROM users WHERE username='$user' OR email='$email'");
        if ($check->num_rows > 0) { $error = "این نام کاربری یا ایمیل قبلاً ثبت شده است."; }
        else {
            $conn->query("INSERT INTO users (username, email, password) VALUES ('$user', '$email', '$hashed_pass')");
            $success = "ثبت‌نام با موفقیت انجام شد. حالا وارد شوید.";
        }
    }
}

if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];
    $result = $conn->query("SELECT * FROM users WHERE username='$user'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['admin_level'] = $row['admin_level'];
            header("Location: index.php");
            exit();
        } else { $error = "رمز عبور اشتباه است."; }
    } else { $error = "کاربر یافت نشد."; }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demons RolePlay</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        body { background-color: #060b13; color: #e2e8f0; overflow-x: hidden; }
        header { width: 100%; padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to bottom, rgba(6, 11, 19, 0.95), transparent); position: fixed; top: 0; left: 0; z-index: 1000; backdrop-filter: blur(8px); border-bottom: 1px solid rgba(0, 149, 255, 0.1); }
        .logo-text { font-family: 'Orbitron', sans-serif; font-size: 26px; font-weight: 900; color: #0095ff; text-shadow: 0 0 15px rgba(0, 149, 255, 0.6); }
        nav { display: flex; gap: 15px; align-items: center; }
        nav a, .btn-modal { color: #94a3b8; text-decoration: none; font-size: 15px; font-weight: 700; transition: all 0.3s ease; padding: 6px 14px; border-radius: 6px; cursor: pointer; background: transparent; border: none; }
        nav a:hover, nav a.active, .btn-modal:hover { color: #ffffff; background: rgba(0, 149, 255, 0.15); border-bottom: 2px solid #0095ff; }
        .admin-badge { color: #ff3b3b !important; border-bottom: 2px solid #ff3b3b !important; }
        .hero { min-height: 85vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 150px 20px 50px 20px; background: radial-gradient(circle, rgba(0, 149, 255, 0.08) 0%, rgba(6, 11, 19, 1) 75%); }
        .hero h1 { font-size: 52px; font-weight: 900; color: #ffffff; margin-bottom: 15px; letter-spacing: 1px; }
        .hero h1 span { color: #0095ff; text-shadow: 0 0 20px rgba(0, 149, 255, 0.5); }
        .hero p { color: #94a3b8; font-size: 19px; max-width: 650px; margin-bottom: 25px; line-height: 1.8; }
        .server-status { background: #0b1524; border: 1px solid #1e293b; padding: 15px 35px; border-radius: 50px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .status-dot { width: 12px; height: 12px; background-color: #00ff88; border-radius: 50%; box-shadow: 0 0 10px #00ff88; animation: pulse 2s infinite; }
        .server-ip {font-family: 'Orbitron', sans-serif; color: #ffffff; font-weight: 700; }
        .main-content { max-width: 1200px; margin: 0 auto; padding: 50px 5% 100px 5%; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .feature-card { background: #0b1524; border: 1px solid #142238; border-radius: 16px; padding: 40px 30px; text-align: center; cursor: pointer; transition: 0.4s; position: relative; overflow: hidden; }
        .feature-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #0095ff; transform: scaleX(0); transition: 0.3s; }
        .feature-card:hover { transform: translateY(-8px); border-color: #0095ff; box-shadow: 0 10px 25px rgba(0, 149, 255, 0.1); }
        .feature-card:hover::before { transform: scaleX(1); }
        .card-icon { font-size: 45px; margin-bottom: 20px; display: inline-block; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(4, 7, 12, 0.85); backdrop-filter: blur(10px); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .modal-box { background: #0b1524; border: 1px solid #1e293b; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; position: relative; text-align: center; }
        .modal-box h2 { margin-bottom: 25px; color: #0095ff; }
        .modal-box input { width: 100%; padding: 14px 18px; background: #060b13; border: 1px solid #1e293b; border-radius: 10px; color: #fff; margin-bottom: 15px; outline: none; text-align: right; }
        .modal-box input:focus { border-color: #0095ff; }
        .modal-box button { width: 100%; padding: 14px; background: #0095ff; border: none; border-radius: 10px; color: #fff; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .modal-box button:hover { background: #33a8ff; }
        .close-btn { position: absolute; top: 15px; left: 15px; color: #64748b; cursor: pointer; font-size: 22px; }
        .alert-box { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; background: #ff3b3b; color: white; border-radius: 8px; z-index: 3000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 12px rgba(0, 255, 136, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 255, 136, 0); } }
    </style>
</head>
<body>

    <?php if($error): ?> <div class="alert-box"><?= $error ?></div> <?php endif; ?>
    <?php if($success): ?> <div class="alert-box" style="background:#00ff88; color:#000;"><?= $success ?></div> <?php endif; ?>

    <header>
        <div class="logo-text">Demons RolePlay</div>
        <nav>
            <a href="#" class="active">صفحه اصلی</a>
            <?php if(isset($_SESSION['username'])): ?>
                <span style="color:#0095ff; font-weight:700;">خوش آمدی، <?= htmlspecialchars($_SESSION['username']) ?></span>
                <?php if($_SESSION['admin_level'] > 0): ?>
                    <a href="admin.php" class="admin-badge">پنل ادمین</a>
                <?php endif; ?>
                <a href="chat.php">گفتگو</a>
                <a href="support.php">پشتیبانی</a>
                <a href="logout.php" style="color:#ff3b3b;">خروج</a>
            <?php else: ?>
                <button class="btn-modal" onclick="openModal('loginModal')">ورود</button>
                <button class="btn-modal" onclick="openModal('registerModal')">ثبت نام</button>
                <button class="btn-modal" onclick="alert('ابتدا باید وارد حساب خود شوید!')">گفتگو</button>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <h1>به دنیای تاریک <span>Demons RolePlay</span> خوش آمدید</h1>
        <p>بزرگترین و حرفه‌ای‌ترین سرور رول‌پلی با امکاناتی بی‌نظیر و تم اختصاصی متمایز.</p>
        <div class="server-status">
            <div class="status-dot"></div>
            <span class="server-ip">connect.demonsrp.ir</span>
            <span style="color: #64748b; font-size: 14px;">| ۵۴۰ پلیر آنلاین</span>
        </div>
    </section>

    <main class="main-content">
        <div class="grid-container">
            <div class="feature-card" onclick="location.href='chat.php'"><span class="card-icon">💬</span><h3>اتاق گفتگو عمومی</h3><p>ارتباط زنده با پلیرها و دریافت اطلاعیه‌های مهم سرور.</p></div>
            <div class="feature-card" onclick="location.href='support.php'"><span class="card-icon">🛠️</span><h3>مرکز پشتیبانی</h3><p>سیستم تیکتینگ هوشمند جهت ارسال گزارشات و باگ‌ها.</p></div>
        </div>
    </main>

    <div id="loginModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal('loginModal')">&times;</span>
            <h2>ورود به حساب</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="نام کاربری" required>
                <input type="password" name="password" placeholder="رمز عبور" required>
                <button type="submit" name="login">ورود</button>
            </form>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal('registerModal')">&times;</span>
            <h2>ثبت نام جدید</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="نام کاربری" required>
                <input type="email" name="email" placeholder="ایمیل شما" required>
                <input type="password" name="password" placeholder="رمز عبور" required>
                <input type="password" name="re-password" placeholder="تکرار رمز عبور" required>
                <button type="submit" name="register">عضویت</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>