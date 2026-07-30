-- Safe additive migration for the existing relational CropSense schema.
-- MySQL 8 / MariaDB: run each CREATE INDEX only if that index is not already present.
CREATE INDEX idx_sensor_readings_created_at ON sensor_readings (created_at);
CREATE INDEX idx_sensor_readings_device_created ON sensor_readings (device_id, created_at);
