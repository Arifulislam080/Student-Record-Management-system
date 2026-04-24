<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$ROLES       = ['admin', 'teacher', 'student'];
$allStudents = $pdo->query("SELECT id, name, student_id FROM students ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$fields = ['name'=>'','email'=>'','password'=>'','confirm'=>'','role'=>'student','student_ref_id'=>0];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']    ?? '');
    $email    = trim($_POST['email']   ?? '');
    $password = $_POST['password']     ?? '';
    $confirm  = $_POST['confirm']      ?? '';
    $role     = in_array($_POST['role'] ?? '', $ROLES) ? $_POST['role'] : 'student';
    $ref_id   = (int)($_POST['student_ref_id'] ?? 0);
    $fields   = compact('name','email','password','confirm','role');
    $fields['student_ref_id'] = $ref_id;

    if ($name === '')                                    { $errors['name']     = 'Name is required.'; }
    if ($email === '')                                   { $errors['email']    = 'Email is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email']    = 'Enter a valid email.'; }
    else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=:e");
        $chk->execute([':e'=>$email]);
        if ($chk->fetch())                               { $errors['email']   = 'Email already in use.'; }
    }
    if ($password === '')                                { $errors['password'] = 'Password is required.'; }
    elseif (strlen($password) < 8)                      { $errors['password'] = 'Minimum 8 characters.'; }
    elseif ($password !== $confirm)                      { $errors['confirm']  = 'Passwords do not match.'; }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, student_ref_id) VALUES (:name,:email,:hash,:role,:ref)"
        );
        $stmt->execute([
            ':name'  => $name,   ':email' => $email,
            ':hash'  => $hash,   ':role'  => $role,
            ':ref'   => ($role === 'student' && $ref_id > 0) ? $ref_id : null,
        ]);
        log_action($pdo, 'Created user: '.$name, 'users', (int)$pdo->lastInsertId());
        header('Location: users.php?msg=User+created+successfully&type=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | StudentRec</title>
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
        <div class="card-header-icon"><i class="bi bi-person-plus"></i></div>
        <h5>Add New User</h5>
        <a href="users.php" class="ms-auto btn-cancel" style="padding:.3rem .8rem;font-size:.82rem;"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
    <div class="p-4">
        <?php if (!empty($errors)): ?><div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill"></i> Please fix the errors below.</div><?php endif; ?>
        <form method="POST" action="" novalidate id="ucForm">
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
                <label class="form-label-custom">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control-custom <?= isset($errors['password'])?'is-invalid':'' ?>" minlength="8" required>
                <?php if(isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm" class="form-control-custom <?= isset($errors['confirm'])?'is-invalid':'' ?>" required>
                <?php if(isset($errors['confirm'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['confirm']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label-custom">Role <span class="text-danger">*</span></label>
                <select name="role" id="roleSelect" class="form-control-custom">
                    <?php foreach ($ROLES as $r): ?>
                    <option value="<?= $r ?>" <?= $fields['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4" id="studentRefGroup" style="display:<?= $fields['role']==='student'?'block':'none' ?>;">
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
            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-submit"><i class="bi bi-floppy me-1"></i>Create User</button>
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
document.getElementById('roleSelect').addEventListener('change', function () {
    document.getElementById('studentRefGroup').style.display = this.value === 'student' ? 'block' : 'none';
});
</script>
</body></html>
