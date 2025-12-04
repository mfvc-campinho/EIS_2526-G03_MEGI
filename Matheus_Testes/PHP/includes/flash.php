<?php
// Minimal flash messaging using session.
if (!isset($_SESSION)) session_start();

function flash_set($type, $message)
{
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_render()
{
  if (empty($_SESSION['flash'])) return;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  $type = htmlspecialchars($f['type']);
  $msg = htmlspecialchars($f['message']);
  echo '<div class="flash flash-' . $type . '">' . $msg . '</div>';
}
