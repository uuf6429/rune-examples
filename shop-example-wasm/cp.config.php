<?php

use ClassPreloader\ClassLoader;
use uuf6429\RuneExamples\ShopExample\App;

return ClassLoader::getIncludes(static function (ClassLoader $loader) {
    require __DIR__ . '/../vendor/autoload.php';
    $loader->register();

    ob_start();
    App::create()->run();
    ob_end_clean();
});
