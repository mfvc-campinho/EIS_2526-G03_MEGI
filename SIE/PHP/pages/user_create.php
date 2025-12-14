<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
?>
<!DOCTYPE html>
<html lang="en">






    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Account • GoodCollections</title>
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

                <form class="form-card" action="users_action.php" method="POST" enctype="multipart/form-data" id="user-create-form">
                    <input type="hidden" name="action" value="create">

                    <label>Name <span class="required-badge">R</span></label>
                    <input type="text" name="name" required>

                    <label>Email <span class="required-badge">R</span></label>
                    <input type="email" name="email" required>

                    <label>Photo (upload)</label>
                    <input type="file" name="photoFile" accept="image/*">

                    <label>Date of birth<span class="required-badge">R</span></label>
                    <input type="date" name="dob" id="dob" required>

                    <label>Password <span class="required-badge">R</span></label>
                    <input type="password" name="password" id="password" required>

                    <label>Confirm Password <span class="required-badge">R</span></label>
                    <input type="password" name="password_confirm" id="password_confirm" required>

                    <div class="actions">
                        <button type="submit" class="explore-btn">Create Account</button>
                        <a class="explore-btn ghost" href="home_page.php">Cancel</a>
                    </div>
                </form>
                                <script>
                                    (function(){
                                        var form = document.getElementById('user-create-form');
                                        var dob = document.getElementById('dob');
                                        var pass = document.getElementById('password');
                                        var pass2 = document.getElementById('password_confirm');
                                        if (dob) {
                                            var today = new Date();
                                            var minAge = 14;
                                            var maxAge = 90;
                                            function toISO(d){
                                                var y = d.getFullYear();
                                                var m = String(d.getMonth()+1).padStart(2,'0');
                                                var day = String(d.getDate()).padStart(2,'0');
                                                return y+'-'+m+'-'+day;
                                            }
                                            var maxDate = new Date(today.getFullYear()-minAge, today.getMonth(), today.getDate());
                                            var minDate = new Date(today.getFullYear()-maxAge, today.getMonth(), today.getDate());
                                            dob.setAttribute('max', toISO(maxDate));
                                            dob.setAttribute('min', toISO(minDate));
                                        }
                                        if (form) {
                                            form.addEventListener('submit', function(e){
                                                // Passwords must match
                                                if (pass && pass2 && pass.value !== pass2.value) {
                                                    e.preventDefault();
                                                    alert('Passwords do not match. Please re-enter.');
                                                    pass2.focus();
                                                    return;
                                                }
                                                // Validate age range 14-90
                                                if (dob && dob.value) {
                                                    try {
                                                        var d = new Date(dob.value);
                                                        var today = new Date();
                                                        var age = today.getFullYear() - d.getFullYear();
                                                        var m = today.getMonth() - d.getMonth();
                                                        if (m < 0 || (m === 0 && today.getDate() < d.getDate())) {
                                                            age--;
                                                        }
                                                        if (age < 14 || age > 90) {
                                                            e.preventDefault();
                                                            alert('Age must be between 14 and 90 years.');
                                                            dob.focus();
                                                            return;
                                                        }
                                                    } catch(err) {}
                                                }
                                            });
                                        }
                                    })();
                                </script>
            </div>
        </main>
    </body>

</html>
