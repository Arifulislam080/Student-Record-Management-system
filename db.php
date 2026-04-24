<?php

$host     = 'localhost';
$dbName   = 'student_crud';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // ── Students ──────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
        `id`          INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(100)  NOT NULL,
        `student_id`  VARCHAR(50)   NOT NULL UNIQUE,
        `email`       VARCHAR(100)  NOT NULL,
        `department`  VARCHAR(100)  NOT NULL,
        `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Departments table ─────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `id`         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name`       VARCHAR(100) NOT NULL UNIQUE,
        `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Users ─────────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`             INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name`           VARCHAR(100) NOT NULL,
        `email`          VARCHAR(100) NOT NULL UNIQUE,
        `password_hash`  VARCHAR(255) NOT NULL,
        `role`           ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
        `student_ref_id` INT          NULL,
        `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_user_student`
            FOREIGN KEY (`student_ref_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Activity log ──────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_log` (
        `id`           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id`      INT          NOT NULL,
        `action`       VARCHAR(255) NOT NULL,
        `target_table` VARCHAR(50)  NULL,
        `target_id`    INT          NULL,
        `ip_address`   VARCHAR(45)  NULL,
        `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Subjects ──────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `subjects` (
        `id`            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name`          VARCHAR(100) NOT NULL,
        `code`          VARCHAR(20)  NOT NULL UNIQUE,
        `department_id` INT          NOT NULL,
        `teacher_id`    INT          NULL,
        `credit_hours`  INT          NOT NULL DEFAULT 3,
        `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_subj_dept`
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_subj_teacher`
            FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Attendance ────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance` (
        `id`         INT                              NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT                              NOT NULL,
        `subject_id` INT                              NOT NULL,
        `date`       DATE                             NOT NULL,
        `status`     ENUM('present','absent','late')  NOT NULL DEFAULT 'present',
        `marked_by`  INT                              NOT NULL,
        `created_at` TIMESTAMP                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_attendance` (`student_id`, `subject_id`, `date`),
        CONSTRAINT `fk_att_student`
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_att_subject`
            FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_att_marker`
            FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Results ───────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `results` (
        `id`             INT                                         NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `student_id`     INT                                         NOT NULL,
        `subject_id`     INT                                         NOT NULL,
        `exam_type`      ENUM('quiz','midterm','final','assignment')  NOT NULL,
        `marks_obtained` DECIMAL(5,2)                               NOT NULL,
        `total_marks`    DECIMAL(5,2)                               NOT NULL DEFAULT 100,
        `grade`          VARCHAR(5)                                  NULL,
        `remarks`        VARCHAR(255)                                NULL,
        `entered_by`     INT                                         NOT NULL,
        `created_at`     TIMESTAMP                                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_res_student`
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_res_subject`
            FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Documents ─────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `documents` (
        `id`            INT                                              NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `student_id`    INT                                              NOT NULL,
        `uploaded_by`   INT                                              NOT NULL,
        `doc_type`      ENUM('certificate','id_card','transcript','other') NOT NULL,
        `title`         VARCHAR(255)                                     NOT NULL,
        `file_name`     VARCHAR(255)                                     NOT NULL,
        `original_name` VARCHAR(255)                                     NOT NULL,
        `file_size`     INT                                              NOT NULL,
        `mime_type`     VARCHAR(100)                                     NOT NULL,
        `created_at`    TIMESTAMP                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_doc_student`
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Materials ─────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `materials` (
        `id`            INT                                            NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `subject_id`    INT                                            NOT NULL,
        `uploaded_by`   INT                                            NOT NULL,
        `title`         VARCHAR(255)                                   NOT NULL,
        `description`   TEXT                                           NULL,
        `material_type` ENUM('notes','slides','assignment','other')    NOT NULL,
        `file_name`     VARCHAR(255)                                   NOT NULL,
        `original_name` VARCHAR(255)                                   NOT NULL,
        `file_size`     INT                                            NOT NULL,
        `mime_type`     VARCHAR(100)                                   NOT NULL,
        `created_at`    TIMESTAMP                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_mat_subject`
            FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Notices ───────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notices` (
        `id`          INT                              NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title`       VARCHAR(255)                     NOT NULL,
        `body`        TEXT                             NOT NULL,
        `posted_by`   INT                              NOT NULL,
        `target_role` ENUM('all','student','teacher')  NOT NULL DEFAULT 'all',
        `is_pinned`   TINYINT(1)                       NOT NULL DEFAULT 0,
        `expires_at`  DATE                             NULL,
        `created_at`  TIMESTAMP                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_notice_user`
            FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Seed default admin ────────────────────────────────────────────────────
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)"
        );
        $stmt->execute([':name'=>'Administrator',':email'=>'admin@admin.com',':hash'=>$hash,':role'=>'admin']);
    }

    // ── Seed default departments ──────────────────────────────────────────────
    $pdo->exec("INSERT IGNORE INTO departments (name) VALUES
        ('Computer Science'), ('Business Administration'), ('Engineering')");

    // ── Seed default subject ──────────────────────────────────────────────────
    $pdo->exec("INSERT IGNORE INTO subjects (name, code, department_id, credit_hours)
        VALUES ('Introduction to Computing', 'CS101', 1, 3)");

} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:.4rem;">
            <strong>Database connection failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
         </div>');
}
