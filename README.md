# Pislia Dzvonyka — After the Bell

**Status: a portfolio project. The author does not plan to operate it as a service for real schools.** The instructions below describe self-hosting the software.

Author: **Viktor Vyhivskyi** — [portfolio](https://vyhivskyi.com/). Developed with assistance from OpenAI Codex; see [AUTHORS.md](AUTHORS.md).

Languages: **PHP, SQL (MySQL), JavaScript, HTML and CSS**. License: **[MIT](LICENSE)**. Use, modification and redistribution are permitted with the copyright and license notice preserved. No warranty or guaranteed support is provided. See [OPEN_SOURCE.md](OPEN_SOURCE.md) for licensing and publication notes.

**Homework always within reach.** The Ukrainian brand name is “Після дзвоника”. The interface and sample assignments are in Ukrainian; developer documentation and code comments are in English.

A mobile-friendly homework board for PHP and MySQL. Students browse without registering: select a city, school, class and subject, or search by text. Assignments appear newest first by publication date. Features include optional deadlines, a Quill editor, external learning-material links, sharing, print/PDF layouts and installation on supported devices.

The board helps teachers and students work together, including students who missed a lesson. The domain homework.org.ua is easy to remember, but a domain name does not establish nonprofit legal status.

## Voluntary school adoption

For anyone deploying their own instance:

1. An authorized representative discusses the proposal with school leadership or another decision maker. The school approves use through its own procedures. An invitation, demonstration visit or public email address does not constitute consent.
2. Only after agreement should the operator create a school and give access to its designated representative. Do not create real school accounts simply because their email addresses are publicly available.
3. Explain staff data processing before entering their information. Set up classes, subjects and teachers, and provide initial training. Never collect or publish students' personal information on the public board.
4. The school centrally informs teachers, students and parents about the rules and the shared address, retaining an agreed backup channel for important updates.

This is an auxiliary homework board, not an official electronic class register. Separate rules apply to official school records: [Ministry of Education explanation](https://mon.gov.ua/news/elektronnyi-zhurnal-u-shkoli-pro-holovne).

## Roles

| Role | Permissions |
| --- | --- |
| Site owner | The single administrator. Creates agreed schools and grants access to authorized representatives. |
| Authorized school representative | Manages teachers, classes, subjects and teaching assignments within their school. |
| Teacher | Creates, edits and deletes their own homework for assigned classes and subjects. Uses a site password with any invited valid email, or Google sign-in with an invited Gmail address. |
| Student | Reads published homework publicly; no account required. |

A second administrator cannot be created through the interface. A database uniqueness constraint also enforces this restriction. Server-side authorization applies to changes and deletions.

The internal role value `secretary` and related field names remain for database compatibility. The interface calls this role the authorized school representative; no SQL renaming or migration is needed.

## Requirements

- PHP **8.2+**, with `pdo_mysql`, `mbstring`, `dom` and `curl`.
- MySQL **5.7+ / 8.x**, or compatible MariaDB, with InnoDB, `utf8mb4` and indexed generated columns.
- Apache or Nginx; HTTPS for an internet deployment.
- Outbound HTTPS and working CA certificates for optional Google sign-in.

Installation requires no Composer, Node.js or frontend build.

## Shared-hosting installation

1. Upload the project with `app/`, `config/`, `database/`, `public/` and root `index.php` beside each other. Prefer **`public/` as DocumentRoot** so private directories remain outside the web root. A project-root DocumentRoot also works through root `index.php`; include the supplied `.htaccess` files to protect private directories.
2. Create a MySQL database and a separate user in the hosting panel, granting access to that database. Select it in phpMyAdmin and import **`database/schema.sql`**. The file creates tables, not the database or MySQL users.
3. Copy **`config/config.example.php`** to **`config/config.php`**. Set database credentials, `base_url` without a trailing slash and a random `setup_token` of **24–200 characters**, without surrounding whitespace. Keep secrets only in this private file or environment variables.
4. Open **`<base_url>/index.php?r=install`**, for example `https://example.com/index.php?r=install`. Submit the setup token in the form and create the administrator. The token is sent by POST, not in the URL.
5. After a school's approval, create its school record and authorized representative. The representative creates classes, subjects, teachers and teaching assignments, trains staff and centrally distributes the board link.
6. Clear `setup_token` after installation, including `APP_SETUP_TOKEN` if set. An existing administrator also blocks reinstallation.

Generate a recommended 64-character token:

```sh
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

For installation in a subdirectory, include it in `base_url`, such as `https://example.com/HW`. Explicit `/HW/public` entry paths are also supported if used consistently. Nginx does not read `.htaccess`: configure its root and private-directory restrictions separately.

### URLs and DocumentRoot

DocumentRoot is a filesystem directory. `base_url` is a web address. Never put `/home/...` filesystem paths in a URL.

| Deployment | DocumentRoot | `base_url` |
| --- | --- | --- |
| Recommended public entry | `/home/USER/example.com/www/public` | `https://example.com` |
| Project-root entry | `/home/USER/example.com/www` | `https://example.com` |
| Intentional `/public/` URL | `/home/USER/example.com/www` | `https://example.com/public` |
| Laragon public entry | `C:/laragon/www/HW/public` | `http://hw.local` |

The public entry opens `www/public/index.php`. The root entry opens `www/index.php`, which loads the application without redirecting the browser. Asset paths are respectively `/assets/` and `/public/assets/`.

Do not overwrite `public/index.php` or `public/.htaccess` with the root files of the same name. Keep `app/`, `config/` and `database/` beside `public/`.

For homework.org.ua, `base_url` is `https://homework.org.ua` with either a `www` or `www/public` document root. The URL should not contain a filesystem path or `public/public`.

Environment variables override file settings:

```text
APP_DB_HOST
APP_DB_PORT
APP_DB_NAME
APP_DB_USER
APP_DB_PASSWORD
APP_BASE_URL
APP_SETUP_TOKEN
GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
```

### FTP updates and missing styles

Upload current `app/`, `public/`, root `index.php` and root `sw.js`, preserving paths. Upload public `config/site.php` when needed, but **do not overwrite the server's private `config/config.php`**. Archives and `output/` are unnecessary. Never place the entire project inside `www/public/`.

Check the server's `base_url` and any overriding `APP_BASE_URL`, then reload with Ctrl+F5. Root `index.php` should load the application without the old `/public/` redirect. If a redirect persists, verify the actual entry file and hosting-panel redirect rules.

| DocumentRoot | CSS URL | File |
| --- | --- | --- |
| `.../www` | `/public/assets/site.css` | `www/public/assets/site.css` |
| `.../www/public` | `/assets/site.css` | `www/public/assets/site.css` |

JavaScript is in the same asset directory. A 404 usually requires checking uploaded paths and DocumentRoot. Keep root and public service workers in their respective locations.

### Installer troubleshooting

The installer checks MySQL connectivity, seven tables and required columns, the setup token and the presence of an administrator without exposing secrets. A configured token alone is insufficient.

| Status | Check |
| --- | --- |
| Access denied | Server MySQL username/password, database assignment and permissions. |
| Database missing | Exact hosting database name, including any provider prefix. |
| Connection unavailable | Hosting `db_host`, `db_port` and MySQL availability. |
| Driver missing | Enable PHP `pdo_mysql`. |
| Missing tables or incompatible schema | Import current `schema.sql` into the configured database. Expected tables: `schools`, `users`, `classes`, `subjects`, `teaching_assignments`, `homework`, `login_attempts`. |
| Schema unreadable | User permissions on the selected database. |
| Administrator exists | Sign in; reinstallation is blocked. |

A local Laragon database is separate from the hosting database. `APP_DB_*` and `APP_SETUP_TOKEN` take priority over the server file. Once prerequisites are ready and no administrator exists, the form appears **on the same installer page** after refreshing; it does not redirect elsewhere. A demo fallback on the home page is not evidence that the database is ready.

## Local development and demo data

In Laragon use `C:/laragon/www/HW/public` as the local domain root, or use `http://localhost/HW/public` with a matching `base_url`.

Alternatively:

```sh
php -S localhost:8080 -t public
```

Set `base_url` to `http://localhost:8080`. PHP's built-in server is for local development.

`index.php?r=demo` renders a built-in example without a database. It is separate from **`database/demo.sql`**, which adds **10 fictional schools, 10 authorized representatives, 60 teachers, 40 classes, 60 subjects, 240 teaching assignments and 720 homework entries**. Staff use reserved `example.invalid` addresses. No administrator, passwords or Google bindings are supplied.

For a fresh test database import the schema, then the demo file. If tables already exist, back up first and import only `demo.sql` into the intended database. FTP upload does not execute SQL. Sequential reimports of an unchanged dataset neither duplicate nor overwrite records. The older single-school demo remains an additional school. See [database/demo.README.md](database/demo.README.md).

## Owner, privacy and terms pages

For an always-current SQL demonstration, run the optional daily CLI job described in [cron/README.md](cron/README.md). It refreshes unchanged HW-DEMO-v2 homework dates while preserving IDs and links; no deletion or reimport is needed.

Footer links open `?r=owner`, `?r=privacy` and `?r=terms`. These pages describe the owner, staff data, public assignment fields, cookies, Google sign-in, external services, content rules and requests concerning data. The Ukrainian pages remain in the language of the interface.

Public information is in **`config/site.php`**; private database and OAuth configuration remains in `config/config.php`. The author's personal email and phone are not published. With no contact email configured, the legal pages display a demonstration notice instead of an empty mail link. Before operating a real instance, supply the actual operator's contact details and review the documents.

Legal pages are readable without signing in or an available MySQL connection. Their existence does not itself establish consent or school agreement. The operator must actually handle requests, review retention needs and clarify hosting logs/backups. Revisit the documents when adding analytics, new data, paid features or providers; obtain appropriate legal review for real-school deployment.

## Sharing, printing and PDF

Select a school and class. Leave all subjects selected for a class collection, or choose one subject. Filter by a day, a Monday–Sunday week or all time, using publication date or deadline. Deadline filtering excludes undated assignments. Apply the search after changing filters.

A compact toolbar provides Copy and Print buttons on the right; narrow screens show accessible icons. Copy immediately copies the URL and confirms success. If both clipboard methods fail, a manual-copy prompt appears. Users paste the link into their chosen chat themselves; nothing is sent automatically.

Class printing opens the complete matching collection, without pagination, grouped by subject. Individual assignments print directly. Use the browser's **Save as PDF** option. A descriptive document title suggests a filename containing class, subject and relevant dates; the browser or PDF printer controls the final name. This is not a direct PDF download.

`public/assets/readability.css` improves screen readability: body text and fields start at 16 px, labels at 14 px and the editor at 17 px. Reset filters is highlighted; assignment links fit their content. Print has separate styling, including clearly marked deadlines.

## Install on a phone

The footer installation button opens the browser's installation prompt where supported, or shows manual instructions, including for iPhone. HTTPS is required, except localhost/loopback development. HTTP `hw.local` may not support installation. The button is hidden in the installed application.

The manifest is at `index.php?r=manifest`, with paths supporting both entry roots and subdirectories. Keep `sw.js` and `public/sw.js` in place. The app uses the network: **homework, profiles and private pages are not cached**. There is no offline board, push notification or background synchronization; offline requests show a generic connection message.

## Google sign-in

1. Create a project in Google Cloud / Google Auth Platform, configure the consent screen and create a Web application OAuth client.
2. Add the exact authorized redirect URI: `<base_url>/index.php?r=google-callback`. For example, `https://homework.org.ua/index.php?r=google-callback`; include a subdirectory or intentional `/public` prefix if present in `base_url`.
3. Store `google_client_id` and `google_client_secret` in private configuration or `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`. Never expose the secret in JavaScript or public files.
4. In Testing mode, add permitted test users. Configure application availability before wider use.
5. The school representative must first invite the teacher's exact Gmail address. Gmail is only for easier Google authentication; no mailbox or Drive access is requested. Teachers with other valid email addresses can use a site password. A non-Gmail teacher must have a new or existing password.

The server uses Authorization Code flow with PKCE S256 and a single-use `state` expiring after ten minutes. It verifies the email, teacher and school status, and binds the immutable Google `sub` on first successful sign-in. Profile data comes directly from Google's HTTPS UserInfo endpoint using a server-obtained token. Tokens are not stored in the database.

Official references: [OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect), [endpoint and parameter reference](https://developers.google.com/identity/openid-connect/reference).

## Editor and learning materials

[Quill 2.0.3](https://quilljs.com/docs/installation) loads from a CDN under BSD-3-Clause. If unavailable, a plain text area remains and accepts permitted HTML. The server sanitizes HTML, rejecting scripts, unsafe URLs and arbitrary attributes.

For attachments, upload a file to a cloud service such as Google Drive, grant the intended students read access and paste its link into the assignment. Check the link and avoid exposing private student data or unrelated documents. This site does not store uploaded files.

## Maintenance

- Back up the database and private configuration; verify restoration.
- Enable HTTPS and disable public PHP error display.
- Use individual staff passwords. School representatives can disable teacher access.
- When changing domains, update `base_url` and the Google redirect URI.
- Structure: `public/` contains public entries/assets, `app/` contains logic/views, `config/` contains settings and `database/` contains the schema and optional examples.
- Local `outreach/`, `output/` and `tests/.runtime/` are not needed for deployment or public source publication. Collected school contacts and the old Git history have been deleted locally.

## Checks

From the project root:

```sh
php tests/security.php
php tests/installation.php
php tests/sharing.php
php tests/pwa.php
php tests/teacher_access.php
php tests/print_names.php
node tests/sharing.mjs
node tests/pwa.mjs
```

Security checks need no MySQL. Installer and other in-memory database checks require `pdo_sqlite`; installer checks also require `pdo_mysql`. Node.js is only needed for JavaScript checks, not deployment.

For MySQL integration checks:

```sh
php tests/integration.php
```

This uses a **separate `homework_test` database**. `APP_TEST_DB_NAME` may override it but must contain only ASCII letters, digits and underscores and end in `_test`. Connection settings come from `APP_DB_HOST`, `APP_DB_PORT`, `APP_DB_USER`, `APP_DB_PASSWORD`, not private `config.php`. Database/table creation privileges are required. Test rows roll back, but the database and schema remain. Use a separate empty test database. These PHP checks are CLI-only.

For end-to-end HTTP checks, start a separate local PHP server with `APP_DB_NAME=homework_web_test`, test MySQL credentials, a local `APP_BASE_URL` and a random `APP_SETUP_TOKEN`. In a second terminal, set the same MySQL credentials and:

```text
APP_TEST_BASE_URL=http://127.0.0.1:8000
APP_TEST_DB_NAME=homework_web_test
APP_TEST_SETUP_TOKEN=<same-token-as-test-server>
```

Then run:

```sh
php tests/http_smoke.php
```

The check only permits loopback web/MySQL hosts and an explicitly named `_test` database. It covers installation, roles, editing, school isolation, CSRF and public browsing. It **leaves example records** for browser inspection. Temporary random owner credentials and cookies stay in ignored `tests/.runtime/`. Never point the test server at a real database.

There is no automatic email sending, student account system or file-upload storage.
