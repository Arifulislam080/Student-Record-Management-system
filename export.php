<?php
require 'auth.php';
require_once 'db.php';
require_role(['admin']);

// ── Fetch all students ────────────────────────────────────────────────────────
$stmt     = $pdo->query("SELECT * FROM students ORDER BY id ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Set CSV headers ───────────────────────────────────────────────────────────
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');

// ── Output CSV ────────────────────────────────────────────────────────────────
$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fputs($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, ['ID', 'Name', 'Student ID', 'Email', 'Department', 'Created At']);

// Data rows
foreach ($students as $row) {
    fputcsv($out, [
        $row['id'],
        $row['name'],
        $row['student_id'],
        $row['email'],
        $row['department'],
        $row['created_at'],
    ]);
}

fclose($out);

log_action($pdo, 'Exported students CSV', 'students', null);
exit;
