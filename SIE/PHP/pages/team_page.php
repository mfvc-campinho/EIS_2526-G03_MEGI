<?php
// Static Team page – no DB calls required.
$team = [
  [
    'name' => 'Afonso Dias Fachada Ramos',
    'id' => 'up202108474',
    'photo' => '../../images/team/Afonso.jpeg',
    'blurb' => "If I used this site, I'd collect Minecraft limited edition figures.",
    'page' => 'https://sigarra.up.pt/feup/pt/fest_geral.cursos_list?pv_num_unico=202108474'
  ],
  [
    'name' => 'Ana Isabel Dias Cunha Amorim',
    'id' => 'up202107329',
    'photo' => '../../images/team/Ana.jpg',
    'blurb' => "If I used this site, I'd collect airplane miniatures. I love them!",
    'page' => 'https://sigarra.up.pt/feup/pt/fest_geral.cursos_list?pv_num_unico=202107329'
  ],
  [
    'name' => 'Filipa Marisa Duarte Mota',
    'id' => 'up202402072',
    'photo' => '../../images/team/Filipa.jpg',
    'blurb' => "If I used this site, I'd collect photos of José Condessa.",
    'page' => 'https://sigarra.up.pt/feup/pt/fest_geral.cursos_list?pv_num_unico=202402072'
  ],
  [
    'name' => 'Matheus Fernandes Vilhena Campinho',
    'id' => 'up202202004',
    'photo' => '../../images/team/Matheus.jpeg',
    'blurb' => "If I used this site, I'd collect retro games and consoles.",
    'page' => 'https://sigarra.up.pt/feup/pt/fest_geral.cursos_list?pv_num_unico=202202004'
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
  <link rel="stylesheet" href="../../CSS/navbar.css">
  <link rel="stylesheet" href="../../CSS/team_page.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../../CSS/christmas.css">
  <script src="../../JS/theme-toggle.js"></script>
  <script src="../../JS/christmas-theme.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>
  <main class="page-shell">

    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
        <li class="breadcrumb-item" aria-current="page">Team</li>
      </ol>
    </nav>

    <!-- ===============================
         Our Team – Hero + Summary
         =============================== -->
    <section class="collections-hero">
      <h1>Our Team</h1>
      <div class="collections-hero-underline"></div>
      <p>Meet the people behind GoodCollections.</p>
    </section>

    <section class="team-summary">
      <article class="team-summary-card">
        <div class="team-summary-icon">
          <i class="bi bi-mortarboard-fill"></i>
        </div>

        <h2 class="team-summary-title">
          GoodCollections — Information System for Collection Management
        </h2>

        <div class="team-summary-grid">
          <div class="team-summary-col">
            <span class="eyebrow">Team</span>
            <div class="team-pill">G03</div>
          </div>

          <div class="team-summary-col">
            <span class="eyebrow">Course</span>
            <p>Masters in Industrial<br>Engineering and Management<br>(MEGI)</p>
          </div>

          <div class="team-summary-col">
            <span class="eyebrow">University</span>
            <p>Faculty of Engineering of<br>University of Porto (FEUP)</p>
          </div>

          <div class="team-summary-col">
            <span class="eyebrow">Academic Year</span>
            <p>1S 2025/2026</p>
          </div>
        </div>
      </article>
    </section>

    <!-- Título da secção de membros (antes dos cards com as fotos) -->
    <section class="team-members-header">
      <h2>
        <i class="bi bi-people-fill"></i>
        <span>Team Members</span>
      </h2>
    </section>

    <section class="team-hero">
      <p class="muted">The crew behind GoodCollections.</p>
    </section>

    <section class="Team">
      <?php foreach ($team as $member): ?>
        <?php $profileUrl = htmlspecialchars($member['page']); ?>
        <article class="member-card card home-card team-card-link"
          role="link"
          tabindex="0"
          data-member-link="<?php echo $profileUrl; ?>">
          <a class="member-photo-link"
            href="<?php echo $profileUrl; ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Visit the profile page for <?php echo htmlspecialchars($member['name']); ?> (opens in a new tab)">
            <div class="card-image member-photo">
              <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
            </div>
          </a>
          <div class="card-info member-info">
            <h3>
              <a class="member-name-link"
                href="<?php echo $profileUrl; ?>"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Visit the profile page for <?php echo htmlspecialchars($member['name']); ?> (opens in a new tab)">
                <?php echo htmlspecialchars($member['name']); ?>
              </a>
            </h3>
            <p class="member-id"><?php echo htmlspecialchars($member['id']); ?></p>
            <p class="member-desc"><?php echo htmlspecialchars($member['blurb']); ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="repo-card-wrapper">
      <a class="repo-card" href="https://github.com/mfvc-campinho/EIS_2526-G03_MEGI" target="_blank" rel="noopener noreferrer">
        <div class="repo-card__icon">
          <i class="bi bi-github"></i>
        </div>
        <div class="repo-card__body">
          <h3>Project Repository</h3>
          <p>View the complete source code and documentation on GitHub</p>
          <span class="repo-card__link">github.com/EIS_2526-G03_MEGI</span>
        </div>
      </a>
    </section>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../../JS/team_page.js"></script>

</body>

</html>
