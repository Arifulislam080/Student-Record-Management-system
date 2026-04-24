<?php
// Shared navbar partial — included by every protected page.
// Bootstrap Icons must be loaded in the <head> of the parent page.
$_roleColors = ['admin' => 'danger', 'teacher' => 'warning', 'student' => 'info'];
$_badge      = $_roleColors[$_SESSION['role'] ?? 'student'] ?? 'secondary';
$_cur        = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid px-4">
        <a class="navbar-brand-text" href="dashboard.php">
            <i class="bi bi-mortarboard-fill me-1"></i>Student<span>Rec</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <i class="bi bi-list" style="color:#fff;font-size:1.4rem;"></i>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-center gap-2 py-2 py-lg-0">
                <li class="nav-item">
                    <span class="badge bg-<?= $_badge ?> text-uppercase px-3 py-2" style="font-size:.72rem;letter-spacing:.07em;">
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                </li>
                <li class="nav-item">
                    <span class="text-white fw-semibold" style="font-size:.92rem;">
                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($_SESSION['name']) ?>
                    </span>
                </li>
                <li class="nav-item ms-2">
                    <a href="profile.php" class="btn btn-nav-add" style="font-size:.82rem;padding:.35rem .9rem;">
                        <i class="bi bi-gear me-1"></i>Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn" style="font-size:.82rem;padding:.35rem .9rem;background:rgba(239,68,68,.18);color:#fca5a5;border:1px solid rgba(239,68,68,.35);">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
