<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
    flash_set('error', 'You need to log in to manage events.');
    header('Location: event_page.php');
    exit;
}
$currentUserId = $_SESSION['user']['id'] ?? null;

$data = load_app_data($mysqli);
$events = $data['events'] ?? [];
$collections = $data['collections'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];
$mysqli->close();

$appTimezone = new DateTimeZone(date_default_timezone_get());
$now = new DateTime('now', $appTimezone);
$today = $now->format('Y-m-d');

if (!function_exists('parse_event_datetime_helper')) {
    function parse_event_datetime_helper($raw, DateTimeZone $tz)
    {
        $result = ['date' => null, 'hasTime' => false];
        if (!$raw) return $result;
        $trim = trim((string)$raw);
        if ($trim === '') return $result;

        $formats = [
            ['Y-m-d H:i:s', true],
            ['Y-m-d H:i', true],
            ['Y-m-d\TH:i:s', true],
            ['Y-m-d\TH:i', true],
            [DateTime::ATOM, true],
            ['Y-m-d', false]
        ];

        foreach ($formats as [$format, $hasTime]) {
            $dt = DateTime::createFromFormat($format, $trim, $tz);
            if ($dt instanceof DateTime) {
                return ['date' => $dt, 'hasTime' => $hasTime];
            }
        }

        try {
            $dt = new DateTime($trim, $tz);
            $hasTime = (bool)preg_match('/\d{1,2}:\d{2}/', $trim);
            return ['date' => $dt, 'hasTime' => $hasTime];
        } catch (Exception $e) {
            return $result;
        }
    }
}

$id = $_GET['id'] ?? null;
$editing = false;
$event = ['id' => '', 'name' => '', 'summary' => '', 'description' => '', 'type' => '', 'localization' => '', 'date' => '', 'collectionId' => '', 'cost' => ''];
$ownedCollections = array_filter($collections, function ($c) use ($currentUserId) {
    return ($c['ownerId'] ?? null) === $currentUserId;
});
$existingCollections = [];

if ($id) {
    foreach ($events as $ev) {
        if ($ev['id'] === $id) {
            $event = $ev;
            $editing = true;
            break;
        }
    }
    foreach ($collectionEvents as $link) {
        if (($link['eventId'] ?? null) === $id) {
            $existingCollections[] = $link['collectionId'];
        }
    }
    if (!$editing) {
        flash_set('error', 'Event not found.');
        header('Location: event_page.php');
        exit;
    }

    $hostId = $event['hostUserId'] ?? $event['host_user_id'] ?? null;
    if ($hostId !== $currentUserId) {
        flash_set('error', 'Não tem permissões para editar este evento.');
        header('Location: event_page.php');
        exit;
    }

    $parsedDate = parse_event_datetime_helper($event['date'] ?? null, $appTimezone);
    $eventDateObj = $parsedDate['date'];
    $hasTime = $parsedDate['hasTime'];
    if ($eventDateObj) {
        $eventHasEnded = $hasTime
            ? ($eventDateObj <= $now)
            : ($eventDateObj->format('Y-m-d') < $today);
        if ($eventHasEnded) {
            flash_set('error', 'Eventos que já aconteceram não podem ser editados.');
            header('Location: event_page.php');
            exit;
        }
    }
}
$existingCollections = array_unique($existingCollections);

$prefillDateValue = '';
$rawEventDate = $event['date'] ?? '';
if ($rawEventDate !== '') {
    $parsedPrefill = parse_event_datetime_helper($rawEventDate, $appTimezone);
    if ($parsedPrefill['date'] instanceof DateTime) {
        $prefillDateValue = $parsedPrefill['date']->format('Y-m-d\TH:i');
    } else {
        $prefillDateValue = str_replace(' ', 'T', substr($rawEventDate, 0, 16));
    }
}
$prefillCostValue = '';
$rawCost = $event['cost'] ?? '';
if ($rawCost !== '' && $rawCost !== null) {
    $prefillCostValue = number_format((float)$rawCost, 2, '.', '');
}
?>


<?php
$eventTypes = [
    'convention' => 'Convention / Expo',
    'fair' => 'Fair',
    'meetup' => 'Meetup',
    'auction' => 'Auction',
    'signing' => 'Autograph Session',
    'online' => 'Online Event',
    'other' => 'Other',
];

$currentType = $event['type'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $editing ? 'Edit' : 'New'; ?> Event ? GoodCollections</title>
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/forms.css">
        <link rel="stylesheet" href="../../CSS/christmas.css">
        <script src="../../JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
    </head>

    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page">
            <?php flash_render(); ?>
            <header class="page__header">
                <h1><?php echo $editing ? 'Edit Event' : 'Create Event'; ?></h1>
                <a href="event_page.php" class="text-link">Back</a>
            </header>

            <form class="form-card" action="events_action.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
                <?php endif; ?>

                <label>Name <span class="required-badge">R</span></label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($event['name']); ?>">

                <label>Summary</label>
                <input type="text" name="summary" value="<?php echo htmlspecialchars($event['summary']); ?>">

                <label>Description</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>

                <label for="event_type">Type</label>
                <select id="event_type" name="type" class="form-control">
                    <option value="">Choose a type…</option>
                    <?php foreach ($eventTypes as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo ($currentType === $value) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Cost (EUR) <span class="required-badge">R</span></label>
                <input type="number"
                       name="cost"
                       step="0.01"
                       min="0"
                       required
                       placeholder="0.00"
                       value="<?php echo htmlspecialchars($prefillCostValue); ?>">

                <label>Location <span class="required-badge">R</span></label>
                <input type="text" name="localization" required value="<?php echo htmlspecialchars($event['localization']); ?>">

                <label>Date <span class="required-badge">R</span></label>
                  <input type="datetime-local" name="date" required
                      min="<?php echo htmlspecialchars($now->format('Y-m-d\TH:i')); ?>"
                      value="<?php echo htmlspecialchars($prefillDateValue); ?>">

                <label>Collections (can associate to multiple of yours) <span class="required-badge">R</span></label>
                <div style="background:#f8fafc; padding:16px; border-radius:14px; border:1px solid #e5e7eb; box-shadow: inset 0 1px 0 #f1f5f9;">
                    <?php foreach ($ownedCollections as $col): ?>
                        <?php $checked = in_array($col['id'], $existingCollections, true) || (!$editing && ($event['collectionId'] ?? '') === $col['id']); ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#1f2937;">
                            <input type="checkbox" name="collectionIds[]" value="<?php echo htmlspecialchars($col['id']); ?>" <?php echo $checked ? 'checked' : ''; ?> style="width:18px; height:18px;">
                            <span><?php echo htmlspecialchars($col['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="muted" style="margin-top:6px;">Only collections that belong to you are shown.</p>

                <div class="actions">
                    <button type="submit" class="explore-btn"><?php echo $editing ? 'Save' : 'Create'; ?></button>
                    <a class="explore-btn ghost" href="event_page.php">Cancel</a>
                </div>
            </form>
        </main>
    </body>

</html>
