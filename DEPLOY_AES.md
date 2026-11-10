# CropSense AES-256-GCM Deployment Guide

## Security stack

The upgraded sensor path is:

7-in-1 sensor -> ESP32 -> AES-256-GCM -> HTTPS/TLS -> PHP API -> validation -> prepared SQL -> MySQL

The existing `X-API-Key` is retained for device authentication.

AES-256-GCM adds:
- sensor-payload confidentiality
- payload integrity/authentication
- tamper detection

Timestamp validation plus `devices.last_message_ts_ms` adds replay/out-of-order rejection.

## 1. Back up production

Before changing anything:
- download/back up `public_html`
- export the CropSense MySQL database

## 2. Generate one AES-256 key

Windows/XAMPP:

```bat
C:\xampp\php\php.exe -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Or:

```bash
openssl rand -base64 32
```

Keep the output private.

## 3. Run the database migration

In Hostinger phpMyAdmin, select the CropSense database and run:

`database/20261027_aes_sensor_payload.sql`

This adds `devices.last_message_ts_ms`.

## 4. Add the AES settings to the EXISTING Hostinger config

Edit:

`public_html/config/security.local.php`

Preserve all of your existing settings, including reCAPTCHA/security entries.

Add these entries inside the returned array:

```php
"sensor_aes_key_base64" => "YOUR_GENERATED_BASE64_AES_KEY",
"sensor_require_encryption" => false,
```

Keep your existing:

```php
"device_api_key" => "YOUR_EXISTING_DEVICE_API_KEY",
```

Use `false` only during the rollout so the old ESP32 will not be immediately locked out.

## 5. Upload the server files

Replace:

- `public_html/includes/security.php`
- `public_html/api/save_sensor_reading.php`

Do NOT replace the real `config/security.local.php` with the example file.

## 6. Prepare the ESP32 private secrets

Copy:

`device/CropSense_ESP32_Dashboard/secrets.example.h`

to:

`device/CropSense_ESP32_Dashboard/secrets.h`

Fill in:
- Wi-Fi SSID
- Wi-Fi password
- existing device API key
- the exact same Base64 AES key used on Hostinger

## 7. Flash the AES-enabled firmware

Flash:

`device/CropSense_ESP32_Dashboard/CropSense_ESP32_Dashboard.ino`

Open Serial Monitor at 115200 baud.

Successful upload should show:

```text
Clock synchronized.
AES-256-GCM encrypted upload -> HTTP 201
Server response: {"ok":true,...,"encrypted":true}
```

## 8. Enforce encryption

After encrypted ESP32 uploads are confirmed, edit Hostinger:

```php
"sensor_require_encryption" => true,
```

From this point, plaintext sensor JSON is rejected.

Expected plaintext rejection:

```json
{"ok":false,"error":"Encrypted sensor payload is required."}
```

## 9. PASS checks

Confirm all of these:

- AES ESP32 request returns HTTP 201
- server response contains `"encrypted":true`
- dashboard continues receiving current sensor readings
- wrong API key returns HTTP 401
- plaintext payload returns HTTP 400 after encryption is enforced
- replaying the same encrypted envelope returns HTTP 409
- modifying `ciphertext`, `tag`, `iv`, `device_id`, `timestamp_ms`, or `message_id` causes rejection
- Wireshark still sees HTTPS/TLS rather than readable sensor JSON

## Encrypted envelope

The ESP32 sends an outer JSON envelope similar to:

```json
{
  "version": 1,
  "device_id": "cropsense-esp32-01",
  "timestamp_ms": 1790000000000,
  "message_id": "32-character-hex-id",
  "iv": "BASE64_12_BYTE_IV",
  "ciphertext": "BASE64_AES_GCM_CIPHERTEXT",
  "tag": "BASE64_16_BYTE_TAG"
}
```

The actual moisture, temperature, EC, pH, N, P, and K values are inside `ciphertext`.

Authenticated additional data (AAD):

```text
v1|device_id|timestamp_ms|message_id
```

## Why both AES and HTTPS?

HTTPS/TLS protects the connection.

AES-256-GCM additionally encrypts and authenticates the IoT application payload before PHP accepts the sensor values.

The device API key remains a separate authentication control.

## Rollback

If encrypted uploads fail:

1. set `sensor_require_encryption` back to `false`
2. restore the previous `api/save_sensor_reading.php`
3. restore the previous `includes/security.php`
4. reflash the previous ESP32 firmware if needed

The added `last_message_ts_ms` column can remain in the database without affecting older code.
