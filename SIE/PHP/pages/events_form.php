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

$id = $_GET['id'] ?? null;
$editing = false;
$event = ['id' => '', 'name' => '', 'summary' => '', 'description' => '', 'type' => '', 'localization' => '', 'date' => '', 'collectionId' => ''];
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
}
$existingCollections = array_unique($existingCollections);
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
        <title><?php echo $editing ? 'Edit' : 'New'; ?> Event • PHP</title>
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/forms.css">
        <script src="../../JS/theme-toggle.js"></script>
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

                <label>Location</label>
                <input type="text" name="localization" value="<?php echo htmlspecialchars($event['localization']); ?>">

                <label>Date</label>
                <input type="datetime-local" name="date" value="<?php echo htmlspecialchars(substr($event['date'], 0, 16)); ?>">

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
