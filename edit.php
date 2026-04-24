<?php
require 'auth.php';
require_once 'db.php';

$role = $_SESSION['role'];
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
      ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) { header('Location: index.php'); exit; }

$fetch = $pdo->prepare("SELECT * FROM students WHERE id = :id");
$fetch->execute([':id' => $id]);
$student = $fetch->fetch(PDO::FETCH_ASSOC);
if (!$student) { header('Location: index.php'); exit; }

if ($role === 'student') {
    if ((int)$_SESSION['student_ref_id'] !== (int)$id) {
        header('Location: dashboard.php?msg=Access+denied.&type=danger'); exit;
    }
}

$depts  = $pdo->query("SELECT name FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$fields = ['name'=>$student['name'],'student_id'=>$student['student_id'],'email'=>$student['email'],'department'=>$student['department']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email']      ?? '');
    $department = trim($_POST['department'] ?? '');
    $fields     = compact('name','student_id','email','department');

    if ($name === '')                                        { $errors['name']       = 'Full name is required.'; }
    elseif (mb_strlen($name) > 100)                        { $errors['name']       = 'Max 100 characters.'; }
    if ($student_id === '')                                 { $errors['student_id'] = 'Student ID is required.'; }
    elseif (mb_strlen($student_id) > 50)                   { $errors['student_id'] = 'Max 50 characters.'; }
    else {
        $chk = $pdo->prepare("SELECT id FROM students WHERE student_id = :sid AND id != :id");
        $chk->execute([':sid'=>$student_id,':id'=>$id]);
        if ($chk->fetch())                                  { $errors['student_id'] = 'This Student ID is used by another record.'; }
    }
    if ($email === '')                                      { $errors['email']      = 'Email is required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))    { $errors['email']      = 'Enter a valid email address.'; }
    elseif (mb_strlen($email) > 100)                       { $errors['email']      = 'Max 100 characters.'; }
    if ($department === '')                                 { $errors['department'] = 'Please select a department.'; }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE students SET name=:name,student_id=:student_id,email=:email,department=:department WHERE id=:id"
        );
        $stmt->execute([':name'=>$name,':student_id'=>$student_id,':email'=>$email,':department'=>$department,':id'=>$id]);
        log_action($pdo, 'Updated student: '.$name, 'students', $id);
        header('Location: index.php?msg=Student+updated+successfully&type=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | StudentRec</title>
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
        <h5>Edit Student</h5>
        <span class="ms-auto text-muted" style="font-size:.8rem;">Record #<?= (int)$id ?></span>
    </div>
    <div class="p-4">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill"></i> Please fix the errors below.</div>
        <?php endif; ?>
        <form method="POST" action="" novalidate id="editForm">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <div class="mb-4">
                <label for="name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control-custom <?= isset($errors['name'])?'is-invalid':'' ?>"
                    value="<?= htmlspecialchars($fields['name']) ?>" maxlength="100" required>
                <?php if(isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="student_id" class="form-label-custom">Student ID <span class="text-danger">*</span></label>
                <input type="text" id="student_id" name="student_id" class="form-control-custom <?= isset($errors['student_id'])?'is-invalid':'' ?>"
                    value="<?= htmlspecialchars($fields['student_id']) ?>" maxlength="50" required>
                <?php if(isset($errors['student_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['student_id']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                <input type="email" id="email" name="email" class="form-control-custom <?= isset($errors['email'])?'is-invalid':'' ?>"
                    value="<?= htmlspecialchars($fields['email']) ?>" maxlength="100" required>
                <?php if(isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="mb-5">
                <label for="department" class="form-label-custom">Department <span class="text-danger">*</span></label>
                <select id="department" name="department" class="form-control-custom <?= isset($errors['department'])?'is-invalid':'' ?>" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($depts as $d): ?>
                    <option value="<?= htmlspecialchars($d['name']) ?>" <?= $fields['department']===$d['name']?'selected':'' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if(isset($errors['department'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['department']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn-submit" id="editSubmitBtn">
                    <i class="bi bi-floppy me-1"></i>Update Student
                </button>
                <a href="index.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
</div></div>
<footer class="footer-bar">&copy; <?= date('Y') ?> StudentRec</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editForm').addEventListener('submit', function () {
    var btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Updating...';
});
</script>
</body></html>
