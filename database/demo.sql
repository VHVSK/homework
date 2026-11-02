-- After the Bell: FICTIONAL sample data HW-DEMO-v2.
-- Import schema.sql first. Sequential reimports do not update or delete records.
-- Clean schema: 10 schools, 70 users, 40 classes, 60 subjects, 240 assignments, 720 homework entries.
-- All institutions, addresses and staff are fictional. @example.invalid is not a working mailbox.
-- No passwords or Google IDs are set. This file does not create an administrator.
-- An existing older single-school demo is left unchanged.
-- Reimport rules and verification queries: database/demo.README.md.
SET NAMES utf8mb4;
START TRANSACTION;
SET @demo_today = CURDATE();

-- 01. Fictional demonstration school in Kyiv.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-01-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційний ліцей «Обрій знань»', 'Київ', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-01. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-01. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-01. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Олена Демо-Коваль' AS name, 'hw-demo-01-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Андрій Демо-Левченко', 'hw-demo-01-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Ірина Демо-Мельник', 'hw-demo-01-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Тарас Демо-Романюк', 'hw-demo-01-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Софія Демо-Бондар', 'hw-demo-01-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Павло Демо-Шевчук', 'hw-demo-01-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Наталія Демо-Гнатюк', 'hw-demo-01-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-01-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-01-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-01-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-01-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-01-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-01-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 02. Fictional demonstration school in Lviv.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-02-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційна гімназія «Майстерня думок»', 'Львів', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-02. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-02. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-02. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Богдан Демо-Романюк' AS name, 'hw-demo-02-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Лілія Демо-Бондар', 'hw-demo-02-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Дмитро Демо-Шевчук', 'hw-demo-02-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Аліна Демо-Гнатюк', 'hw-demo-02-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Роман Демо-Ткачук', 'hw-demo-02-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Вікторія Демо-Мороз', 'hw-demo-02-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Максим Демо-Олійник', 'hw-demo-02-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-02-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-02-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-02-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-02-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-02-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-02-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 03. Fictional demonstration school in Odesa.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-03-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційний ліцей «Морський компас»', 'Одеса', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-03. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-03. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-03. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Катерина Демо-Гнатюк' AS name, 'hw-demo-03-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Михайло Демо-Ткачук', 'hw-demo-03-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Валерія Демо-Мороз', 'hw-demo-03-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Артем Демо-Олійник', 'hw-demo-03-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Людмила Демо-Сидоренко', 'hw-demo-03-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Євген Демо-Кравчук', 'hw-demo-03-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Олена Демо-Лисенко', 'hw-demo-03-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-03-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-03-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-03-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-03-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-03-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-03-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 04. Fictional demonstration school in Dnipro.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-04-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційна школа «Сузір’я відкриттів»', 'Дніпро', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-04. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-04. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-04. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Андрій Демо-Олійник' AS name, 'hw-demo-04-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Ірина Демо-Сидоренко', 'hw-demo-04-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Тарас Демо-Кравчук', 'hw-demo-04-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Софія Демо-Лисенко', 'hw-demo-04-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Павло Демо-Петренко', 'hw-demo-04-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Наталія Демо-Шульга', 'hw-demo-04-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Богдан Демо-Гриценко', 'hw-demo-04-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-04-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-04-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-04-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-04-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-04-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-04-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 05. Fictional demonstration school in Vinnytsia.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-05-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційний ліцей «Крок до мрії»', 'Вінниця', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-05. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-05. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-05. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Лілія Демо-Лисенко' AS name, 'hw-demo-05-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Дмитро Демо-Петренко', 'hw-demo-05-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Аліна Демо-Шульга', 'hw-demo-05-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Роман Демо-Гриценко', 'hw-demo-05-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Вікторія Демо-Білик', 'hw-demo-05-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Максим Демо-Василенко', 'hw-demo-05-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Катерина Демо-Козак', 'hw-demo-05-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-05-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-05-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-05-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-05-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-05-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-05-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 06. Fictional demonstration school in Poltava.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-06-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційна гімназія «Джерело ідей»', 'Полтава', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-06. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-06. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-06. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Михайло Демо-Гриценко' AS name, 'hw-demo-06-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Валерія Демо-Білик', 'hw-demo-06-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Артем Демо-Василенко', 'hw-demo-06-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Людмила Демо-Козак', 'hw-demo-06-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Євген Демо-Мазур', 'hw-demo-06-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Олена Демо-Коваль', 'hw-demo-06-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Андрій Демо-Левченко', 'hw-demo-06-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-06-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-06-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-06-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-06-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-06-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-06-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 07. Fictional demonstration school in Chernivtsi.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-07-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційний ліцей «Барви навчання»', 'Чернівці', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-07. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-07. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-07. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Ірина Демо-Козак' AS name, 'hw-demo-07-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Тарас Демо-Мазур', 'hw-demo-07-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Софія Демо-Коваль', 'hw-demo-07-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Павло Демо-Левченко', 'hw-demo-07-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Наталія Демо-Мельник', 'hw-demo-07-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Богдан Демо-Романюк', 'hw-demo-07-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Лілія Демо-Бондар', 'hw-demo-07-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-07-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-07-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-07-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-07-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-07-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-07-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 08. Fictional demonstration school in Ivano-Frankivsk.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-08-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційна школа «Вершина пошуку»', 'Івано-Франківськ', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-08. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-08. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-08. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Дмитро Демо-Левченко' AS name, 'hw-demo-08-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Аліна Демо-Мельник', 'hw-demo-08-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Роман Демо-Романюк', 'hw-demo-08-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Вікторія Демо-Бондар', 'hw-demo-08-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Максим Демо-Шевчук', 'hw-demo-08-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Катерина Демо-Гнатюк', 'hw-demo-08-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Михайло Демо-Ткачук', 'hw-demo-08-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-08-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-08-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-08-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-08-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-08-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-08-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 09. Fictional demonstration school in Cherkasy.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-09-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційний ліцей «Паперовий кораблик»', 'Черкаси', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-09. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-09. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-09. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Валерія Демо-Бондар' AS name, 'hw-demo-09-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Артем Демо-Шевчук', 'hw-demo-09-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Людмила Демо-Гнатюк', 'hw-demo-09-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Євген Демо-Ткачук', 'hw-demo-09-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Олена Демо-Мороз', 'hw-demo-09-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Андрій Демо-Олійник', 'hw-demo-09-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Ірина Демо-Сидоренко', 'hw-demo-09-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-09-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-09-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-09-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-09-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-09-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-09-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- 10. Fictional demonstration school in Ternopil.
SET @demo_school = (SELECT school_id FROM users WHERE email = 'hw-demo-10-secretary@example.invalid' AND role = 'secretary' AND school_id IS NOT NULL LIMIT 1);
INSERT INTO schools (name, city, address) SELECT 'Демонстраційна гімназія «Ранкова зірка»', 'Тернопіль', 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-10. Вигадана адреса; школа не існує.' WHERE @demo_school IS NULL AND NOT EXISTS (SELECT 1 FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-10. Вигадана адреса; школа не існує.');
SET @demo_school = COALESCE(@demo_school, (SELECT id FROM schools WHERE address = 'ДЕМОНСТРАЦІЙНИЙ ЗАПИС HW-DEMO-10. Вигадана адреса; школа не існує.' ORDER BY id LIMIT 1));
INSERT INTO users (school_id, name, email, role, password_hash, google_sub)
SELECT @demo_school, d.name, d.email, d.role, NULL, NULL FROM (
    SELECT 'Тарас Демо-Ткачук' AS name, 'hw-demo-10-secretary@example.invalid' AS email, 'secretary' AS role
    UNION ALL SELECT 'Софія Демо-Мороз', 'hw-demo-10-teacher-1@example.invalid', 'teacher'
    UNION ALL SELECT 'Павло Демо-Олійник', 'hw-demo-10-teacher-2@example.invalid', 'teacher'
    UNION ALL SELECT 'Наталія Демо-Сидоренко', 'hw-demo-10-teacher-3@example.invalid', 'teacher'
    UNION ALL SELECT 'Богдан Демо-Кравчук', 'hw-demo-10-teacher-4@example.invalid', 'teacher'
    UNION ALL SELECT 'Лілія Демо-Лисенко', 'hw-demo-10-teacher-5@example.invalid', 'teacher'
    UNION ALL SELECT 'Дмитро Демо-Петренко', 'hw-demo-10-teacher-6@example.invalid', 'teacher'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.email);
