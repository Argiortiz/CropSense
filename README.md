# CropSense RS485 soil-sensor integration

The ESP32 reads registers `0x0000` through `0x0006` in one Modbus RTU request and uploads only CRC-checked, range-valid data to `https://cropsense.site/api/save_sensor_reading.php`. The dashboard polls `api/get_latest_reading.php` every five seconds without reloading.

## Hardware and Arduino setup

Install the **ESP32 by Espressif Systems** board package in Arduino IDE. The sketch uses the bundled `WiFi`, `WiFiClientSecure`, and `HTTPClient` libraries; no third-party Modbus library is required.

Wire sensor A/B to the RS485 transceiver A/B, ESP32 GPIO17 to DI, GPIO16 to RO, and tied DE+RE to GPIO4. Connect grounds. Power the sensor at its rated voltage. Use a 3.3 V MAX3485 with ESP32; a 5 V MAX485 RO output must be level-shifted so GPIO16 never receives 5 V.

Open `device/CropSense_ESP32_Dashboard/CropSense_ESP32_Dashboard.ino` and enter Wi-Fi SSID/password, the same API key configured on the server, and the PEM root CA for `cropsense.site`. Keep the HTTPS URL and do not disable certificate validation.

## Database and Hostinger deployment

1. Back up the Hostinger database and `public_html`.
2. Import `database/cropsense_hostinger_schema.sql` only for a new installation. For an existing installation, import `database/20260721_rs485_sensor_indexes.sql`; skip an index if phpMyAdmin reports it already exists.
3. Upload the project contents into `public_html`, preserving folders. The existing `.htaccess` prevents web access to configuration, SQL, documentation, and firmware files.
4. Copy `config/database.local.example.php` to `config/database.local.php` and enter Hostinger database credentials.
5. Copy `config/security.local.example.php` to `config/security.local.php`, replace `CREATE_A_LONG_RANDOM_SENSOR_API_KEY` with a long random key, and put the identical value in firmware `API_KEY`.
6. Confirm `https://cropsense.site/api/get_latest_reading.php` returns JSON, then flash the firmware.

## API request and tests

Expected JSON fields are exactly `device_id`, `moisture`, `temperature`, `ec`, `ph`, `nitrogen`, `phosphorus`, and `potassium`. Zero nutrient values are stored and displayed as zero.

```json
{"device_id":"cropsense-esp32-01","moisture":54.2,"temperature":30.4,"ec":186,"ph":5.5,"nitrogen":0,"phosphorus":46,"potassium":39}
```

Valid request (replace the key):

```bash
curl -i https://cropsense.site/api/save_sensor_reading.php -H "Content-Type: application/json" -H "X-API-Key: YOUR_KEY" --data '{"device_id":"cropsense-esp32-01","moisture":54.2,"temperature":30.4,"ec":186,"ph":5.5,"nitrogen":0,"phosphorus":46,"potassium":39}'
```

Expect HTTP 201 and `{"ok":true,...,"id":NUMBER}`. Repeat with a wrong key (expect 401), omit `ph` (422), send moisture 101 (422), and request `api/get_latest_reading.php?device_id=unknown-device` (200 with `data:null`). Sign in and leave the dashboard open; values should update within five seconds. Stop the ESP32: status becomes Stale after 30 seconds and Offline after 60 seconds. Temporarily block the latest-reading request in browser developer tools to verify the clear refresh-error state.

## ESP32 checks and troubleshooting

Open Serial Monitor at 115200 baud. A successful cycle prints Wi-Fi state, a 19-byte Modbus response, all seven values, HTTP 201, and the server response. Wrong response length usually means A/B reversal, power, baud, address, or wiring trouble. CRC errors suggest noise or termination issues. HTTP 401 means the keys differ; 422 names a bad field; 500 should be investigated in Hostinger PHP/MySQL logs. A certificate error means `ROOT_CA` is missing, expired, or is not the root that validates the domain.

The existing schema requires at least one farm row. Incoming `device_id` is safely mapped to the existing `devices.device_code` and its numeric foreign key, preserving current reports and login behavior. The soil sensor does not provide air humidity, so the legacy required humidity column is stored as `0` and the dashboard labels humidity as not supplied.
