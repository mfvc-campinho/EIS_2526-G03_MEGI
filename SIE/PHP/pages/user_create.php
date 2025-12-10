<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
?>
<!DOCTYPE html>
<html lang="en">






    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Account • PHP</title>
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
                        <li class="breadcrumb-item" aria-current="page">Create Account</li>
                    </ol>
                </nav>

                <header class="page__header">
                    <h1>Create Account</h1>
                    <a href="home_page.php" class="text-link">Back</a>
                </header>

                <form class="form-card" action="users_action.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">

                    <label>Name <span class="required-badge">R</span></label>
                    <input type="text" name="name" required>

                    <label>Email <span class="required-badge">R</span></label>
                    <input type="email" name="email" required>

                    <label>Photo (upload)</label>
                    <input type="file" name="photoFile" accept="image/*">

                    <label>Date of birth</label>
                    <input type="date" name="dob">

                    <label>Password <span class="required-badge">R</span></label>
                    <input type="password" name="password" required>

                    <div class="actions">
                        <button type="submit" class="explore-btn">Create Account</button>
                        <a class="explore-btn ghost" href="home_page.php">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </body>

</html>