INSERT INTO classes (school_id, name, sort_order) SELECT @demo_school, d.name, d.sort_order FROM (
    SELECT '5-А' AS name, 51 AS sort_order
    UNION ALL SELECT '7-А', 71
    UNION ALL SELECT '8-Б', 82
    UNION ALL SELECT '10-А', 101
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM classes c WHERE c.school_id = @demo_school AND c.name = d.name);
INSERT INTO subjects (school_id, name, color) SELECT @demo_school, d.name, d.color FROM (
    SELECT 'Математика' AS name, '#7265d7' AS color
    UNION ALL SELECT 'Українська мова', '#df8c4a'
    UNION ALL SELECT 'Англійська мова', '#548ec4'
    UNION ALL SELECT 'Історія', '#c06d8b'
    UNION ALL SELECT 'Інформатика', '#568e80'
    UNION ALL SELECT 'Географія', '#779645'
) AS d WHERE @demo_school IS NOT NULL AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.school_id = @demo_school AND s.name = d.name);
-- One teacher per subject across four classes: 24 teaching assignments per school.
INSERT INTO teaching_assignments (school_id, teacher_id, subject_id, class_id)
SELECT @demo_school, u.id, s.id, c.id FROM (
    SELECT 'Математика' AS subject_name, 'hw-demo-10-teacher-1@example.invalid' AS teacher_email
    UNION ALL SELECT 'Українська мова', 'hw-demo-10-teacher-2@example.invalid'
    UNION ALL SELECT 'Англійська мова', 'hw-demo-10-teacher-3@example.invalid'
    UNION ALL SELECT 'Історія', 'hw-demo-10-teacher-4@example.invalid'
    UNION ALL SELECT 'Інформатика', 'hw-demo-10-teacher-5@example.invalid'
    UNION ALL SELECT 'Географія', 'hw-demo-10-teacher-6@example.invalid'
) AS d
JOIN users u ON u.email = d.teacher_email AND u.school_id = @demo_school AND u.role = 'teacher'
JOIN subjects s ON s.name = d.subject_name AND s.school_id = @demo_school
JOIN classes c ON c.school_id = @demo_school AND c.name IN ('5-А', '7-А', '8-Б', '10-А')
WHERE NOT EXISTS (SELECT 1 FROM teaching_assignments a WHERE a.teacher_id = u.id AND a.subject_id = s.id AND a.class_id = c.id);

