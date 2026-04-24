<?php
require 'auth.php';
require_once 'db.php';

$role = $_SESSION['role'];

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalDepts    = (int)$pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$totalUsers    = ($role === 'admin') ? (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;
$thisMonth     = (int)$pdo->query(
    "SELECT COUNT(*) FROM students WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
)->fetchColumn();

// ── Chart data (dept breakdown) ───────────────────────────────────────────────
$chartRows = $pdo->query(
    "SELECT d.name, COUNT(s.id) AS total
     FROM departments d
     LEFT JOIN students s ON s.department = d.name
     GROUP BY d.name ORDER BY d.name"
)->fetchAll(PDO::FETCH_ASSOC);
$chartJson = json_encode($chartRows);

// ── Recent activity (admin) ───────────────────────────────────────────────────
$recentLogs = [];
if ($role === 'admin') {
    $recentLogs = $pdo->query(
        "SELECT al.action, al.created_at, al.ip_address, u.name AS user_name
         FROM activity_log al
         LEFT JOIN users u ON u.id = al.user_id
         ORDER BY al.id DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// ── Student's own record (student role) ──────────────────────────────────────
$myRecord = null;
if ($role === 'student' && !empty($_SESSION['student_ref_id'])) {
    $s = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $s->execute([':id' => $_SESSION['student_ref_id']]);
    $myRecord = $s->fetch(PDO::FETCH_ASSOC);
}

// ── Flash message ─────────────────────────────────────────────────────────────
$msg     = trim($_GET['msg']  ?? '');
$msgType = in_array($_GET['type'] ?? '', ['success','danger','warning']) ? $_GET['type'] : 'success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | StudentRec</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .sidebar { background:linear-gradient(180deg,#1e1b4b 0%,#312e81 100%); min-height:calc(100vh - 64px); padding:1.5rem 0; }
        .sidebar .nav-link { color:rgba(255,255,255,.7); font-size:.88rem; font-weight:500; padding:.6rem 1.4rem; border-radius:.4rem; margin:.15rem .6rem; transition:all .2s; display:flex; align-items:center; gap:.6rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background:rgba(255,255,255,.12); color:#fff; }
        .sidebar .nav-section { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.35); padding:.8rem 1.4rem .3rem; }
        .stat-card { border-radius:.75rem; padding:1.4rem 1.6rem; color:#fff; display:flex; align-items:center; gap:1.1rem; box-shadow:0 6px 20px rgba(0,0,0,.12); transition:transform .2s; }
        .stat-card:hover { transform:translateY(-3px); }
        .stat-icon { width:3rem; height:3rem; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .stat-value { font-size:1.9rem; font-weight:800; line-height:1; }
        .stat-label { font-size:.78rem; opacity:.8; font-weight:500; margin-top:.15rem; }
        .sc-blue   { background:linear-gradient(135deg,#4f46e5,#818cf8); }
        .sc-teal   { background:linear-gradient(135deg,#0891b2,#06b6d4); }
        .sc-green  { background:linear-gradient(135deg,#059669,#10b981); }
        .sc-orange { background:linear-gradient(135deg,#d97706,#f59e0b); }
        .chart-card { background:#fff; border-radius:.75rem; box-shadow:0 2px 12px rgba(0,0,0,.07); padding:1.5rem; border:1px solid #e2e8f0; }
        .log-table th { background:linear-gradient(90deg,#312e81,#4f46e5); color:#fff; font-size:.77rem; text-transform:uppercase; letter-spacing:.05em; }
        .log-table td { font-size:.84rem; vertical-align:middle; }
        .my-record-card { background:linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1px solid #bae6fd; border-radius:.75rem; padding:1.8rem; }
    </style>
</head>
<body>

<?php require '_nav.php'; ?>

<div class="container-fluid p-0">
    <div class="row g-0">

        <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
        <div class="col-lg-2 col-md-3 sidebar d-none d-md-block">
            <div class="pt-1">
                <span class="nav-section">Main</span>
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
                <a href="index.php"     class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php'     ? 'active' : '' ?>">🎓 Students</a>
                <a href="profile.php"   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php'   ? 'active' : '' ?>">👤 My Profile</a>

                <?php if ($role === 'admin'): ?>
                <span class="nav-section">Admin</span>
                <a href="departments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : '' ?>">🏫 Departments</a>
                <a href="users.php"       class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php'       ? 'active' : '' ?>">👥 Users</a>
                <a href="export.php"      class="nav-link">📥 Export CSV</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Main content ─────────────────────────────────────────────────── -->
        <div class="col-lg-10 col-md-9 p-4">

            <?php if ($msg !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($msgType) ?> alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
                <?= $msgType === 'danger' ? '❌' : '✅' ?> <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <h4 class="fw-bold mb-1" style="color:#1e293b;">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h4>
            <p class="text-muted mb-4" style="font-size:.88rem;">Here's what's happening in your system today.</p>

            <!-- ── Stat cards ────────────────────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card sc-blue">
                        <div class="stat-icon">🎓</div>
                        <div>
                            <div class="stat-value"><?= $totalStudents ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card sc-teal">
                        <div class="stat-icon">🏫</div>
                        <div>
                            <div class="stat-value"><?= $totalDepts ?></div>
                            <div class="stat-label">Departments</div>
                        </div>
                    </div>
                </div>
                <?php if ($role === 'admin'): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card sc-green">
                        <div class="stat-icon">👥</div>
                        <div>
                            <div class="stat-value"><?= $totalUsers ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card sc-orange">
                        <div class="stat-icon">📅</div>
                        <div>
                            <div class="stat-value"><?= $thisMonth ?></div>
                            <div class="stat-label">Added This Month</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Student role: own record ──────────────────────────────────── -->
            <?php if ($role === 'student'): ?>
                <?php if ($myRecord): ?>
                <div class="my-record-card mb-4">
                    <h5 class="fw-bold mb-3" style="color:#0c4a6e;">📋 My Student Record</h5>
                    <div class="row g-3">
                        <div class="col-sm-6"><span class="text-muted d-block" style="font-size:.78rem;">Full Name</span><strong><?= htmlspecialchars($myRecord['name']) ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted d-block" style="font-size:.78rem;">Student ID</span><strong><?= htmlspecialchars($myRecord['student_id']) ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted d-block" style="font-size:.78rem;">Email</span><strong><?= htmlspecialchars($myRecord['email']) ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted d-block" style="font-size:.78rem;">Department</span><strong><?= htmlspecialchars($myRecord['department']) ?></strong></div>
                        <div class="col-sm-6"><span class="text-muted d-block" style="font-size:.78rem;">Enrolled</span><strong><?= date('d M Y', strtotime($myRecord['created_at'])) ?></strong></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">No student record is linked to your account yet. Please contact an administrator.</div>
                <?php endif; ?>

            <?php else: ?>
            <!-- ── Chart (admin & teacher) ───────────────────────────────────── -->
            <div class="chart-card mb-4">
                <h6 class="fw-bold mb-3" style="color:#1e293b;">📊 Students by Department</h6>
                <canvas id="deptChart" height="80"></canvas>
            </div>
            <?php endif; ?>

            <!-- ── Recent Activity (admin only) ──────────────────────────────── -->
            <?php if ($role === 'admin' && !empty($recentLogs)): ?>
            <div class="chart-card">
                <h6 class="fw-bold mb-3" style="color:#1e293b;">🕐 Recent Activity</h6>
                <div class="table-responsive">
                    <table class="table log-table mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><span class="fw-semibold"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><code style="font-size:.78rem;"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></code></td>
                                <td class="text-muted" style="font-size:.8rem;"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /col main -->
    </div><!-- /row -->
</div><!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($role !== 'student'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const rows   = <?= $chartJson ?>;
    const labels = rows.map(r => r.name);
    const data   = rows.map(r => parseInt(r.total, 10));

    const colors = [
        'rgba(79,70,229,.8)','rgba(6,182,212,.8)','rgba(16,185,129,.8)',
        'rgba(245,158,11,.8)','rgba(239,68,68,.8)','rgba(139,92,246,.8)'
    ];

    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Students',
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.06)' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
