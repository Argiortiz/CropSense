# CropSense Database Query Review

Date: 2026-07-15

## Connection

- Main connection file: `config/database.php`
- Production credential file: `config/database.local.php`
- Git protection: `config/database.local.php`, `config/security.local.php`, and `.env` are ignored.
- Hostinger database name: `u910796166_cropsense_db`
- Hostinger database user: `u910796166_cs_admin`
- Database password is not committed.

## Query Review

- `includes/auth.php`: uses prepared statements for login lookup and `password_verify`.
- `user_management.php`: uses prepared statements for user create, email reset, password reset, and delete.
- `api/sensor_readings.php`: uses prepared statements for device lookup/update/create and sensor inserts. Fixed table helper only accepts internal table names.
- `dashboard.php`: uses a fixed read-only latest-reading query.
- `collected_data.php` and `export_collected_data.php`: use fixed SQL conditions selected from a server-side whitelist.
- `includes/device_status.php`: now uses the shared database connection instead of hardcoded XAMPP credentials.
- `includes/security.php`: uses prepared statements for audit logs.
- `test.php` and `info.php`: blocked by `.htaccess` and should not be uploaded if not needed.

## Deployment Checks

- Run `php tools/check_database.php` after setting Hostinger credentials.
- Import `database/cropsense_hostinger_schema.sql` before the admin seed file.
- Keep `config/database.local.php` out of Git and browser access.
- Use `config/security.local.php` or `CROPSENSE_DEVICE_API_KEY` to protect the public sensor API.

