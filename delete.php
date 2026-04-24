<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?msg=Invalid+record+ID&type=danger');
    exit;
}

$check = $pdo->prepare("SELECT id FROM students WHERE id = :id");
$check->execute([':id' => $id]);

if (!$check->fetch()) {
    header('Location: index.php?msg=Record+not+found&type=danger');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
$stmt->execute([':id' => $id]);

log_action($pdo, 'Deleted student ID: ' . $id, 'students', $id);

header('Location: index.php?msg=Student+deleted+successfully&type=success');
exit;
