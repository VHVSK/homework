-- Homework board: MySQL 5.7+ / 8.x, InnoDB, utf8mb4.
-- Create an empty database in the hosting panel, then import this file.
-- The administrator is created only through the site installer.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(180) NOT NULL,
    city VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_schools_city_active (city, is_active),
    KEY idx_schools_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    google_sub VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL,
    role ENUM('admin', 'secretary', 'teacher') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    -- UNIQUE allows multiple NULL values, but only one row with the value 1.
    admin_guard TINYINT GENERATED ALWAYS AS (CASE WHEN role = 'admin' THEN 1 ELSE NULL END) STORED,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_google_sub (google_sub),
    UNIQUE KEY uq_single_admin (admin_guard),
    UNIQUE KEY uq_users_id_school (id, school_id),
    KEY idx_users_school_role_active (school_id, role, is_active),
    CONSTRAINT fk_users_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT UNSIGNED NOT NULL,
    name VARCHAR(60) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_school_name (school_id, name),
    UNIQUE KEY uq_classes_id_school (id, school_id),
    CONSTRAINT fk_classes_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#7265d7',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_school_name (school_id, name),
    UNIQUE KEY uq_subjects_id_school (id, school_id),
    CONSTRAINT fk_subjects_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teaching_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teaching_assignment (teacher_id, subject_id, class_id),
    KEY idx_assignments_school (school_id),
    KEY idx_assignments_teacher_school (teacher_id, school_id),
    KEY idx_assignments_subject_school (subject_id, school_id),
    KEY idx_assignments_class_school (class_id, school_id),
    CONSTRAINT fk_assignments_school FOREIGN KEY (school_id) REFERENCES schools (id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_teacher_school FOREIGN KEY (teacher_id, school_id) REFERENCES users (id, school_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_subject_school FOREIGN KEY (subject_id, school_id) REFERENCES subjects (id, school_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_class_school FOREIGN KEY (class_id, school_id) REFERENCES classes (id, school_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homework (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    published_date DATE NOT NULL,
    due_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_homework_chronology (published_date, id),
    KEY idx_homework_assignment_date (assignment_id, published_date, id),
    KEY idx_homework_due_date (due_date),
    CONSTRAINT fk_homework_assignment FOREIGN KEY (assignment_id) REFERENCES teaching_assignments (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_identifier_time (identifier_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
