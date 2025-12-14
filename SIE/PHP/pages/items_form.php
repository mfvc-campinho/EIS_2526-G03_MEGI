<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
    flash_set('error', 'You need to log in to manage items.');
    header('Location: home_page.php');
    exit;
}
$currentUserId = $_SESSION['user']['id'] ?? null;

$data = load_app_data($mysqli);
$items = $data['items'] ?? [];
$collections = $data['collections'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$mysqli->close();

$id = $_GET['id'] ?? null;
$preferredCollectionId = $_GET['collectionId'] ?? null;

$editing = false;
$item = [
    'id' => '',
    'name' => '',
    'importance' => '',
    'weight' => '',
    'price' => '',
    'acquisitionDate' => '',
    'image' => '',
    'collectionId' => '',
];

$ownedCollections = array_filter($collections, function ($c) use ($currentUserId) {
    return ($c['ownerId'] ?? null) === $currentUserId;
});

$existingCollections = [];

if ($id) {
    // edit: load item and its collections
    foreach ($items as $it) {
        if ($it['id'] === $id) {
            $item = $it;
            $editing = true;
            break;
        }
    }

    foreach ($collectionItems as $link) {
        if (($link['itemId'] ?? null) === $id) {
            $existingCollections[] = $link['collectionId'];
        }
    }

    if (!$existingCollections && !empty($item['collectionId'])) {
        $existingCollections[] = $item['collectionId'];
    }

    if (!$editing) {
        flash_set('error', 'Item not found.');
        header('Location: home_page.php');
        exit;
    }
}

// if creating and a collectionId came in the query, preselect it
if (!$id && $preferredCollectionId) {
    $existingCollections[] = $preferredCollectionId;
}

$existingCollections = array_unique($existingCollections);
?>



<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $editing ? 'Edit' : 'New'; ?> Item • PHP</title>
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/navbar.css">
        <link rel="stylesheet" href="../../CSS/forms.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../../CSS/christmas.css">
        <script src="../../JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
    </head>

    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page">
            <?php flash_render(); ?>

            <header class="page__header">
                <h1><?php echo $editing ? 'Edit Item' : 'Create Item'; ?></h1>
                <a href="home_page.php" class="text-link">Back</a>
            </header>

            <form class="form-card" action="items_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
                <?php endif; ?>

                <label>Name <span class="required-badge">R</span></label>
                <input type="text" name="name" required
                       value="<?php echo htmlspecialchars($item['name']); ?>">

                <label>Importance</label>
                <select name="importance">
                    <option value="">Select importance...</option>
                    <option value="very_low" <?php echo ($item['importance'] ?? '') === 'very_low' ? 'selected' : ''; ?>>Very Low</option>
                    <option value="low" <?php echo ($item['importance'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo ($item['importance'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo ($item['importance'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="very_high" <?php echo ($item['importance'] ?? '') === 'very_high' ? 'selected' : ''; ?>>Very High</option>
                </select>

                <label>Weight</label>
                <input type="text" name="weight"
                       value="<?php echo htmlspecialchars($item['weight']); ?>">

                  <label>Price</label>
                <input type="text" name="price"
                       value="<?php echo htmlspecialchars($item['price']); ?>">

                  <label>Acquisition date</label>
                <input type="date" name="acquisitionDate"
                       value="<?php echo htmlspecialchars($item['acquisitionDate']); ?>">

                                    <label>Image (upload) <span class="required-badge">R</span></label>
                                <input type="file" name="imageFile" accept="image/*" required>
                <?php if (!empty($item['image'])): ?>
                    <?php
                      $imgPreview = $item['image'];
                      if ($imgPreview && !preg_match('#^https?://#', $imgPreview)) {
                        // stored under /uploads/items relative to /PHP/pages
                        $imgPreview = '../../' . ltrim($imgPreview, './');
                      }
                    ?>
                    <div class="muted" style="margin-top:8px; display:flex; align-items:flex-start; gap:10px;">
                      <div style="flex:0 0 auto; width:120px; height:120px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#f8fafc; display:flex; align-items:center; justify-content:center;">
                        <img src="<?php echo htmlspecialchars($imgPreview); ?>" alt="Current item image" style="max-width:100%; max-height:100%; object-fit:cover;">
                      </div>
                      <p style="margin:0; line-height:1.5;">Current image (leave empty to keep).</p>
                    </div>
                <?php endif; ?>

                <label>
                      Collections (choose all of yours that contain this item)
                    <span class="required-badge">R</span>
                </label>

                <div class="checkbox-grid" style="background: linear-gradient(135deg, #f8fafb 0%, #ffffff 100%); padding: 12px; border-radius: 14px; border: 2px solid #f0f4f8;">
                    <?php foreach ($ownedCollections as $col): ?>
                        <?php $checked = in_array($col['id'], $existingCollections, true) || (!$editing && ($item['collectionId'] ?? '') === $col['id']); ?>
                        <label class="checkbox-pill">
                            <input type="checkbox" name="collectionIds[]" value="<?php echo htmlspecialchars($col['id']); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($col['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="form-help" style="margin-top: 10px;">
                    Only collections that belong to you are shown.
                </p>


                <div class="actions">
                    <button type="submit" class="explore-btn">
                        <?php echo $editing ? 'Save' : 'Create'; ?>
                    </button>
                    <a class="explore-btn ghost" href="home_page.php">Cancel</a>
                </div>
            </form>
        </main>
    </body>

</html>
