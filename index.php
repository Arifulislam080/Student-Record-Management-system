<?php
require 'auth.php';
require_once 'db.php';

if ($_SESSION['role'] === 'student') { header('Location: dashboard.php'); exit; }

$search = trim($_GET['search'] ?? '');
$dept   = trim($_GET['dept']   ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$params = [];
$where  = [];
if ($search !== '') {
    $where[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR department LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}
if ($dept !== '') { $where[] = "department = ?"; $params[] = $dept; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students $whereSql");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page-1)*$limit; }

$dataStmt = $pdo->prepare("SELECT * FROM students $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
$dataStmt->execute($params);
$students = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$depts = $pdo->query("SELECT name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$msg     = trim($_GET['msg']  ?? '');
$msgType = in_array($_GET['type'] ?? '', ['success','danger']) ? $_GET['type'] : 'success';

function pageUrl(int $p, string $s, string $d): string {
    $q = http_build_query(array_filter(['search'=>$s,'dept'=>$d,'page'=>$p]));
    return 'index.php' . ($q ? "?$q" : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | StudentRec</title>
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

    <!-- Search & filter -->
    <form method="GET" action="index.php" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label-custom mb-1"><i class="bi bi-search me-1"></i>Search</label>
                <input type="text" name="search" class="form-control-custom" placeholder="Name, ID, Email, Dept…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label-custom mb-1"><i class="bi bi-building me-1"></i>Department</label>
                <select name="dept" class="form-control-custom">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                    <option value="<?= htmlspecialchars($d['name']) ?>" <?= $dept===$d['name']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn-submit" style="padding:.55rem 1rem;font-size:.85rem;"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="index.php" class="btn-cancel" style="padding:.55rem .8rem;font-size:.85rem;">Reset</a>
            </div>
            <div class="col-md-3 text-end">
                <a href="create.php" class="btn-submit" style="padding:.55rem 1.1rem;font-size:.85rem;display:inline-block;"><i class="bi bi-plus-circle me-1"></i>Add Student</a>
            </div>
        </div>
    </form>

    <div class="card-custom">
        <div class="card-header-custom">
            <div class="card-header-icon"><i class="bi bi-table"></i></div>
            <h5>Student Records</h5>
            <span class="ms-auto badge bg-primary rounded-pill"><?= $totalRecords ?> total</span>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="export.php" class="btn ms-2" style="font-size:.78rem;padding:.3rem .8rem;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:.4rem;font-weight:600;">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($students)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox empty-state-icon" style="font-size:3.5rem;opacity:.25;display:block;margin-bottom:1rem;"></i>
            <p>No records found. <?= ($search||$dept) ? '<a href="index.php">Clear filters</a>' : '<a href="create.php" class="text-primary fw-semibold">Add the first student</a>' ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Student ID</th><th>Email</th><th>Department</th><th>Created At</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php $serial = $offset+1; foreach ($students as $st): ?>
                    <tr>
                        <td><span class="badge-serial"><?= $serial++ ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($st['name']) ?></td>
                        <td><span class="badge-sid"><?= htmlspecialchars($st['student_id']) ?></span></td>
                        <td><a href="mailto:<?= htmlspecialchars($st['email']) ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($st['email']) ?></a></td>
                        <td><span class="badge-dept"><?= htmlspecialchars($st['department']) ?></span></td>
                        <td class="text-date"><?= date('d M Y', strtotime($st['created_at'])) ?></td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= (int)$st['id'] ?>" class="btn-action btn-edit me-1" id="editBtn<?= (int)$st['id'] ?>">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <button type="button" class="btn-action btn-delete" id="deleteBtn<?= (int)$st['id'] ?>"
                                onclick="confirmDelete(<?= (int)$st['id'] ?>, '<?= addslashes(htmlspecialchars($st['name'])) ?>')">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top" style="background:#f8fafc;">
            <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$limit,$totalRecords) ?> of <?= $totalRecords ?></small>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= pageUrl($page-1,$search,$dept) ?>">Prev</a></li>
                <?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= pageUrl($p,$search,$dept) ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= pageUrl($page+1,$search,$dept) ?>">Next</a></li>
            </ul></nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:.75rem;border:none;overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(90deg,#fee2e2,#fff1f2);border-bottom:1px solid #fecdd3;">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 px-4"><p class="mb-0 text-secondary" id="deleteModalBody"></p></div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteLink" class="btn-submit" style="background:linear-gradient(90deg,#b91c1c,#ef4444);">
            <i class="bi bi-trash me-1"></i>Delete
        </a>
      </div>
    </div>
  </div>
</div>

<footer class="footer-bar">&copy; <?= date('Y') ?> Student Record Management System</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteModalBody').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('confirmDeleteLink').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
(function(){ var a=document.getElementById('flashAlert'); if(a) setTimeout(function(){bootstrap.Alert.getOrCreateInstance(a).close();},5000); })();
</script>
</body></html>
