# Daily demonstration date refresh

Run `refresh_demo.php` once a day with PHP CLI 8.2+. It reads the usual private configuration and environment overrides. No HTTP endpoint, browser request or external API is required.

The script refreshes dates of unchanged **HW-DEMO-v2** homework from `database/demo.sql`. IDs, links, content, accounts and school settings are preserved. It creates and deletes nothing. The older single-school demo and custom entries are not refreshed. Import the current demo seed once before using this job.

Matching requires the reserved demo teacher and representative emails, school address marker, original title and exact original body. Both demo accounts must still have no password or Google binding. Modified entries are skipped deliberately. Dates are restored to the seed's offsets relative to today in the configured application timezone (normally Europe/Kyiv). Existing deadline edits on otherwise unchanged seed entries will be reset.

## Hosting setup

Upload `cron/` and `database/refresh-demo-dates.sql`, preserving their position beside `app/` and `config/`. If DocumentRoot is the project root, also upload the updated root `.htaccess`. Keep maintenance files outside the public root where possible. The script additionally refuses web requests regardless of web-server rules.

First test with the hosting provider's PHP CLI binary:

```sh
/usr/bin/php /home/USER/example.com/www/cron/refresh_demo.php --dry-run
```

Replace `/usr/bin/php` with the provider's PHP 8.2+ path and the project path with your actual absolute path. Dry-run performs the update inside a transaction and rolls it back, reporting the number of rows that would change. Back up the database before the first actual run.

Then configure a daily cron entry, for example at 03:15 in the cron scheduler's timezone:

```cron
15 3 * * * /usr/bin/php /home/USER/example.com/www/cron/refresh_demo.php
```

For the existing homework.org.ua directory layout, the script path is `/home/plitkote/homework.org.ua/www/cron/refresh_demo.php`. A hosting panel may provide separate schedule and command fields; in that case put only the PHP command in its command field. The scheduler's timezone can differ from the application timezone.

The script reports changed rows, returns exit code 1 on failure and uses a MySQL advisory lock to prevent overlapping refresh jobs. Repeating it on the same day makes no further date changes. It uses a short InnoDB transaction; there is no table truncation, deletion or demo rebuild. A dry-run briefly takes the same row locks as an actual update.

Do not run the seed import concurrently with maintenance. If you change the seed's canonical tasks or offsets, update the matching library in `database/refresh-demo-dates.sql` as well. This refresh keeps dates current; it does not create new educational content or reset a writable public sandbox.

## Local integration check

`php tests/demo_refresh.php` uses loopback MySQL with `APP_DB_HOST`, `APP_DB_PORT`, `APP_DB_USER` and `APP_DB_PASSWORD` (defaults: 127.0.0.1:3306, root, empty password). It creates a randomly named disposable database ending in `_test`, verifies refresh, idempotence, rollback and preservation of custom content/IDs, then drops only that database. It does not read the site's private configuration or use the working database.
