<?php
require 'auth.php';
require_once 'db.php';

$uid = (int)$_SESSION['user_id'];
$fetch = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$fetch->execute([':id'=>$uid]);
$me = $fetch->fetch(PDO::FETCH_ASSOC);

$profileErrors  = [];
$passwordErrors = [];
$msg     = trim($_GET['msg']  ?? '');
$msgType = in_array($_GET['type'] ?? '', ['success','danger']) ? $_GET['type'] : 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '')                                    { $profileErrors['name']  = 'Name is required.'; }
    if ($email === '')                                   { $profileErrors['email'] = 'Email is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $profileErrors['email'] = 'Enter a valid email.'; }
    else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=:e AND id!=:id");
        $chk->execute([':e'=>$email,':id'=>$uid]);
        if ($chk->fetch()) { $profileErrors['email'] = 'Email already used by another account.'; }
    }
    if (empty($profileErrors)) {
        $pdo->prepare("UPDATE users SET name=:n,email=:e WHERE id=:id")->execute([':n'=>$name,':e'=>$email,':id'=>$uid]);
        $_SESSION['name'] = $name; $_SESSION['email'] = $email;
        log_action($pdo,'Updated own profile','users',$uid);
        header('Location: profile.php?msg=Profile+updated+successfully&type=success'); exit;
    }
    $me['name'] = $name; $me['email'] = $email;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $newPwd  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($current === '')                                                  { $passwordErrors['current'] = 'Current password is required.'; }
    elseif (!password_verify($current, $me['password_hash']))            { $passwordErrors['current'] = 'Current password is incorrect.'; }
    if ($newPwd === '')                                                   { $passwordErrors['new']     = 'New password is required.'; }
    elseif (strlen($newPwd) < 8)                                         { $passwordErrors['new']     = 'Minimum 8 characters.'; }
    elseif ($newPwd !== $confirm)                                        { $passwordErrors['confirm'] = 'Passwords do not match.'; }
    if (empty($passwordErrors)) {
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash=:h WHERE id=:id")->execute([':h'=>$hash,':id'=>$uid]);
        log_action($pdo,'Changed own password','users',$uid);
        header('Location: profile.php?msg=Password+changed+successfully&type=success'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | StudentRec</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require '_nav.php'; ?>
<div class="page-wrapper"><div class="container-xl">
<div class="row justify-content-center"><div class="col-lg-8 col-md-10">

    <?php if ($msg !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($msgType) ?> alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert" id="flashAlert">
        <i class="bi bi-<?= $msgType==='danger'?'x-circle-fill':'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-person-circle"></i></div>
            <h5>My Profile</h5>
            <?php $rc=['admin'=>'danger','teacher'=>'warning','student'=>'info']; ?>
            <span class="ms-auto badge bg-<?= $rc[$_SESSION['role']] ?? 'secondary' ?> text-uppercase"><?= htmlspecialchars($_SESSION['role']) ?></span>
        </div>
        <div class="p-4">
            <?php if (!empty($profileErrors)): ?><div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill"></i> Please fix the errors below.</div><?php endif; ?>
            <form method="POST" action="" novalidate>
                <input type="hidden" name="action" value="profile">
                <div class="mb-3">
                    <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control-custom <?= isset($profileErrors['name'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($me['name']) ?>" required>
                    <?php if(isset($profileErrors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($profileErrors['name']) ?></div><?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control-custom <?= isset($profileErrors['email'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($me['email']) ?>" required>
                    <?php if(isset($profileErrors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($profileErrors['email']) ?></div><?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label-custom">Role</label>
                    <input type="text" class="form-control-custom" value="<?= ucfirst($me['role']) ?>" disabled style="background:#f1f5f9;color:#64748b;">
                    <small class="text-muted" style="font-size:.78rem;"><i class="bi bi-info-circle me-1"></i>Role cannot be changed from this page.</small>
                </div>
                <button type="submit" class="btn-submit"><i class="bi bi-floppy me-1"></i>Save Changes</button>
            </form>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-shield-lock"></i></div>
            <h5>Change Password</h5>
        </div>
        <div class="p-4">
            <?php if (!empty($passwordErrors)): ?><div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill"></i> Please fix the errors below.</div><?php endif; ?>
            <form method="POST" action="" novalidate>
                <input type="hidden" name="action" value="password">
                <div class="mb-3">
                    <label class="form-label-custom">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control-custom <?= isset($passwordErrors['current'])?'is-invalid':'' ?>" required>
                    <?php if(isset($passwordErrors['current'])): ?><div class="invalid-feedback"><?= htmlspecialchars($passwordErrors['current']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control-custom <?= isset($passwordErrors['new'])?'is-invalid':'' ?>" minlength="8" required>
                    <?php if(isset($passwordErrors['new'])): ?><div class="invalid-feedback"><?= htmlspecialchars($passwordErrors['new']) ?></div><?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label-custom">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control-custom <?= isset($passwordErrors['confirm'])?'is-invalid':'' ?>" required>
                    <?php if(isset($passwordErrors['confirm'])): ?><div class="invalid-feedback"><?= htmlspecialchars($passwordErrors['confirm']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn-submit" style="background:linear-gradient(90deg,#0891b2,#06b6d4);">
                    <i class="bi bi-key me-1"></i>Change Password
                </button>
            </form>
        </div>
    </div>
</div></div>
</div></div>
<footer class="footer-bar">&copy; <?= date('Y') ?> StudentRec</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>(function(){ var a=document.getElementById('flashAlert'); if(a) setTimeout(function(){bootstrap.Alert.getOrCreateInstance(a).close();},5000); })();</script>
</body></html>
