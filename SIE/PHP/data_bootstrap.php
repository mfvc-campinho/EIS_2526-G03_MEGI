<?php
session_start();
header('Content-Type: application/javascript; charset=utf-8');
require_once __DIR__ . '/includes/data_loader.php';

$data = load_app_data($mysqli);
$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Use HEX_* flags to avoid breaking out of the script context.
$encodedData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$encodedUser = json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$timestamp = time();

echo "window.SERVER_APP_DATA = {$encodedData};";
echo "window.SERVER_AUTH_USER = {$encodedUser};";
echo "window.SERVER_APP_DATA_TS = {$timestamp};";

// Provide a minimal appData shim so legacy scripts don't fail when they expect it.
echo "window.appData = window.appData || {};";
echo "window.appData.loadData = function(){ try { if (window.SERVER_APP_DATA) return window.SERVER_APP_DATA; const raw = localStorage.getItem('collectionsData'); return raw ? JSON.parse(raw) : {}; } catch(e){ return {}; } };";
echo "window.appData.saveData = function(data){ try { window.SERVER_APP_DATA = data; localStorage.setItem('collectionsData', JSON.stringify(data)); } catch(e){} };";
// Legacy helpers used by event/item scripts
echo "window.appData.getCollectionOwnerId = function(collectionId, data){ try { const d = data || window.appData.loadData(); const link = (d.collectionsUsers || []).find(function(entry){ return entry.collectionId === collectionId; }); return link ? link.ownerId : null; } catch(e){ return null; } };";
echo "window.appData.getCollectionOwner = function(collectionId, data){ try { const d = data || window.appData.loadData(); const ownerId = window.appData.getCollectionOwnerId(collectionId, d); if (!ownerId) return null; const users = d.users || []; for (var i=0;i<users.length;i++){ var u = users[i]; var uid = String((u.id||u.user_id||u['owner-id']||'')); var uname = String((u['owner-name']||u.user_name||u['user_name']||'')); if (uid === ownerId || uname === ownerId) return u; } return null; } catch(e){ return null; } };";

$mysqli->close();
