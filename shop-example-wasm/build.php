<?php

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', true);
ini_set('error_reporting', -1);

$buildDir = __DIR__ . '/../build';

chdir(__DIR__ . '/../');

if (!is_dir($buildDir) && !mkdir($buildDir, 0777, true) && !is_dir($buildDir)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $buildDir));
}

passthru(implode(' ', [
    'docker run --rm',
    '-v .:/app',
    'emscripten/emsdk',
    'python3 /emsdk/upstream/emscripten/tools/file_packager.py',
    '/app/build/php-web.data',
    '--use-preload-cache --lz4 --preload',
    '/app/shop-example/public@public',
    '/app/shop-example/src@src',
    '/app/vendor@vendor',
    '--js-output=/app/build/php-web.data.js --no-node'
]));

copy(__DIR__.'/main.tpl.html', "$buildDir/index.html");



die;


passthru('composer exec classpreloader -- compile --strict_types=1 --config=shop-example-wasm/cp.config.php --output=build/classes.php');

$prefix = '<?php declare(strict_types=1);';
$classes = str_replace($prefix, '', file_get_contents("$buildDir/classes.php"));
$classes = preg_split('/}\s*\nnamespace /', $classes);
$firstIndex = 0;
$lastIndex = count($classes) - 1;
$classes = array_map(
    static fn(int $index, string $code): string => match (true) {
        $index === $firstIndex => "$code}\n",
        $index === $lastIndex => "namespace $code",
        default => "namespace $code}\n",
    },
    array_keys($classes),
    $classes
);

$classMap = array_combine(
    array_map(static function (string $code): string {
        if (!preg_match('/(^|})\s*namespace ([\w\\\\]+)\s+[{;]/', $code, $namespaceMatch)) {
            throw new RuntimeException("Could not detect namespace in:\n$code");
        }
        if (!preg_match('/\n(?:abstract class|final class|class|interface|trait) (\w+)\s+(?:extends|implements|\{)/', $code, $classMatch)) {
            throw new RuntimeException("Could not detect class/trait/interface in:\n$code");
        }
        return ltrim(trim($namespaceMatch[1], '\\') . '\\' . trim($classMatch[1]), '\\');
    }, $classes),
    $classes
);

file_put_contents("$buildDir/index.php", sprintf(
    <<<'PHP'
    %s
    
    spl_autoload_register(static function (string $classFqn): void {
        static $classMap = %s;
        isset($classMap[$classFqn]) && eval($classMap[$classFqn]);
    });

    //uuf6429\RuneExamples\ShopExample\App::create()->run();
    phpinfo();
    
    PHP,
    $prefix,
    var_export(base64_encode(var_export($classMap, true)), true),
));

$phpCode = str_replace($prefix, '', file_get_contents("$buildDir/index.php"));

$htmlFile = str_replace('%PHP_CODE%', "<?php $phpCode ?>", file_get_contents(__DIR__ . '/main.html'));

file_put_contents("$buildDir/index.html", $htmlFile);