-- Shared library: three age-appropriate tasks per subject and class, 720 in total.
-- Publications: today to seven days ago; deadlines: six days ago to five days ahead.
-- Current dates apply only to NEW records; existing dates are preserved.
INSERT INTO homework (assignment_id, title, body_html, published_date, due_date)
SELECT a.id, d.title,
    CONCAT(d.body_html, '<p><em>Демонстраційне завдання HW-DEMO-', LPAD(m.school_no, 2, '0'), '. Усі дані вигадані.</em></p>'),
    DATE_ADD(@demo_today, INTERVAL (d.published_offset - MOD(m.school_no - 1, 3)) DAY),
    DATE_ADD(@demo_today, INTERVAL (d.due_offset - MOD(m.school_no - 1, 3)) DAY)
FROM (
    SELECT 1 AS teacher_no, 'Математика' AS subject_name, '5-А' AS class_name, 'Натуральні числа та порядок дій' AS title, '<p>Обчисліть: <strong>48 + 36 : 4</strong>, <strong>(72 − 18) : 6</strong>, <strong>125 − 5 · 9</strong>. Запишіть порядок дій.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>' AS body_html, 0 AS published_offset, 1 AS due_offset
    UNION ALL SELECT 1, 'Математика', '5-А', 'Звичайні дроби на малюнку', '<p>Намалюйте три однакові прямокутники. Зафарбуйте <strong>1/2, 1/4 та 3/4</strong> кожного. Поясніть, який дріб найбільший.</p>', -2, 0
    UNION ALL SELECT 1, 'Математика', '5-А', 'Периметр і площа прямокутника', '<p>Обчисліть периметр і площу прямокутника зі сторонами <strong>8 см і 5 см</strong>. Складіть власну подібну задачу.</p>', -5, -2
    UNION ALL SELECT 1, 'Математика', '7-А', 'Лінійні рівняння з однією змінною', '<p>Розв’яжіть <strong>3x + 7 = 22</strong>, <strong>5(x − 2) = 20</strong>, <strong>4x − 9 = 2x + 5</strong>. Перевірте корені підстановкою.</p>', 0, 2
    UNION ALL SELECT 1, 'Математика', '7-А', 'Степінь із натуральним показником', '<p>Спростіть <strong>a³ · a²</strong>, <strong>(b²)³</strong>, <strong>c⁷ : c⁴</strong>. Запишіть використані властивості степенів.</p>', -2, 1
    UNION ALL SELECT 1, 'Математика', '7-А', 'Сума кутів трикутника', '<p>Два кути трикутника дорівнюють <strong>48° і 67°</strong>. Знайдіть третій. Накресліть інший трикутник і перевірте суму його кутів.</p>', -5, -1
    UNION ALL SELECT 1, 'Математика', '8-Б', 'Квадратні корені та їхні властивості', '<p>Обчисліть <strong>√144, √225, √(16 · 25)</strong>. Поясніть відмінність між арифметичним квадратним коренем і коренями рівняння x² = 49.</p>', 0, 3
    UNION ALL SELECT 1, 'Математика', '8-Б', 'Квадратні рівняння: формула коренів', '<p>Розв’яжіть <strong>x² − 5x + 6 = 0</strong> та <strong>2x² + 3x − 2 = 0</strong>. Для кожного рівняння запишіть дискримінант.</p>', -2, 2
    UNION ALL SELECT 1, 'Математика', '8-Б', 'Теорема Піфагора у практичній задачі', '<p>Катети прямокутного трикутника дорівнюють <strong>6 см і 8 см</strong>. Знайдіть гіпотенузу. Складіть задачу про діагональ прямокутника.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -5, 0
    UNION ALL SELECT 1, 'Математика', '10-А', 'Функції: область визначення', '<p>Знайдіть область визначення <strong>y = 1/(x − 3)</strong> та <strong>y = √(x + 2)</strong>. Відповіді запишіть за допомогою проміжків.</p>', 0, 4
    UNION ALL SELECT 1, 'Математика', '10-А', 'Тригонометричне коло', '<p>Позначте на одиничному колі кути <strong>30°, 90°, 150° і 270°</strong>. Запишіть значення синуса та косинуса.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 3
    UNION ALL SELECT 1, 'Математика', '10-А', 'Паралельність прямих і площин', '<p>Побудуйте рисунок куба ABCDA₁B₁C₁D₁. Наведіть три пари паралельних прямих і дві пари паралельних площин. Обґрунтуйте одну відповідь.</p>', -5, -4
    UNION ALL SELECT 2, 'Українська мова', '5-А', 'Будова слова: корінь і закінчення', '<p>Доберіть по три спільнокореневі слова до слів <strong>ліс, вода, школа</strong>. Позначте корінь і закінчення.</p>', 0, 2
    UNION ALL SELECT 2, 'Українська мова', '5-А', 'Синоніми роблять текст виразнішим', '<p>Замініть повтори слова <strong>гарний</strong> у п’яти реченнях власного тексту. Підкресліть дібрані синоніми.</p>', -2, 1
    UNION ALL SELECT 2, 'Українська мова', '5-А', 'Текст-опис улюбленої пори року', '<p>Напишіть <strong>6–8 речень</strong> про улюблену пору року. Використайте три прикметники та придумайте заголовок.</p>', -5, -1
    UNION ALL SELECT 2, 'Українська мова', '7-А', 'Дієслово: вид і час', '<p>Випишіть із навчального тексту <strong>8 дієслів</strong>. Визначте їхній вид, час і початкову форму.</p>', 0, 3
    UNION ALL SELECT 2, 'Українська мова', '7-А', 'Дієприкметник: ознаки та правопис', '<p>Утворіть дієприкметники від слів <strong>прочитати, написати, намалювати, зберегти</strong>. Складіть із двома з них речення.</p>', -2, 2
    UNION ALL SELECT 2, 'Українська мова', '7-А', 'Прислівник у власному висловлюванні', '<p>Напишіть мінітекст <strong>«Як я планую навчальний день»</strong> обсягом 7–9 речень. Підкресліть п’ять прислівників.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -5, 0
    UNION ALL SELECT 2, 'Українська мова', '8-Б', 'Головні та другорядні члени речення', '<p>Складіть <strong>5 поширених речень</strong> про осінній парк. Підкресліть граматичні основи, додатки, означення та обставини.</p>', 0, 4
    UNION ALL SELECT 2, 'Українська мова', '8-Б', 'Однорідні члени та розділові знаки', '<p>Складіть чотири речення з однорідними членами: два зі сполучниками і два без сполучників. Поясніть уживання ком.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 3
    UNION ALL SELECT 2, 'Українська мова', '8-Б', 'Звертання і вставні слова', '<p>Напишіть короткий лист однокласнику або однокласниці. Використайте <strong>два звертання</strong> і <strong>два вставні слова</strong>. Позначте розділові знаки.</p>', -5, -4
    UNION ALL SELECT 2, 'Українська мова', '10-А', 'Наголос і мовна норма', '<p>Доберіть <strong>10 слів</strong>, у наголошуванні яких ви сумніваєтеся. Перевірте їх за орфоепічним словником і позначте наголоси.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', 0, 5
    UNION ALL SELECT 2, 'Українська мова', '10-А', 'Редагування власного тексту', '<p>Напишіть абзац про улюблене заняття. Виправте повтори, неточне слововживання й порушення милозвучності. Покажіть три приклади редагування.</p>', -2, -1
    UNION ALL SELECT 2, 'Українська мова', '10-А', 'Есе: відповідальне спілкування', '<p>Напишіть есе <strong>120–150 слів</strong> на тему «Як слова впливають на співрозмовника». Сформулюйте тезу, два аргументи та висновок.</p>', -5, -3
    UNION ALL SELECT 3, 'Англійська мова', '5-А', 'My daily routine: Present Simple', '<p>Write <strong>6 sentences</strong> about your daily routine. Use always, usually and sometimes. Underline the verbs in the Present Simple.</p>', 0, 3
    UNION ALL SELECT 3, 'Англійська мова', '5-А', 'My room: there is and there are', '<p>Draw a simple room plan and write <strong>5 sentences</strong> with there is / there are. Use three prepositions of place.</p>', -2, 2
    UNION ALL SELECT 3, 'Англійська мова', '5-А', 'A friendly email', '<p>Write an email to an imaginary pen friend: introduce yourself, describe one hobby and ask two questions. Length: <strong>50–60 words</strong>.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -5, 0
    UNION ALL SELECT 3, 'Англійська мова', '7-А', 'Past Simple and Past Continuous', '<p>Write <strong>6 sentences</strong> about a fictional journey. Use the Past Simple in three sentences and the Past Continuous in three sentences.</p>', 0, 4
    UNION ALL SELECT 3, 'Англійська мова', '7-А', 'Healthy habits: giving advice', '<p>List <strong>6 healthy study habits</strong>. Give advice using should / should not. Explain two of your suggestions.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 3
    UNION ALL SELECT 3, 'Англійська мова', '7-А', 'Planning a school trip', '<p>Write a dialogue of <strong>8–10 lines</strong> about an imaginary school trip. Include a destination, transport, time and two suggestions.</p>', -5, -4
    UNION ALL SELECT 3, 'Англійська мова', '8-Б', 'Present Perfect: life experiences', '<p>Write five statements about imaginary experiences using <strong>have / has + past participle</strong>. Add two questions with ever.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', 0, 5
    UNION ALL SELECT 3, 'Англійська мова', '8-Б', 'Comparing books and films', '<p>Compare an imaginary book and its film adaptation in <strong>80–100 words</strong>. Use four comparative or superlative forms.</p>', -2, -1
    UNION ALL SELECT 3, 'Англійська мова', '8-Б', 'An environmental poster', '<p>Write a text for an environmental poster. Include a heading, <strong>4 practical actions</strong> and an invitation to participate.</p>', -5, -3
    UNION ALL SELECT 3, 'Англійська мова', '10-А', 'Future careers: a personal profile', '<p>Write a fictional career profile of <strong>100–120 words</strong>. Explain interests, skills, a possible profession and two learning goals.</p>', 0, 1
    UNION ALL SELECT 3, 'Англійська мова', '10-А', 'Conditionals: choices and consequences', '<p>Write <strong>4 first conditional</strong> and <strong>4 second conditional</strong> sentences about learning, travel or future plans.</p>', -2, 0
    UNION ALL SELECT 3, 'Англійська мова', '10-А', 'A formal email requesting information', '<p>Write a formal email of <strong>120–150 words</strong> about an imaginary educational course. Include a subject, polite opening, three questions and a closing.</p>', -5, -2
    UNION ALL SELECT 4, 'Історія', '5-А', 'Історичний час і стрічка подій', '<p>Побудуйте стрічку часу з <strong>п’яти подій вигаданого навчального року</strong>. Поясніть, чим дата відрізняється від періоду.</p>', 0, 4
    UNION ALL SELECT 4, 'Історія', '5-А', 'Історичні джерела навколо нас', '<p>Доберіть по два приклади речових, писемних та усних джерел. Поясніть, яку інформацію дає кожне джерело.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 3
    UNION ALL SELECT 4, 'Історія', '5-А', 'Пам’ятка та її історія', '<p>Оберіть пам’ятку з підручника. Складіть картку: назва, приблизний час створення, призначення та <strong>два запитання дослідника</strong>.</p>', -5, -4
    UNION ALL SELECT 4, 'Історія', '7-А', 'Київська Русь: міста та ремесла', '<p>Прочитайте відповідний розділ підручника. Складіть таблицю <strong>«Ремесло — виріб — значення для міста»</strong> із п’яти рядків.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', 0, 5
    UNION ALL SELECT 4, 'Історія', '7-А', 'Культура Русі: архітектура і писемність', '<p>Підготуйте повідомлення обсягом <strong>8–10 речень</strong> про одну архітектурну або писемну пам’ятку Русі. Зазначте джерело.</p>', -2, -1
    UNION ALL SELECT 4, 'Історія', '7-А', 'Середньовічне суспільство', '<p>Порівняйте життя містянина та селянина за трьома ознаками. Сформулюйте один спільний висновок.</p>', -5, -3
    UNION ALL SELECT 4, 'Історія', '8-Б', 'Українські землі в ранньомодерний час', '<p>Складіть хронологічну таблицю з <strong>п’яти подій</strong> теми, яку вивчали на уроці. Для кожної зазначте причину або наслідок.</p>', 0, 1
    UNION ALL SELECT 4, 'Історія', '8-Б', 'Козацька Січ: устрій і традиції', '<p>Намалюйте схему козацького самоврядування. Поясніть роль ради, кошового отамана та військової старшини.</p>', -2, 0
    UNION ALL SELECT 4, 'Історія', '8-Б', 'Як працювати з історичним документом', '<p>Оберіть уривок джерела з підручника. Визначте автора, час створення, головну думку і <strong>два запитання до тексту</strong>.</p>', -5, -2
    UNION ALL SELECT 4, 'Історія', '10-А', 'Українська революція: послідовність подій', '<p>За підручником складіть хронологію <strong>1917–1921 років</strong> із шести ключових подій. Відокремте факт від власної оцінки.</p>', 0, 2
    UNION ALL SELECT 4, 'Історія', '10-А', 'Історична карта як джерело', '<p>Опрацюйте карту з поточної теми. Запишіть три висновки з легенди та умовних позначень. Зазначте, чого карта не пояснює.</p>', -2, 1
    UNION ALL SELECT 4, 'Історія', '10-А', 'Порівнюємо історичні свідчення', '<p>Порівняйте два джерела з підручника про одну подію. Визначте спільне, відмінне та причини різних поглядів. Обсяг: <strong>150–180 слів</strong>.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -5, -1
    UNION ALL SELECT 5, 'Інформатика', '5-А', 'Файли й папки: упорядковуємо матеріали', '<p>Створіть навчальну папку з трьома вкладеними папками. Запишіть приклади зрозумілих назв файлів. Не завантажуйте приватних даних.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', 0, 5
    UNION ALL SELECT 5, 'Інформатика', '5-А', 'Алгоритм із повсякденного життя', '<p>Складіть алгоритм із <strong>7–9 команд</strong> для підготовки до уроку. Позначте початок і завершення. Перевірте однозначність команд.</p>', -2, -1
    UNION ALL SELECT 5, 'Інформатика', '5-А', 'Безпека в інтернеті', '<p>Складіть пам’ятку з <strong>п’яти правил</strong> безпечного спілкування онлайн. Для кожного наведіть вигаданий приклад.</p><p><a href="https://drive.google.com/" target="_blank" rel="noopener noreferrer">Google Drive: приклад посилання без навчального файлу</a>.</p>', -5, -3
    UNION ALL SELECT 5, 'Інформатика', '7-А', 'Електронні таблиці: прості формули', '<p>Створіть таблицю витрат для вигаданого класного заходу: назва, кількість, ціна, вартість. Використайте формулу множення та підсумок. Суми вигадайте.</p>', 0, 1
    UNION ALL SELECT 5, 'Інформатика', '7-А', 'Блок-схема з розгалуженням', '<p>Побудуйте блок-схему перевірки, чи є введене число додатним. Покажіть гілки <strong>«так»</strong> і <strong>«ні»</strong>.</p>', -2, 0
    UNION ALL SELECT 5, 'Інформатика', '7-А', 'Перевіряємо інформацію онлайн', '<p>Опишіть три способи перевірки навчальної публікації: автор, дата, посилання на джерела. Складіть контрольний список.</p><p><a href="https://drive.google.com/" target="_blank" rel="noopener noreferrer">Google Drive: приклад посилання без навчального файлу</a>.</p>', -5, -2
    UNION ALL SELECT 5, 'Інформатика', '8-Б', 'HTML: структура навчальної сторінки', '<p>Створіть локальний HTML-файл із заголовком, абзацом, списком та посиланням. Використайте лише вигадані дані. Збережіть файл на своєму пристрої.</p>', 0, 2
    UNION ALL SELECT 5, 'Інформатика', '8-Б', 'Алгоритми з повторенням', '<p>Запишіть псевдокод, який обчислює суму чисел від <strong>1 до 20</strong>. Поясніть, як змінити верхню межу циклу.</p>', -2, 1
    UNION ALL SELECT 5, 'Інформатика', '8-Б', 'Діаграми в електронній таблиці', '<p>Створіть таблицю вигаданих температур за сім днів і побудуйте лінійну діаграму. Додайте назву та підписи осей.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul><p><a href="https://drive.google.com/" target="_blank" rel="noopener noreferrer">Google Drive: приклад посилання без навчального файлу</a>.</p>', -5, -1
    UNION ALL SELECT 5, 'Інформатика', '10-А', 'Інформаційна безпека: модель загроз', '<p>Для вигаданого навчального акаунта визначте три загрози й три способи захисту. Поясніть роль двофакторної автентифікації. Не вказуйте власних паролів.</p>', 0, 3
    UNION ALL SELECT 5, 'Інформатика', '10-А', 'Реляційні дані: зв’язки між таблицями', '<p>Спроєктуйте таблиці <strong>«Книги»</strong>, <strong>«Читачі»</strong> та <strong>«Видачі»</strong>. Позначте первинні й зовнішні ключі, наведіть вигадані рядки.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 2
    UNION ALL SELECT 5, 'Інформатика', '10-А', 'Вебджерела та авторське право', '<p>Складіть чотири правила використання матеріалів у навчальному проєкті. Наведіть приклад підпису ілюстрації з автором, джерелом і ліцензією.</p><p><a href="https://drive.google.com/" target="_blank" rel="noopener noreferrer">Google Drive: приклад посилання без навчального файлу</a>.</p>', -5, 0
    UNION ALL SELECT 6, 'Географія', '5-А', 'План місцевості та умовні знаки', '<p>Намалюйте план вигаданого шкільного подвір’я. Позначте <strong>шість об’єктів</strong>, сторони горизонту та легенду. Не використовуйте реальних адрес.</p>', 0, 1
    UNION ALL SELECT 6, 'Географія', '5-А', 'Масштаб: відстань на плані', '<p>Масштаб плану <strong>1 : 1000</strong>. Обчисліть відстань на місцевості, якщо на плані вона становить 3 см, 5 см і 8 см.</p>', -2, 0
    UNION ALL SELECT 6, 'Географія', '5-А', 'Спостереження за погодою', '<p>Підготуйте таблицю погоди на три дні: температура, хмарність, опади, вітер. Використайте й явно позначте <strong>вигадані навчальні дані</strong>.</p>', -5, -2
    UNION ALL SELECT 6, 'Географія', '7-А', 'Африка: географічне положення', '<p>За атласом визначте океани, що омивають Африку, основні лінії градусної сітки та крайні точки материка.</p>', 0, 2
    UNION ALL SELECT 6, 'Географія', '7-А', 'Природні зони материків', '<p>Порівняйте савану та вологий екваторіальний ліс за кліматом, рослинністю й тваринним світом. Оформіть таблицю.</p>', -2, 1
    UNION ALL SELECT 6, 'Географія', '7-А', 'Океани: течії та їхній вплив', '<p>За картою знайдіть по дві теплі й холодні течії. Поясніть на одному прикладі вплив течії на природу узбережжя.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -5, -1
    UNION ALL SELECT 6, 'Географія', '8-Б', 'Рельєф України: основні форми', '<p>За фізичною картою назвіть основні височини, низовини та гірські системи України. Підготуйте схему з <strong>шести прикладів</strong>.</p>', 0, 3
    UNION ALL SELECT 6, 'Географія', '8-Б', 'Клімат України та кліматотвірні чинники', '<p>Поясніть роль географічної широти, повітряних мас і підстильної поверхні. Порівняйте клімат двох умовних пунктів за атласом.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', -2, 2
    UNION ALL SELECT 6, 'Географія', '8-Б', 'Річкові басейни України', '<p>За картою визначте басейни Дніпра, Дністра та Південного Бугу. Складіть таблицю: річка, напрям течії, дві притоки.</p>', -5, 0
    UNION ALL SELECT 6, 'Географія', '10-А', 'Країни Європи: порівняльна характеристика', '<p>Оберіть дві європейські країни та порівняйте положення, населення й спеціалізацію господарства. Використайте підручник і навчальний атлас.</p><ul><li>Перевірте себе та оформіть відповідь охайно.</li><li>Запишіть запитання для обговорення на уроці.</li></ul>', 0, 4
    UNION ALL SELECT 6, 'Географія', '10-А', 'Світове господарство та міжнародні зв’язки', '<p>Побудуйте схему зв’язків трьох секторів господарства на прикладі умовного товару. Додайте <strong>щонайменше шість ланок</strong>.</p>', -2, 3
    UNION ALL SELECT 6, 'Географія', '10-А', 'Сталий розвиток: локальна ініціатива', '<p>Запропонуйте ініціативу для вигаданої громади: проблема, мета, три дії та очікуваний результат. Обсяг: <strong>120–150 слів</strong>.</p>', -5, -4
) AS d
CROSS JOIN (
    SELECT 1 AS school_no, 'hw-demo-01-secretary@example.invalid' AS secretary_email
    UNION ALL SELECT 2, 'hw-demo-02-secretary@example.invalid'
    UNION ALL SELECT 3, 'hw-demo-03-secretary@example.invalid'
    UNION ALL SELECT 4, 'hw-demo-04-secretary@example.invalid'
    UNION ALL SELECT 5, 'hw-demo-05-secretary@example.invalid'
    UNION ALL SELECT 6, 'hw-demo-06-secretary@example.invalid'
    UNION ALL SELECT 7, 'hw-demo-07-secretary@example.invalid'
    UNION ALL SELECT 8, 'hw-demo-08-secretary@example.invalid'
    UNION ALL SELECT 9, 'hw-demo-09-secretary@example.invalid'
    UNION ALL SELECT 10, 'hw-demo-10-secretary@example.invalid'
) AS m
JOIN users secretary ON secretary.email = m.secretary_email AND secretary.role = 'secretary'
JOIN users teacher ON teacher.school_id = secretary.school_id AND teacher.role = 'teacher' AND teacher.email = CONCAT('hw-demo-', LPAD(m.school_no, 2, '0'), '-teacher-', d.teacher_no, '@example.invalid')
JOIN subjects s ON s.school_id = secretary.school_id AND s.name = d.subject_name
JOIN classes c ON c.school_id = secretary.school_id AND c.name = d.class_name
JOIN teaching_assignments a ON a.school_id = secretary.school_id AND a.teacher_id = teacher.id AND a.subject_id = s.id AND a.class_id = c.id
WHERE NOT EXISTS (SELECT 1 FROM homework h WHERE h.assignment_id = a.id AND h.title = d.title);

COMMIT;
