#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <time.h>


#include "secrets.h"

// ================= CONFIGURATION (do not commit real secrets) =================
const char *WIFI_SSID = "YOUR_WIFI_NAME";
const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char *API_URL = "https://cropsense.site/api/save_sensor_reading.php";
const char *API_KEY = "YOUR_API_KEY";
const char *DEVICE_ID = "cropsense-esp32-01";
// Paste the PEM root CA that validates cropsense.site. Never use setInsecure().
const char *ROOT_CA = R"PEM(
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+
0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6U
A5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sW
T8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyH
B5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j3x+UC
B5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b0SHzUv
KBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWn
OlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTn
jh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbw
qHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CI
rU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBBjAPBgNV
HRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY9umbbjANBgkq
hkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ
3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KK
NFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5
ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNbOhdjsnvzqvHu7Ur
TkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nxe5AW0wdeRlN8NwdC
jNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1NbdWhscdCb+ZAJzVc
oyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1lvh+wjChP4kqKOJ2qxq
4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0GE44Za4rF2LN9d11TPA
mRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq37Z0j4r7g1SgEEzwxA57d
emyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----
)PEM";
// ============================================================================

constexpr uint8_t RS485_RX = 16;
constexpr uint8_t RS485_TX = 17;
constexpr uint8_t RS485_DE_RE = 4;
constexpr uint32_t SENSOR_INTERVAL_MS = 10000;
constexpr uint32_t WIFI_RETRY_MS = 10000;
constexpr uint32_t MODBUS_TIMEOUT_MS = 1200;
HardwareSerial sensorSerial(2);

struct SoilReading {
  float moisture;
  float temperature;
  uint16_t ec;
  float ph;
  uint16_t nitrogen;
  uint16_t phosphorus;
  uint16_t potassium;
  bool valid;
};

bool cycleRunning = false;
unsigned long lastCycleAt = 0;
unsigned long lastWiFiAttemptAt = 0;
bool clockSynchronized = false;

uint16_t modbusCRC(const uint8_t *data, size_t length) {
  uint16_t crc = 0xFFFF;
  for (size_t i = 0; i < length; ++i) {
    crc ^= data[i];
    for (uint8_t bit = 0; bit < 8; ++bit) {
      crc = (crc & 1) ? (crc >> 1) ^ 0xA001 : crc >> 1;
    }
  }
  return crc;
}

void maintainWiFi() {
  if (WiFi.status() == WL_CONNECTED || millis() - lastWiFiAttemptAt < WIFI_RETRY_MS) return;
  lastWiFiAttemptAt = millis();
  Serial.printf("Wi-Fi reconnecting to %s...\n", WIFI_SSID);
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
}

bool synchronizeClock() {
  if (clockSynchronized) return true;
  if (WiFi.status() != WL_CONNECTED) return false;
  Serial.println("Synchronizing clock for HTTPS certificate validation...");
  configTime(0, 0, "pool.ntp.org", "time.google.com");
  time_t now = time(nullptr);
  const unsigned long started = millis();
  while (now < 1700000000 && millis() - started < 10000) {
    delay(100);
    now = time(nullptr);
  }
  clockSynchronized = now >= 1700000000;
  Serial.println(clockSynchronized ? "Clock synchronized." : "Clock synchronization failed; will retry.");
  return clockSynchronized;
}

