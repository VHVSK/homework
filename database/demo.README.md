# HW-DEMO-v2 sample data

Import schema.sql followed by demo.sql into the selected database using phpMyAdmin or a MySQL client. The file contains no CREATE DATABASE, USE, stored procedures or DELIMITER commands and is suitable for shared hosting. Import the entire file in one connection. Do not run concurrent imports.

If current tables already exist, back up the database and import only demo.sql. Uploading SQL over FTP does not execute it.

All institutions, addresses and staff are fictional. School names contain the Ukrainian word for “Demonstration”, addresses contain HW-DEMO-01 through HW-DEMO-10, and staff surnames use the “Демо-” prefix. Email addresses use reserved example.invalid. No emails are sent.

| Records | Per school | Total |
| --- | ---: | ---: |
| Schools | 1 | 10 |
| Authorized representatives | 1 | 10 |
| Teachers | 6 | 60 |
| Classes | 4 | 40 |
| Subjects | 6 | 60 |
| Teacher/subject/class assignments | 24 | 240 |
| Homework entries | 72 | 720 |

All 70 users have password_hash = NULL and google_sub = NULL. They populate the interface but have no usable sign-in credentials. No administrator is created and existing accounts are not updated.

The fictional schools are in Kyiv, Lviv, Odesa, Dnipro, Vinnytsia, Poltava, Chernivtsi, Ivano-Frankivsk, Cherkasy and Ternopil, in marker order 01–10. Names and assignments remain in Ukrainian to match the interface.

Each school has classes 5-А, 7-А, 8-Б and 10-А with matching sort order. Subjects are mathematics, Ukrainian, English, history, computer science and geography. Each teacher teaches one subject across all four classes.

Each teaching assignment receives three age-appropriate topics, ranging from fractions and word structure to trigonometry, essays, relational data and the world economy. Rich text includes lists, emphasis, examples and a few explicitly labeled sample Google Drive homepage links with no learning file attached.

Dates are calculated from MySQL CURDATE() when each record is first imported. Publications range from today to seven days ago; deadlines range from six days ago to five days ahead. Reimporting does not move existing dates.

## Reimport behavior

For a continuously current portfolio demo, use the optional [daily date refresh](../cron/README.md) after the initial import. It preserves IDs and content, updates unchanged HW-DEMO-v2 dates and requires no daily deletion or reimport. It intentionally resets deadlines of matching seed entries, while skipping changed titles or bodies.

Only missing records are inserted. There are no UPDATE, DELETE, TRUNCATE, REPLACE, INSERT IGNORE or foreign-key disabling commands. IDs are resolved by queries rather than fixed numeric values.

A school is identified by its representative's reserved email, or by its exact demo address marker if that user is absent. Users are matched by email; classes and subjects by school and name; teaching assignments by teacher, subject and class; homework by assignment and exact title.

Edits to content, dates, names, activity, passwords and Google bindings are preserved while record identifiers remain recognizable. Renaming a homework title, class or subject may cause the original version to be inserted on a later import. Deleted demo records may be recreated. Changing both a representative email and school address marker may duplicate that school. This is a demo seed, not a synchronization tool.

If a reserved email is already used by an unrelated record, that record is left unchanged and dependent demo records may be missing. Inspect the result after such conflicts. Sequential reimports of an unchanged dataset do not add duplicates.

The older single-school demo with representative olena.kovalchuk@example.invalid is neither removed nor reassigned. If only that older dataset existed, importing the new seed produces totals of **11 schools, 71 users, 42 classes, 63 subjects, 243 teaching assignments and 724 homework entries**, excluding any administrator or other records.

## Verification queries

On a clean schema these queries return 10, 70, 40, 60, 240 and 720:

```sql
SELECT COUNT(*) AS schools FROM schools;
SELECT COUNT(*) AS users FROM users;
SELECT COUNT(*) AS classes FROM classes;
SELECT COUNT(*) AS subjects FROM subjects;
SELECT COUNT(*) AS assignments FROM teaching_assignments;
SELECT COUNT(*) AS homework FROM homework;
```

For an existing database, count only schools belonging to the new seed:

```sql
SELECT s.city, s.name,
       (SELECT COUNT(*) FROM users u WHERE u.school_id = s.id AND u.role = 'teacher') AS teachers,
       (SELECT COUNT(*) FROM classes c WHERE c.school_id = s.id) AS classes,
       (SELECT COUNT(*) FROM subjects sub WHERE sub.school_id = s.id) AS subjects,
       (SELECT COUNT(*) FROM teaching_assignments a WHERE a.school_id = s.id) AS assignments,
       (SELECT COUNT(*) FROM homework h JOIN teaching_assignments a ON a.id = h.assignment_id
        WHERE a.school_id = s.id) AS homework
FROM schools s
JOIN users secretary ON secretary.school_id = s.id AND secretary.role = 'secretary'
WHERE secretary.email REGEXP '^hw-demo-(0[1-9]|10)-secretary@example[.]invalid$'
ORDER BY s.city, s.name;
```

Expect ten rows with six teachers, four classes, six subjects, 24 teaching assignments and 72 homework entries each. Counts remain unchanged after reimporting an unchanged seed.
