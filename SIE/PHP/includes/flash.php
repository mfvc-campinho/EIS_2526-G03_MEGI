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

  $title = $type === 'success' ? 'Success' : 'Heads up';
  if ($type === 'error') {
    $title = 'Oops!';
  }

  echo '<div class="flash-modal flash-modal--' . $type . '" role="alertdialog" aria-live="assertive" aria-modal="true" data-flash-type="' . $type . '" aria-label="' . $title . '">';
  echo '  <div class="flash-modal__backdrop"></div>';
  echo '  <div class="flash-modal__card" tabindex="-1">';
  echo '    <button class="flash-modal__close" type="button" aria-label="Close notification">&times;</button>';
  $iconGlyph = $type === 'success' ? '&#10003;' : '&#9888;';
  echo '    <div class="flash-modal__icon" aria-hidden="true">' . $iconGlyph . '</div>';
  echo '    <div class="flash-modal__content">';
  echo '      <h3>' . htmlspecialchars($title) . '</h3>';
  echo '      <p>' . $msg . '</p>';
  echo '    </div>';
  echo '  </div>';
  echo '</div>';
  echo '<script>(function(){const modal=document.currentScript.previousElementSibling;if(!modal)return;const type=modal.dataset.flashType||"info";const closeBtn=modal.querySelector(".flash-modal__close");const card=modal.querySelector(".flash-modal__card");let timer;function remove(){if(timer){clearTimeout(timer);}modal.classList.add("is-closing");setTimeout(()=>{if(modal&&modal.parentNode){modal.parentNode.removeChild(modal);}document.removeEventListener("keydown",onKey);},220);}function onKey(ev){if(ev.key==="Escape"){remove();}}modal.addEventListener("click",(ev)=>{if(ev.target===modal||ev.target.classList.contains("flash-modal__backdrop")){remove();}});if(closeBtn){closeBtn.addEventListener("click",remove);}document.addEventListener("keydown",onKey);const delay=type==="error"?8000:5000;timer=setTimeout(remove,delay);if(card&&card.focus){requestAnimationFrame(()=>{card.focus();});}})();</script>';
}
