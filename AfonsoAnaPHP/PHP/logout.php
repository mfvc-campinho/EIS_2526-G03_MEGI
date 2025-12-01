<?php
// public_html/PHP/logout.php
session_start();
require_once __DIR__ . '/db.php';

session_unset();
session_destroy();

redirect('../HTML/home_page.php');
