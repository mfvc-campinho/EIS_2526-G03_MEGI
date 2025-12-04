<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
  flash_set('error', 'Precisa de iniciar sessÃ£o para gerir eventos.');
  header('Location: event_page.php');
  exit;
}

$data = load_app_data($mysqli);
$events = $data['events'] ?? [];
$collections = $data['collections'] ?? [];
$mysqli->close();

$id = $_GET['id'] ?? null;
$editing = false;
$event = ['id' => '', 'name' => '', 'summary' => '', 'description' => '', 'type' => '', 'localization' => '', 'date' => '', 'collectionId' => ''];

if ($id) {
  foreach ($events as $ev) {
    if ($ev['id'] === $id) {
      $event = $ev;
      $editing = true;
      break;
    }
  }
  if (!$editing) {
    flash_set('error', 'Evento nÃ£o encontrado.');
    header('Location: event_page.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $editing ? 'Editar' : 'Novo'; ?> Evento â€” PHP</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/forms.css">
  <script src="../../JS/theme-toggle.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page">
    <?php flash_render(); ?>
    <header class="page__header">
      <h1><?php echo $editing ? 'Editar Evento' : 'Criar Evento'; ?></h1>
      <a href="event_page.php" class="text-link">Voltar</a>
    </header>

    <form class="form-card" action="events_action.php" method="POST">
      <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
      <?php endif; ?>

      <label>Nome</label>
      <input type="text" name="name" required value="<?php echo htmlspecialchars($event['name']); ?>">

      <label>Resumo</label>
      <input type="text" name="summary" value="<?php echo htmlspecialchars($event['summary']); ?>">

      <label>DescriÃ§Ã£o</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>

      <label>Tipo</label>
      <input type="text" name="type" value="<?php echo htmlspecialchars($event['type']); ?>">

      <label>LocalizaÃ§Ã£o</label>
      <input type="text" name="localization" value="<?php echo htmlspecialchars($event['localization']); ?>">

      <label>Data</label>
      <input type="datetime-local" name="date" value="<?php echo htmlspecialchars(substr($event['date'], 0, 16)); ?>">

      <label>ColeÃ§Ã£o associada (opcional)</label>
      <select name="collectionId">
        <option value="">-- nenhuma --</option>
        <?php foreach ($collections as $col): ?>
          <option value="<?php echo htmlspecialchars($col['id']); ?>" <?php echo ($event['collectionId'] ?? '') === $col['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($col['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="actions">
        <button type="submit" class="explore-btn"><?php echo $editing ? 'Guardar' : 'Criar'; ?></button>
        <a class="explore-btn ghost" href="event_page.php">Cancelar</a>
      </div>
    </form>
  </main>
</body>

</html>



