<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

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
