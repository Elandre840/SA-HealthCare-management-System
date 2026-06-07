<?php
include_once 'helpers.php';
require_login();
include_once 'db.php';
include_once 'clinic_schema.php';

header('Content-Type: application/json; charset=UTF-8');

$province = $_SESSION['province'] ?? '';
$city = $_SESSION['city'] ?? '';
$facility = $_SESSION['facility'] ?? '';
$currentRole = $_SESSION['role'] ?? '';

$sinceId = (int)($_GET['since_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 20);

if (!cs_announcements_supported($conn)) {
    echo json_encode(['ok' => true, 'items' => [], 'latest_id' => 0]);
    exit;
}

$rows = cs_fetch_announcements($conn, $province, $city, $facility, $currentRole, [
    'since_id' => $sinceId,
    'limit' => $limit,
]);

$items = [];
$latestId = 0;
foreach ($rows as $row) {
    $id = (int)($row['announcement_id'] ?? 0);
    if ($id > $latestId) {
        $latestId = $id;
    }
    $items[] = [
        'id' => $id,
        'message' => $row['message'] ?? '',
        'poster_name' => $row['poster_name'] ?? 'Staff',
        'role_target' => $row['role_target'] ?? 'All',
        'created_at' => $row['created_at'] ?? '',
        'priority' => $row['priority'] ?? 'normal',
        'emergency_type' => $row['emergency_type'] ?? '',
        'alert_status' => $row['alert_status'] ?? '',
    ];
}

echo json_encode([
    'ok' => true,
    'items' => $items,
    'latest_id' => $latestId,
    'fetched_at' => date('c'),
]);
