<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$ROLES       = ['admin', 'teacher', 'student'];
$allStudents = $pdo->query("SELECT id, name, student_id FROM students ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: users.php'); exit; }

$fetch = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$fetch->execute([':id'=>$id]);
$user = $fetch->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: users.php'); exit; }

$fields = ['name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'student_ref_id'=>$user['student_ref_id'],'password'=>'','confirm'=>''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']    ?? '');
    $email    = trim($_POST['email']   ?? '');
    $role     = in_array($_POST['role'] ?? '', $ROLES) ? $_POST['role'] : 'student';
    $ref_id   = (int)($_POST['student_ref_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $fields   = compact('name','email','role','password','confirm');
    $fields['student_ref_id'] = $ref_id;

    if ($name === '')                                    { $errors['name']  = 'Name is required.'; }
    if ($email === '')                                   { $errors['email'] = 'Email is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email.'; }
    else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=:e AND id!=:id");
        $chk->execute([':e'=>$email,':id'=>$id]);
        if ($chk->fetch())                               { $errors['email'] = 'Email already in use by another account.'; }
    }
    if ($password !== '') {
        if (strlen($password) < 8)                       { $errors['password'] = 'Minimum 8 characters.'; }
        elseif ($password !== $confirm)                  { $errors['confirm']  = 'Passwords do not match.'; }
    }

    if (empty($errors)) {
        $refVal = ($role === 'student' && $ref_id > 0) ? $ref_id : null;
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=:n,email=:e,role=:r,student_ref_id=:ref,password_hash=:h WHERE id=:id");
            $stmt->execute([':n'=>$name,':e'=>$email,':r'=>$role,':ref'=>$refVal,':h'=>$hash,':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=:n,email=:e,role=:r,student_ref_id=:ref WHERE id=:id");
            $stmt->execute([':n'=>$name,':e'=>$email,':r'=>$role,':ref'=>$refVal,':id'=>$id]);
        }
        log_action($pdo,'Updated user: '.$name,'users',$id);
        header('Location: users.php?msg=User+updated+successfully&type=success'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | StudentRec</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require '_nav.php'; ?>
<div class="page-wrapper"><div class="container-xl">
<div class="row justify-content-center"><div class="col-lg-7 col-md-9">
<div class="card-custom">
    <div class="card-header-custom">
        <div class="card-header-icon"><i class="bi bi-pencil-square"></i></div>
        <h5>Edit User</h5>
        <a href="users.php" class="ms-auto btn-cancel" style="padding:.3rem .8rem;font-size:.82rem;"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="p-4">
        <?php if (!empty($errors)): ?><div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill"></i> Please fix the errors below.</div><?php endif; ?>
        <form method="POST" action="" novalidate>
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <div class="mb-3">
                <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control-custom <?= isset($errors['name'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($fields['name']) ?>" required>
                <?php if(isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control-custom <?= isset($errors['email'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($fields['email']) ?>" required>
                <?php if(isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Role <span class="text-danger">*</span></label>
                <select name="role" id="roleSelectEdit" class="form-control-custom">
                    <?php foreach ($ROLES as $r): ?>
                    <option value="<?= $r ?>" <?= $fields['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3" id="studentRefGroupEdit" style="display:<?= $fields['role']==='student'?'block':'none' ?>;">
                <label class="form-label-custom">Link to Student Record</label>
                <select name="student_ref_id" class="form-control-custom">
                    <option value="0">-- None --</option>
                    <?php foreach ($allStudents as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)$fields['student_ref_id']===(int)$s['id']?'selected':'' ?>>
                        <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['student_id']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <hr class="my-4">
            <p class="text-muted mb-3" style="font-size:.84rem;"><i class="bi bi-info-circle me-1"></i>Leave password blank to keep existing.</p>
            <div class="mb-3">
                <label class="form-label-custom">New Password</label>
                <input type="password" name="password" class="form-control-custom <?= isset($errors['password'])?'is-invalid':'' ?>" minlength="8">
                <?php if(isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
                <label class="form-label-custom">Confirm New Password</label>
                <input type="password" name="confirm" class="form-control-custom <?= isset($errors['confirm'])?'is-invalid':'' ?>">
                <?php if(isset($errors['confirm'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['confirm']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn-submit"><i class="bi bi-floppy me-1"></i>Update User</button>
                <a href="users.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
</div></div>
<footer class="footer-bar">&copy; <?= date('Y') ?> StudentRec</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('roleSelectEdit').addEventListener('change', function () {
    document.getElementById('studentRefGroupEdit').style.display = this.value === 'student' ? 'block' : 'none';
});
</script>
</body></html>
