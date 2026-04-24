<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$errors  = [];
$newName = '';
$editErrors = [];
$msg     = trim($_GET['msg']  ?? '');
$msgType = in_array($_GET['type'] ?? '', ['success','danger']) ? $_GET['type'] : 'success';

// ── Add department ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $newName = trim($_POST['dept_name'] ?? '');
    if ($newName === '')                    { $errors['dept_name'] = 'Department name is required.'; }
    elseif (mb_strlen($newName) > 100)     { $errors['dept_name'] = 'Max 100 characters.'; }
    if (empty($errors)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (:name)");
        $ins->execute([':name' => $newName]);
        log_action($pdo, 'Added department: '.$newName, 'departments', (int)$pdo->lastInsertId());
        header('Location: departments.php?msg=Department+added&type=success'); exit;
    }
}

// ── Inline edit department ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $editId   = (int)($_POST['edit_id']   ?? 0);
    $editName = trim($_POST['edit_name']  ?? '');
    if ($editId && $editName !== '') {
        $chk = $pdo->prepare("SELECT id FROM departments WHERE name=:name AND id!=:id");
        $chk->execute([':name'=>$editName,':id'=>$editId]);
        if ($chk->fetch()) {
            $msg = 'Another department with that name already exists.'; $msgType = 'danger';
        } else {
            $upd = $pdo->prepare("UPDATE departments SET name=:name WHERE id=:id");
            $upd->execute([':name'=>$editName,':id'=>$editId]);
            log_action($pdo, 'Updated department: '.$editName, 'departments', $editId);
            header('Location: departments.php?msg=Department+updated&type=success'); exit;
        }
    }
}

// ── Fetch departments with student counts ─────────────────────────────────────
$depts = $pdo->query(
    "SELECT d.id, d.name, d.created_at, COUNT(s.id) AS student_count
     FROM departments d
     LEFT JOIN students s ON s.department = d.name
     GROUP BY d.id, d.name, d.created_at ORDER BY d.name"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | StudentRec</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require '_nav.php'; ?>
<div class="page-wrapper"><div class="container-xl">

    <?php if ($msg !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($msgType) ?> alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert" id="flashAlert">
        <i class="bi bi-<?= $msgType==='danger'?'x-circle-fill':'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Add Form -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-plus-circle"></i></div>
            <h5>Add Department</h5>
        </div>
        <div class="p-4">
            <form method="POST" action="" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="add">
                <div class="col-md-7">
                    <label class="form-label-custom mb-1">Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="dept_name"
                           class="form-control-custom <?= isset($errors['dept_name'])?'is-invalid':'' ?>"
                           placeholder="e.g. Biomedical Engineering"
                           value="<?= htmlspecialchars($newName) ?>" maxlength="100">
                    <?php if(isset($errors['dept_name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['dept_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-submit" style="padding:.6rem 1.5rem;">
                        <i class="bi bi-plus-circle me-1"></i>Add
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="card-custom">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-building"></i></div>
            <h5>All Departments</h5>
            <span class="ms-auto badge bg-primary rounded-pill"><?= count($depts) ?> total</span>
        </div>
        <?php if (empty($depts)): ?>
        <div class="empty-state"><i class="bi bi-building" style="font-size:3rem;opacity:.2;display:block;margin-bottom:1rem;"></i><p>No departments yet.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr><th>#</th><th>Department Name</th><th class="text-center">Students</th><th>Created At</th><th class="text-center" style="min-width:280px;">Actions</th></tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($depts as $d): ?>
                    <tr id="row<?= (int)$d['id'] ?>">
                        <td><span class="badge-serial"><?= $i++ ?></span></td>
                        <td>
                            <!-- View mode -->
                            <span id="viewName<?= (int)$d['id'] ?>" class="fw-semibold"><?= htmlspecialchars($d['name']) ?></span>
                            <!-- Edit mode (hidden by default) -->
                            <form method="POST" action="" id="editForm<?= (int)$d['id'] ?>" class="d-none d-flex gap-2 align-items-center">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="edit_id" value="<?= (int)$d['id'] ?>">
                                <input type="text" name="edit_name" class="form-control-custom" style="padding:.35rem .7rem;font-size:.85rem;width:auto;"
                                       value="<?= htmlspecialchars($d['name']) ?>" maxlength="100" required>
                                <button type="submit" class="btn-action btn-edit"><i class="bi bi-check-lg"></i></button>
                                <button type="button" class="btn-action btn-cancel" onclick="cancelEdit(<?= (int)$d['id'] ?>)"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </td>
                        <td class="text-center"><span class="badge bg-primary rounded-pill"><?= (int)$d['student_count'] ?></span></td>
                        <td class="text-date"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                        <td class="text-center">
                            <button type="button" class="btn-action btn-edit me-1" onclick="startEdit(<?= (int)$d['id'] ?>)" id="editTrigger<?= (int)$d['id'] ?>">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>
                            <button type="button" class="btn-action btn-delete" id="deleteBtn<?= (int)$d['id'] ?>"
                                onclick="confirmDeptDelete(<?= (int)$d['id'] ?>, '<?= addslashes(htmlspecialchars($d['name'])) ?>', <?= (int)$d['student_count'] ?>)">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div></div>

<!-- Delete modal -->
<div class="modal fade" id="deptDelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:.75rem;border:none;overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(90deg,#fee2e2,#fff1f2);border-bottom:1px solid #fecdd3;">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Delete Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 px-4"><p class="mb-0 text-secondary" id="deptDelBody"></p></div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="deptDelLink" class="btn-submit" style="background:linear-gradient(90deg,#b91c1c,#ef4444);">
            <i class="bi bi-trash me-1"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>

<footer class="footer-bar">&copy; <?= date('Y') ?> StudentRec</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function startEdit(id) {
    document.getElementById('viewName'+id).classList.add('d-none');
    document.getElementById('editForm'+id).classList.remove('d-none');
    document.getElementById('editTrigger'+id).classList.add('d-none');
    document.getElementById('deleteBtn'+id).classList.add('d-none');
}
function cancelEdit(id) {
    document.getElementById('viewName'+id).classList.remove('d-none');
    document.getElementById('editForm'+id).classList.add('d-none');
    document.getElementById('editTrigger'+id).classList.remove('d-none');
    document.getElementById('deleteBtn'+id).classList.remove('d-none');
}
function confirmDeptDelete(id, name, count) {
    if (count > 0) {
        document.getElementById('deptDelBody').innerHTML =
            '<strong class="text-danger">Cannot delete "' + name + '".</strong><br>' +
            count + ' student(s) are currently assigned to this department. Reassign or remove them first.';
        document.getElementById('deptDelLink').removeAttribute('href');
        document.getElementById('deptDelLink').style.display = 'none';
    } else {
        document.getElementById('deptDelBody').textContent = 'Delete "' + name + '"? This cannot be undone.';
        document.getElementById('deptDelLink').href = 'dept_delete.php?id=' + id;
        document.getElementById('deptDelLink').style.display = '';
    }
    new bootstrap.Modal(document.getElementById('deptDelModal')).show();
}
(function(){ var a=document.getElementById('flashAlert'); if(a) setTimeout(function(){bootstrap.Alert.getOrCreateInstance(a).close();},5000); })();
</script>
</body></html>
