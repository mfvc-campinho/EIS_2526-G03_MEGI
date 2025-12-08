<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
?>
<!DOCTYPE html>
<html lang="pt">






    <head>
        <meta charset="UTF-8" />
        <title>Criar conta</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $editing ? 'Editar' : 'Novo'; ?> Evento • PHP</title>
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/forms.css">
        <script src="../../JS/theme-toggle.js"></script>
    </head>


    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page">
            <?php flash_render(); ?>

            <div class="page-shell">
                <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                        <li class="breadcrumb-item" aria-current="page">Criar conta</li>
                    </ol>
                </nav>

                <header class="page__header">
                    <h1>Criar conta</h1>
                    <a href="home_page.php" class="text-link">Voltar</a>
                </header>

                <form class="form-card" action="users_action.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">

                    <label>Nome <span class="required-badge">R</span></label>
                    <input type="text" name="name" required>

                    <label>Email <span class="required-badge">R</span></label>
                    <input type="email" name="email" required>

                    <label>Foto (upload)</label>
                    <input type="file" name="photoFile" accept="image/*">

                    <label>Data de nascimento</label>
                    <input type="date" name="dob">

                    <label>Palavra-passe <span class="required-badge">R</span></label>
                    <input type="password" name="password" required>

                    <div class="actions">
                        <button type="submit" class="explore-btn">Criar conta</button>
                        <a class="explore-btn ghost" href="home_page.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </body>

</html>
