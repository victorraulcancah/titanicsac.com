<?php
// Los avisos de PHP NO deben imprimirse en producción: las respuestas AJAX son JSON y
// cualquier warning impreso las invalida (el front muestra "ERR: Error en el servidor"
// aunque la operación se haya guardado bien). En producción se registran en un log.
$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
$esLocal = (strpos($host, '.test') !== false)
    || (strpos($host, 'localhost') !== false)
    || (strpos($host, '127.0.0.1') !== false);

error_reporting(E_ALL);
ini_set('display_errors', $esLocal ? '1' : '0');
ini_set('display_startup_errors', $esLocal ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/files/log/php-errors.log');

require './src/launcher.php';
