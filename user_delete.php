<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: users.php?msg=Invalid+ID&type=danger');
    exit;
}

// Prevent self-deletion
if ((int)$id === (int)$_SESSION['user_id']) {
    header('Location: users.php?msg=Cannot+delete+your+own+account&type=danger');
    exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE id = :id");
$check->execute([':id' => $id]);
if (!$check->fetch()) {
    header('Location: users.php?msg=User+not+found&type=danger');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);

log_action($pdo, 'Deleted user ID: ' . $id, 'users', $id);

header('Location: users.php?msg=User+deleted+successfully&type=success');
exit;
