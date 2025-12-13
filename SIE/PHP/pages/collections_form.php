<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
    flash_set('error', 'Precisa de iniciar sessÃ£o para gerir coleÃ§Ãµes.');
    header('Location: all_collections.php');
    exit;
}


$collectionTypes = [
    'Collectible Cards',
    'Coins',
    'Stamps',
    'Board Games',
    'Toys & Figures',
    'Comics',
    'Memorabilia',
    'Other'
];

$data = load_app_data($mysqli);
$collections = $data['collections'] ?? [];
$mysqli->close();

$id = isset($_GET['id']) ? $_GET['id'] : null;
$editing = false;
$collection = ['id' => '', 'name' => '', 'summary' => '', 'description' => '', 'type' => '', 'coverImage' => ''];

if ($id) {
    foreach ($collections as $col) {
        if ($col['id'] === $id) {
            $collection = $col;
            $editing = true;
            break;
        }
    }
    if (!$editing) {
        flash_set('error', 'Collection not found.');
        header('Location: all_collections.php');
        exit;
    }
}

function extract_internal_path($url)
{
    if (!$url) return '';
    $url = trim($url);
    if ($url === '') return '';

    if (preg_match('#^https?://#i', $url)) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $parsed = parse_url($url);
        if (!$parsed) return '';
        $refererHost = $parsed['host'] ?? '';
        if ($host && $refererHost && strcasecmp($host, $refererHost) !== 0) {
            return '';
        }
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        return $path . $query;
    }

    if ($url[0] === '/') {
        return $url;
    }

    if (preg_match('#^[A-Za-z0-9_./?=&-]+$#', $url)) {
        return $url;
    }

    return '';
}

$returnTo = extract_internal_path($_GET['return_to'] ?? '');
if (!$returnTo) {
    $returnTo = extract_internal_path($_SERVER['HTTP_REFERER'] ?? '');
}
if (!$returnTo || stripos($returnTo, 'collections_form.php') !== false) {
    $returnTo = 'all_collections.php';
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $editing ? 'Edit' : 'New'; ?> Collection — PHP</title>
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
                <h1><?php echo $editing ? 'Edit Collection' : 'Create Collection'; ?></h1>
                <a href="all_collections.php" class="text-link">Back</a>
            </header>

            <form class="form-card" action="collections_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection['id']); ?>">
                <?php endif; ?>

                <label>Name <span class="required-badge">R</span></label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($collection['name']); ?>">

                <label>Type <span class="required-badge">R</span></label>
                <select name="type" required>
                    <option value="">Select a type...</option>
                    <?php
                    $currentType = $collection['type'] ?? '';
                    foreach ($collectionTypes as $typeOption):
                        ?>
                        <option value="<?php echo htmlspecialchars($typeOption); ?>"
                                <?php echo ($currentType === $typeOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($typeOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Summary</label>
                <input type="text" name="summary" value="<?php echo htmlspecialchars($collection['summary']); ?>">

                <label>Description</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($collection['description']); ?></textarea>

                <label>Cover image (upload)</label>
                <input type="file" name="coverImageFile" accept="image/*">
                <?php if (!empty($collection['coverImage'])): ?>
                    <p class="muted" style="margin-top:4px;">Current image: <?php echo htmlspecialchars($collection['coverImage']); ?> (leave empty to keep)</p>
                <?php endif; ?>

                <div class="actions">
                    <button type="submit" class="explore-btn"><?php echo $editing ? 'Save' : 'Create'; ?></button>
                    <a class="explore-btn ghost" href="<?php echo htmlspecialchars($returnTo); ?>">Cancel</a>
                </div>
            </form>
        </main>
    </body>

</html>



