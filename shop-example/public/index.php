<?php declare(strict_types=1);

foreach (
    [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php'
    ] as $file
) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

use uuf6429\RuneExamples\ShopExample\App;

// This check prevents access to demo on live systems if uploaded by mistake. Shamelessly copied from silex-skeleton.
if (
    !defined('SHOW_EXAMPLE') && (
        isset($_SERVER['HTTP_CLIENT_IP'])
        || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        || !in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1'])
    )
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check <code>' . __FILE__ . '</code> for more information.');
}

defined('SHOW_EXAMPLE') or define('SHOW_EXAMPLE', true);

App::create()->run();
