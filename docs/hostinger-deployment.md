# Hostinger Deployment Guide

Use this guide to move CropSense from local XAMPP to Hostinger.

## 1. Create The Hostinger Database

In Hostinger hPanel, create a MySQL database. Hostinger usually gives names like:

- Database name: `u910796166_cropsense_db`
- Username: `u910796166_cs_admin`
- Host: `localhost`
- Password: the password you created

## 2. Import Tables

Open Hostinger phpMyAdmin for the new database and import these files in order:

1. `database/cropsense_hostinger_schema.sql`
2. `database/cropsense_hostinger_seed_admins.sql`

The seed file creates the approved Administrator accounts. Temporary password:

```text
CropSense@2026
```

Change the password after first login.

## 3. Add Hostinger Database Credentials

Copy:

```text
config/database.local.example.php
```

Rename the copy to:

```text
config/database.local.php
```

Then replace the placeholders with the database details from Hostinger:

```php
return [
    "host" => "localhost",
    "dbname" => "u910796166_cropsense_db",
    "username" => "u910796166_cs_admin",
    "password" => "YOUR_HOSTINGER_DATABASE_PASSWORD",
];
```

Do not commit or publicly upload the real password. The project ignores:

```text
config/database.local.php
config/security.local.php
.env
```

## 4. Optional Sensor API Key

For production, copy:

```text
config/security.local.example.php
```

Rename the copy to:

```text
config/security.local.php
```

Set a long random value for `device_api_key`, then send the same key from the ESP32 using the `X-CropSense-Key` header.

## 5. Upload Site Files

Upload the CropSense files into Hostinger `public_html`.

Do not upload local-only folders if you do not need them online:

- `.git`
- `.agents`
- `.codex`

The `.htaccess` file blocks browser access to private folders such as `config`, `database`, `docs`, `device`, and `tools`.

## 6. Test Database Connection

If Hostinger terminal access is available, run:

```text
php tools/check_database.php
```

The test checks the connection plus required CropSense tables and columns.

## 7. Update ESP32 API URL

After your domain is live, update the Arduino code:

```cpp
const char* SERVER_URL = "https://your-domain.com/api/sensor_readings.php";
```

Then upload the updated sketch to the ESP32.

## 8. Login

Open:

```text
https://your-domain.com
```

The site will redirect to the login page.

Any valid, unique email address can be assigned to either the Administrator or MAO Staff role. Access permissions come from the role stored in the `users` table.
