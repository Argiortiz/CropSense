# Hostinger public_html Checklist

Upload the CropSense project files into Hostinger `public_html`.

## Required

- `api/`
- `assets/`
- `config/database.php`
- `config/database.local.php`
- `includes/`
- `uploads/`
- `index.php`
- `login.php`
- `dashboard.php`
- `collected_data.php`
- `export_collected_data.php`
- `logout.php`
- `settings.php`
- `user_management.php`
- `.htaccess`

## Optional But Recommended

- `config/security.local.php`

## Do Not Upload Unless Needed

- `.git/`
- `.agents/`
- `.codex/`
- `info.php`
- `test.php`
- `device/`
- `docs/`
- `database/`
- `tools/`

The `.htaccess` file blocks private folders if they are uploaded, but leaving local/debug files out of `public_html` is cleaner.

