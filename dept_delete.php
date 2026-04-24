<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: departments.php?msg=Invalid+ID&type=danger'); exit; }

$check = $pdo->prepare("SELECT id, name FROM departments WHERE id = :id");
$check->execute([':id' => $id]);
$dept = $check->fetch(PDO::FETCH_ASSOC);
if (!$dept) { header('Location: departments.php?msg=Department+not+found&type=danger'); exit; }

// Block deletion if students are assigned
$studentCount = (int)$pdo->prepare("SELECT COUNT(*) FROM students WHERE department = :name")
                          ->execute([':name' => $dept['name']]) ? 
                 $pdo->query("SELECT COUNT(*) FROM students WHERE department = '".$pdo->quote($dept['name'])."'")->fetchColumn() : 0;

$sc = $pdo->prepare("SELECT COUNT(*) FROM students WHERE department = :name");
$sc->execute([':name' => $dept['name']]);
$studentCount = (int)$sc->fetchColumn();

if ($studentCount > 0) {
    header('Location: departments.php?msg=Cannot+delete+department+with+assigned+students&type=danger');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM departments WHERE id = :id");
$stmt->execute([':id' => $id]);
log_action($pdo, 'Deleted department: '.$dept['name'], 'departments', $id);

header('Location: departments.php?msg=Department+deleted&type=success');
exit;
