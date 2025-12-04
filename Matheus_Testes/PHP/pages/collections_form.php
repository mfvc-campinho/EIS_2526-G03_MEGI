<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
  flash_set('error', 'Precisa de iniciar sessÃ£o para gerir coleÃ§Ãµes.');
  header('Location: all_collections.php');
  exit;
}

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
    flash_set('error', 'ColeÃ§Ã£o nÃ£o encontrada.');
    header('Location: all_collections.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $editing ? 'Editar' : 'Nova'; ?> ColeÃ§Ã£o â€” PHP</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/forms.css">
  <script src="../../JS/theme-toggle.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page">
    <?php flash_render(); ?>
    <header class="page__header">
      <h1><?php echo $editing ? 'Editar ColeÃ§Ã£o' : 'Criar ColeÃ§Ã£o'; ?></h1>
      <a href="all_collections.php" class="text-link">Voltar</a>
    </header>

    <form class="form-card" action="collections_action.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection['id']); ?>">
      <?php endif; ?>

      <label>Nome</label>
      <input type="text" name="name" required value="<?php echo htmlspecialchars($collection['name']); ?>">

      <label>Tipo</label>
      <input type="text" name="type" value="<?php echo htmlspecialchars($collection['type']); ?>">

      <label>Resumo</label>
      <input type="text" name="summary" value="<?php echo htmlspecialchars($collection['summary']); ?>">

      <label>DescriÃ§Ã£o</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($collection['description']); ?></textarea>

      <label>Imagem (upload)</label>
      <input type="file" name="coverImageFile" accept="image/*">
      <?php if (!empty($collection['coverImage'])): ?>
        <p class="muted" style="margin-top:4px;">Imagem atual: <?php echo htmlspecialchars($collection['coverImage']); ?> (deixe vazio para manter)</p>
      <?php endif; ?>

      <div class="actions">
        <button type="submit" class="explore-btn"><?php echo $editing ? 'Guardar' : 'Criar'; ?></button>
        <a class="explore-btn ghost" href="all_collections.php">Cancelar</a>
      </div>
    </form>
  </main>
</body>

</html>



