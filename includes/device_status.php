<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('cropsense_device_summary')) {
    function cropsense_device_summary($connection = null)
    {
        global $conn;

        $summary = [
            'active_count' => 0,
            'total_count' => 0,
            'status_text' => 'Device Offline',
            'status_class' => 'offline',
            'meter_width' => 100,
        ];

        $connection = $connection ?: $conn;

        if (!$connection instanceof mysqli || $connection->connect_error) {
            return $summary;
        }

        $table = null;
        foreach (['devices', 'device', 'sensors'] as $candidateTable) {
            $escapedTable = $connection->real_escape_string($candidateTable);
            $tableResult = $connection->query("SHOW TABLES LIKE '{$escapedTable}'");

            if ($tableResult && $tableResult->num_rows > 0) {
                $table = $candidateTable;
                break;
            }
        }

        if (!$table) {
            return $summary;
        }

        $safeTable = '`' . str_replace('`', '``', $table) . '`';
        $totalResult = $connection->query("SELECT COUNT(*) AS total FROM {$safeTable}");

        if ($totalResult) {
            $totalRow = $totalResult->fetch_assoc();
            $summary['total_count'] = (int) ($totalRow['total'] ?? 0);
        }

        $columns = [];
        $columnResult = $connection->query("SHOW COLUMNS FROM {$safeTable}");

        if ($columnResult) {
            while ($column = $columnResult->fetch_assoc()) {
                $columns[] = $column['Field'];
            }
        }

        $activityConditions = [];
        $freshnessConditions = [];

        foreach (['status', 'device_status', 'connection_status'] as $statusColumn) {
            if (in_array($statusColumn, $columns, true)) {
                $safeColumn = '`' . str_replace('`', '``', $statusColumn) . '`';
                $activityConditions[] = "LOWER(CAST({$safeColumn} AS CHAR)) IN ('active', 'online', 'connected', 'on', '1')";
            }
        }

        foreach (['is_active', 'active', 'is_online', 'online', 'connected'] as $booleanColumn) {
            if (in_array($booleanColumn, $columns, true)) {
                $safeColumn = '`' . str_replace('`', '``', $booleanColumn) . '`';
                $activityConditions[] = "{$safeColumn} = 1";
            }
        }

        foreach (['last_seen', 'last_online', 'last_active', 'updated_at'] as $dateColumn) {
            if (in_array($dateColumn, $columns, true)) {
                $safeColumn = '`' . str_replace('`', '``', $dateColumn) . '`';
                $freshnessConditions[] = "{$safeColumn} >= DATE_SUB(NOW(), INTERVAL 60 SECOND)";
            }
        }

        $whereParts = [];

        if ($activityConditions) {
            $whereParts[] = '(' . implode(' OR ', $activityConditions) . ')';
        }

        if ($freshnessConditions) {
            $whereParts[] = '(' . implode(' OR ', $freshnessConditions) . ')';
        }

        if ($whereParts) {
            $activeSql = "SELECT COUNT(*) AS active FROM {$safeTable} WHERE " . implode(' AND ', $whereParts);
            $activeResult = $connection->query($activeSql);

            if ($activeResult) {
                $activeRow = $activeResult->fetch_assoc();
                $summary['active_count'] = (int) ($activeRow['active'] ?? 0);
            }
        }

        if ($summary['active_count'] > 0) {
            $summary['status_text'] = $summary['active_count'] === 1
                ? '1 Device Active'
                : $summary['active_count'] . ' Devices Active';
            $summary['status_class'] = 'active';
            $summary['meter_width'] = $summary['total_count'] > 0
                ? max(8, min(100, round(($summary['active_count'] / $summary['total_count']) * 100)))
                : 100;
        }

        return $summary;
    }
}

$deviceSummary = cropsense_device_summary();
