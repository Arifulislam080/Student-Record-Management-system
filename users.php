<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$ROLES   = ['admin', 'teacher', 'student'];
$msg     = trim($_GET['msg']  ?? '');
$msgType = in_array($_GET['type'] ?? '', ['success','danger','warning']) ? $_GET['type'] : 'success';

$users = $pdo->query(
    "SELECT id, name, email, role, student_ref_id, created_at FROM users ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$roleColors = ['admin' => 'danger', 'teacher' => 'warning', 'student' => 'info'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | StudentRec</title>
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

    <div class="card-custom">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-people"></i></div>
            <h5>User Management</h5>
            <a href="user_create.php" class="ms-auto btn-submit" style="padding:.35rem 1rem;font-size:.82rem;">
                <i class="bi bi-person-plus me-1"></i>Add User
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Created At</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($users as $u): ?>
                    <tr>
                        <td><span class="badge-serial"><?= $i++ ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if (in_array($u['role'], $ROLES, true)): ?>
                            <span class="badge bg-<?= $roleColors[$u['role']] ?? 'secondary' ?> text-uppercase" style="font-size:.72rem;">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-date"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-center">
                            <a href="user_edit.php?id=<?= (int)$u['id'] ?>" class="btn-action btn-edit me-1" id="editUser<?= (int)$u['id'] ?>">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <button type="button" class="btn-action btn-delete" id="deleteUser<?= (int)$u['id'] ?>"
                                onclick="confirmUserDelete(<?= (int)$u['id'] ?>, '<?= addslashes(htmlspecialchars($u['name'])) ?>')">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.78rem;">(your account)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div>

<div class="modal fade" id="delUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:.75rem;border:none;overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(90deg,#fee2e2,#fff1f2);border-bottom:1px solid #fecdd3;">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Delete User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 px-4"><p class="mb-0 text-secondary" id="delUserBody"></p></div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="delUserLink" class="btn-submit" style="background:linear-gradient(90deg,#b91c1c,#ef4444);">
            <i class="bi bi-trash me-1"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>

<footer class="footer-bar">&copy; <?= date('Y') ?> StudentRec</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmUserDelete(id, name) {
    document.getElementById('delUserBody').textContent = 'Delete user "' + name + '"? This cannot be undone.';
    document.getElementById('delUserLink').href = 'user_delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('delUserModal')).show();
}
(function(){ var a=document.getElementById('flashAlert'); if(a) setTimeout(function(){bootstrap.Alert.getOrCreateInstance(a).close();},5000); })();
</script>
</body></html>
