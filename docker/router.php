<?php

/*
 * Routeur du serveur PHP intégré (développement uniquement).
 * Sans lui, `php -S` répond 404 à toute URL « fichier » absente du disque,
 * comme /assets/app-XXXX.js que AssetMapper génère à la volée.
 */
$file = __DIR__.'/../public'.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ('/' !== $_SERVER['REQUEST_URI'] && is_file($file)) {
    return false; // fichier réel (image, favicon…) : servi directement
}

$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__.'/../public/index.php';
