<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
  flash_set('error', 'Precisa de iniciar sessÃ£o para gerir itens.');
  header('Location: home_page.php');
  exit;
}

$data = load_app_data($mysqli);
$items = $data['items'] ?? [];
$collections = $data['collections'] ?? [];
$mysqli->close();

$id = $_GET['id'] ?? null;
$editing = false;
$item = ['id' => '', 'name' => '', 'importance' => '', 'weight' => '', 'price' => '', 'acquisitionDate' => '', 'image' => '', 'collectionId' => ''];

if ($id) {
  foreach ($items as $it) {
    if ($it['id'] === $id) {
      $item = $it;
      $editing = true;
      break;
    }
  }
  if (!$editing) {
    flash_set('error', 'Item nÃ£o encontrado.');
    header('Location: home_page.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $editing ? 'Editar' : 'Novo'; ?> Item â€” PHP</title>
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

      <label>Nome</label>
      <input type="text" name="name" required value="<?php echo htmlspecialchars($item['name']); ?>">

      <label>ImportÃ¢ncia</label>
      <input type="text" name="importance" value="<?php echo htmlspecialchars($item['importance']); ?>">

      <label>Peso</label>
      <input type="text" name="weight" value="<?php echo htmlspecialchars($item['weight']); ?>">

      <label>PreÃ§o</label>
      <input type="text" name="price" value="<?php echo htmlspecialchars($item['price']); ?>">

      <label>Data de aquisiÃ§Ã£o</label>
      <input type="date" name="acquisitionDate" value="<?php echo htmlspecialchars($item['acquisitionDate']); ?>">

      <label>Imagem (upload)</label>
      <input type="file" name="imageFile" accept="image/*">
      <?php if (!empty($item['image'])): ?>
        <p class="muted" style="margin-top:4px;">Imagem atual: <?php echo htmlspecialchars($item['image']); ?> (deixe vazio para manter)</p>
      <?php endif; ?>

      <label>ColeÃ§Ã£o</label>
      <select name="collectionId" required>
        <?php foreach ($collections as $col): ?>
          <option value="<?php echo htmlspecialchars($col['id']); ?>" <?php echo ($item['collectionId'] ?? '') === $col['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($col['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="actions">
        <button type="submit" class="explore-btn"><?php echo $editing ? 'Guardar' : 'Criar'; ?></button>
        <a class="explore-btn ghost" href="home_page.php">Cancelar</a>
      </div>
    </form>
  </main>
</body>

</html>