bool readSoilSensor(SoilReading &reading) {
  reading.valid = false;
  uint8_t request[8] = {0x01, 0x03, 0x00, 0x00, 0x00, 0x07, 0, 0};
  uint16_t requestCRC = modbusCRC(request, 6);
  request[6] = requestCRC & 0xFF;
  request[7] = requestCRC >> 8;
  while (sensorSerial.available()) sensorSerial.read();

  digitalWrite(RS485_DE_RE, HIGH);
  delayMicroseconds(200);
  sensorSerial.write(request, sizeof(request));
  sensorSerial.flush();
  delayMicroseconds(200);
  digitalWrite(RS485_DE_RE, LOW);

  uint8_t response[19];
  size_t count = 0;
  unsigned long started = millis();
  while (count < sizeof(response) && millis() - started < MODBUS_TIMEOUT_MS) {
    if (sensorSerial.available()) response[count++] = sensorSerial.read();
    else yield();
  }
  Serial.printf("Modbus response length: %u bytes\n", (unsigned) count);
  if (count != sizeof(response)) { Serial.println("Sensor error: wrong response length."); return false; }
  if (response[0] != 0x01 || response[1] != 0x03 || response[2] != 0x0E) {
    Serial.println("Sensor error: address, function, or byte count mismatch."); return false;
  }
  uint16_t receivedCRC = response[17] | ((uint16_t) response[18] << 8);
  if (modbusCRC(response, 17) != receivedCRC) { Serial.println("Sensor error: CRC mismatch."); return false; }

  uint16_t raw[7];
  for (uint8_t i = 0; i < 7; ++i) raw[i] = ((uint16_t) response[3 + i * 2] << 8) | response[4 + i * 2];
  reading.moisture = raw[0] / 10.0f;
  reading.temperature = ((int16_t) raw[1]) / 10.0f;
  reading.ec = raw[2];
  reading.ph = raw[3] / 10.0f;
  reading.nitrogen = raw[4];
  reading.phosphorus = raw[5];
  reading.potassium = raw[6];
  reading.valid = reading.moisture >= 0 && reading.moisture <= 100 &&
                  reading.temperature >= -40 && reading.temperature <= 85 && reading.ph >= 0 && reading.ph <= 14;
  if (!reading.valid) Serial.println("Sensor error: decoded values are outside accepted ranges.");
  return reading.valid;
}

bool uploadReading(const SoilReading &r) {
  if (!r.valid || WiFi.status() != WL_CONNECTED) {
    Serial.println("Upload skipped: reading invalid or Wi-Fi disconnected."); return false;
  }
  if (!synchronizeClock()) {
    Serial.println("Upload skipped: HTTPS clock is not ready."); return false;
  }
  if (String(ROOT_CA).indexOf("PASTE_") >= 0) {
    Serial.println("Upload blocked: configure ROOT_CA to keep HTTPS verification enabled."); return false;
  }
  WiFiClientSecure client;
  client.setCACert(ROOT_CA);
  HTTPClient http;
  http.setTimeout(8000);
  if (!http.begin(client, API_URL)) { Serial.println("HTTPS initialization failed."); return false; }
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);
  String json = "{\"device_id\":\"" + String(DEVICE_ID) + "\",\"moisture\":" + String(r.moisture, 1) +
    ",\"temperature\":" + String(r.temperature, 1) + ",\"ec\":" + String(r.ec) +
    ",\"ph\":" + String(r.ph, 1) + ",\"nitrogen\":" + String(r.nitrogen) +
    ",\"phosphorus\":" + String(r.phosphorus) + ",\"potassium\":" + String(r.potassium) + "}";
  int code = http.POST(json);
  String body = http.getString();
  Serial.printf("HTTP response code: %d\nServer response: %s\n", code, body.c_str());
  http.end();
  return code >= 200 && code < 300;
}

void setup() {
  Serial.begin(115200);
  pinMode(RS485_DE_RE, OUTPUT);
  digitalWrite(RS485_DE_RE, LOW);
  sensorSerial.begin(4800, SERIAL_8N1, RS485_RX, RS485_TX);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  lastWiFiAttemptAt = millis();
  Serial.println("CropSense RS485 soil sensor starting.");
}

void loop() {
  maintainWiFi();
  if (cycleRunning || millis() - lastCycleAt < SENSOR_INTERVAL_MS) { yield(); return; }
  cycleRunning = true;
  lastCycleAt = millis();
  Serial.printf("Wi-Fi status: %s\n", WiFi.status() == WL_CONNECTED ? "Connected" : "Disconnected");
  SoilReading reading{};
  if (readSoilSensor(reading)) {
    Serial.printf("Moisture %.1f%% | Temperature %.1f C | EC %u uS/cm | pH %.1f | N %u | P %u | K %u mg/kg\n",
      reading.moisture, reading.temperature, reading.ec, reading.ph, reading.nitrogen, reading.phosphorus, reading.potassium);
    uploadReading(reading);
  }
  cycleRunning = false;
}
