<?php
// Static Team page – no DB calls required.
$team = [
  [
    'name' => 'Afonso Dias Fachada Ramos',
    'id' => 'up202108474',
    'photo' => '../../images/team/Afonso.jpeg',
    'blurb' => "If I used this site, I'd collect Minecraft limited edition figures."
  ],
  [
    'name' => 'Ana Isabel Dias Cunha Amorim',
    'id' => 'up202107329',
    'photo' => '../../images/team/Ana.jpg',
    'blurb' => "If I used this site, I'd collect CS:GO Skins. I love them!."
  ],
  [
    'name' => 'Filipa Marisa Duarte Mota',
    'id' => 'up202402072',
    'photo' => '../../images/team/Filipa.jpg',
    'blurb' => "If I used this site, I'd collect photos of José Condessa."
  ],
  [
    'name' => 'Matheus Fernandes Vilhena Campinho',
    'id' => 'up202202004',
    'photo' => '../../images/team/Matheus.jpeg',
    'blurb' => "If I used this site, I'd collect retro games and consoles."
  ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Team • GoodCollections</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/team_page.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../../JS/theme-toggle.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>
  <main class="page">
    <section class="team-hero">
      <div class="icon-stack"><i class="bi bi-people-fill"></i></div>
      <h1>Team Members</h1>
      <p class="muted">The crew behind GoodCollections.</p>
    </section>

    <section class="Team">
      <?php foreach ($team as $member): ?>
        <article class="member">
          <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
          <h2><?php echo htmlspecialchars($member['name']); ?></h2>
          <div class="id"><?php echo htmlspecialchars($member['id']); ?></div>
          <p class="desc"><?php echo htmlspecialchars($member['blurb']); ?></p>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
