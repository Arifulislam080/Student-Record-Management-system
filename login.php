<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
require_once 'db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['name']           = $user['name'];
            $_SESSION['email']          = $user['email'];
            $_SESSION['role']           = $user['role'];
            $_SESSION['student_ref_id'] = $user['student_ref_id'];
            $lg = $pdo->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (:uid, :act, :ip)");
            $lg->execute([':uid'=>$user['id'],':act'=>'Logged in',':ip'=>$_SERVER['REMOTE_ADDR']??null]);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | StudentRec</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background:linear-gradient(135deg,#1e1b4b 0%,#312e81 40%,#0e7490 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { background:#fff; border-radius:1.2rem; box-shadow:0 25px 80px rgba(0,0,0,.4); overflow:hidden; width:100%; max-width:420px; }
        .login-header { background:linear-gradient(135deg,#4f46e5,#06b6d4); padding:2.2rem 2rem 1.8rem; text-align:center; }
        .login-header h1 { color:#fff; font-size:1.6rem; font-weight:800; margin:0 0 .3rem; }
        .login-header p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
        .login-body { padding:2rem; }
        .login-label { font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.3rem; display:block; }
        .login-input { width:100%; border:1.5px solid #e2e8f0; border-radius:.5rem; padding:.65rem 1rem; font-size:.93rem; color:#1e293b; transition:border-color .2s,box-shadow .2s; }
        .login-input:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
        .btn-login { width:100%; background:linear-gradient(90deg,#4338ca,#4f46e5); color:#fff; border:none; border-radius:.5rem; padding:.75rem; font-weight:700; font-size:.95rem; cursor:pointer; transition:opacity .2s,transform .2s; box-shadow:0 4px 14px rgba(79,70,229,.4); }
        .btn-login:hover { opacity:.92; transform:translateY(-1px); }
        .hint-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:.5rem; padding:.75rem 1rem; font-size:.8rem; color:#166534; margin-top:1.2rem; text-align:center; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div style="font-size:2.5rem;margin-bottom:.5rem;"><i class="bi bi-mortarboard-fill" style="color:#fff;"></i></div>
            <h1>StudentRec</h1>
            <p>Student Record Management System</p>
        </div>
        <div class="login-body">
            <?php if ($error !== ''): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-4" style="font-size:.88rem;border-radius:.5rem;" role="alert">
                <i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label for="loginEmail" class="login-label">Email Address</label>
                    <input type="email" id="loginEmail" name="email" class="login-input"
                           placeholder="admin@admin.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="mb-4">
                    <label for="loginPassword" class="login-label">Password</label>
                    <input type="password" id="loginPassword" name="password" class="login-input"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>
            <div class="hint-box">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Default Admin:</strong> admin@admin.com / admin123
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Signing in...';
        });
    </script>
</body>
</html>
