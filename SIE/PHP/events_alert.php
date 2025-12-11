<?php
session_start();
header('Content-Type: application/json');

// If not authenticated, no alert
if (empty($_SESSION['user']['id'])) {
    echo json_encode(['hasUpcoming' => false]);
    exit;
}

try {
    require_once __DIR__ . '/config/db.php'; // defines $mysqli
    require_once __DIR__ . '/includes/data_loader.php';

    $data = load_app_data($mysqli);
    if ($mysqli instanceof mysqli) {
        $mysqli->close();
    }

    $events = $data['events'] ?? [];
    $eventsUsers = $data['eventsUsers'] ?? [];
    $currentUserId = $_SESSION['user']['id'];

    $today = new DateTimeImmutable('today');
    $limit = $today->modify('+5 days');

    // Build map for quick lookup
    $eventsById = [];
    foreach ($events as $ev) {
        if (!empty($ev['id'])) {
            $eventsById[$ev['id']] = $ev;
        }
    }

    $hasUpcoming = false;
    foreach ($eventsUsers as $link) {
        if (($link['userId'] ?? null) !== $currentUserId) continue;
        if (empty($link['rsvp'])) continue;
        $eid = $link['eventId'] ?? null;
        if (!$eid || !isset($eventsById[$eid])) continue;
        $dateStr = substr($eventsById[$eid]['date'] ?? '', 0, 10);
        if (!$dateStr) continue;
        $evDate = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        if (!$evDate) continue;
        if ($evDate >= $today && $evDate <= $limit) {
            $hasUpcoming = true;
            break;
        }
    }

    echo json_encode(['hasUpcoming' => $hasUpcoming]);
    exit;
} catch (Throwable $e) {
    // Fail quietly
    echo json_encode(['hasUpcoming' => false]);
    exit;
}
