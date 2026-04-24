<?php
/**
 * auth.php — Session guard & helper functions.
 * Include this as the FIRST line in every protected page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Redirect to login if not authenticated ────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Role guard ────────────────────────────────────────────────────────────────
/**
 * Aborts with a redirect if the logged-in user's role is not in $roles.
 */
function require_role(array $roles): void
{
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header('Location: dashboard.php?msg=Access+denied.+You+do+not+have+permission+to+view+that+page.&type=danger');
        exit;
    }
}

// ── Activity logger ───────────────────────────────────────────────────────────
/**
 * Inserts a row into activity_log via a prepared statement.
 */
function log_action(PDO $pdo, string $action, string $table = null, int $target_id = null): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO activity_log (user_id, action, target_table, target_id, ip_address)
         VALUES (:user_id, :action, :target_table, :target_id, :ip)"
    );
    $stmt->execute([
        ':user_id'      => $_SESSION['user_id'],
        ':action'       => $action,
        ':target_table' => $table,
        ':target_id'    => $target_id,
        ':ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
