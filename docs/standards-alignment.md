# CropSense Standards Alignment

This document maps implemented code controls to ISO/IEC 25010 software quality characteristics and ISO/IEC 27001 information security management controls.

## ISO/IEC 25010

| Quality characteristic | Code-level alignment |
| --- | --- |
| Functional suitability | `api/sensor_readings.php` validates required sensor values before storing readings and returns structured JSON responses for dashboard integration. |
| Reliability | Dashboard pages render the latest database reading at page load and continue polling once per second in `assets/js/dashboard.js`; stale readings are marked offline instead of shown as live. |
| Performance efficiency | The dashboard fetches only the latest sensor record and the API caps sensor payload size with `CROPSENSE_MAX_SENSOR_PAYLOAD_BYTES`. |
| Compatibility | The API accepts JSON and form-style payloads and keeps CORS enabled for device/browser integration. |
| Usability | Dashboard cards label live, stale, and unavailable pH data clearly for operators. |
| Security | `includes/security.php` applies secure session cookie settings, security headers, optional device API key validation, and audit helpers. |
| Maintainability | Shared security behavior is centralized in `includes/security.php` instead of repeated across pages. |
| Portability | Configuration values such as `CROPSENSE_DEVICE_API_KEY` can be supplied through environment variables without changing code. |

## ISO/IEC 27001

| Security objective | Code-level alignment |
| --- | --- |
| Access control | Authenticated dashboard pages use `includes/session.php`; login rejects inactive accounts; optional sensor API key enforcement is available through `CROPSENSE_DEVICE_API_KEY`. |
| Authentication security | Login uses `password_verify()` and regenerates the session ID after successful authentication. |
| Logging and accountability | `includes/auth.php` and `logout.php` write user sign-in, failed login, inactive-account, and sign-out events to `audit_logs` where a user account is known. |
| Secure communications support | HTTP security headers reduce browser attack surface; deployment should use HTTPS for production. |
| Input validation | Sensor API rejects invalid JSON, oversized payloads, missing required readings, and out-of-range values. |
| Least privilege and exposure reduction | Sensor API key is optional for development and can be enabled for deployments without changing endpoint code. |
| Operational monitoring | Dashboard displays sensor live/offline state based on latest reading age. |

## Deployment Notes

- For production, set `CROPSENSE_DEVICE_API_KEY` in the web server environment and send the same value from the ESP32 in the `X-CropSense-Key` header.
- Run the application over HTTPS before setting session cookies as secure-only in production.
- Keep database credentials outside version control for hosted deployments.
- Review `audit_logs` regularly as part of operational monitoring.
