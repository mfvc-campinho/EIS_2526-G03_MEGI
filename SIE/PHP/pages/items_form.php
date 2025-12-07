<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
    flash_set('error', 'Precisa de iniciar sessão para gerir itens.');
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
    // editar: carregar item e coleções associadas
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
        flash_set('error', 'Item não encontrado.');
        header('Location: home_page.php');
        exit;
    }
}

// se estamos a criar e veio um collectionId na query, pré-seleciona-o
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
        <title><?php echo $editing ? 'Editar' : 'Novo'; ?> Item • PHP</title>
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/forms.css">
        <script src="../../JS/theme-toggle.js"></script>
    </head>

    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page">
            <?php flash_render(); ?>

            <header class="page__header">
                <h1><?php echo $editing ? 'Editar Item' : 'Criar Item'; ?></h1>
                <a href="home_page.php" class="text-link">Voltar</a>
            </header>

            <form class="form-card" action="items_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
                <?php endif; ?>

                <label>Nome <span class="required-badge">R</span></label>
                <input type="text" name="name" required
                       value="<?php echo htmlspecialchars($item['name']); ?>">

                <label>Importância</label>
                <input type="text" name="importance"
                       value="<?php echo htmlspecialchars($item['importance']); ?>">

                <label>Peso</label>
                <input type="text" name="weight"
                       value="<?php echo htmlspecialchars($item['weight']); ?>">

                <label>Preço</label>
                <input type="text" name="price"
                       value="<?php echo htmlspecialchars($item['price']); ?>">

                <label>Data de aquisição</label>
                <input type="date" name="acquisitionDate"
                       value="<?php echo htmlspecialchars($item['acquisitionDate']); ?>">

                <label>Imagem (upload)</label>
                <input type="file" name="imageFile" accept="image/*">
                <?php if (!empty($item['image'])): ?>
                    <p class="muted" style="margin-top:4px;">
                        Imagem atual: <?php echo htmlspecialchars($item['image']); ?> (deixe vazio para manter)
                    </p>
                <?php endif; ?>

                <label>
                    Coleções (escolha todas as suas que contêm este item)
                    <span class="required-badge">R</span>
                </label>

                <div style="background:#f8fafc; padding:16px; border-radius:14px; border:1px solid #e5e7eb; box-shadow: inset 0 1px 0 #f1f5f9;">
                    <?php foreach ($ownedCollections as $col): ?>
                        <?php
                        $checked = in_array($col['id'], $existingCollections, true) || (!$editing && ($item['collectionId'] ?? '') === $col['id']);
                        ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#1f2937;">
                            <input
                                type="checkbox"
                                name="collectionIds[]"
                                value="<?php echo htmlspecialchars($col['id']); ?>"
                                <?php echo $checked ? 'checked' : ''; ?>
                                style="width:18px; height:18px;"
                                >
                            <span><?php echo htmlspecialchars($col['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="muted" style="margin-top:6px;">
                    Apenas aparecem coleções que pertencem ao utilizador.
                </p>


                <div class="actions">
                    <button type="submit" class="explore-btn">
                        <?php echo $editing ? 'Guardar' : 'Criar'; ?>
                    </button>
                    <a class="explore-btn ghost" href="home_page.php">Cancelar</a>
                </div>
            </form>
        </main>
    </body>

</html>
