<?php /** @noinspection PhpUnhandledExceptionInspection */

function runCommand(string|array $cmd): void
{
    is_array($cmd) && ($cmd = implode(' ', $cmd));
    echo "‣ Running $cmd ...\n";
    passthru($cmd);
}

$buildDir = __DIR__ . '/build';
if (!is_dir($buildDir) && !mkdir($buildDir, 0777, true) && !is_dir($buildDir)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $buildDir));
}

runCommand('docker create --name=php-wasm-builder soyuka/php-wasm:latest');
runCommand('docker cp php-wasm-builder:/build/php-web.mjs ./build');
runCommand('docker cp php-wasm-builder:/build/php-web.wasm ./build');
runCommand('docker rm php-wasm-builder');
runCommand([
    'docker run',
    '--volume .:/src',
    'soyuka/php-wasm:latest',
    'python3 /emsdk/upstream/emscripten/tools/file_packager.py',
    '/src/build/php-web.data',
    '--use-preload-cache --lz4 --preload',
    '/src/shop-example@/app/shop-example',
    '/src/vendor@/app/vendor',
    '--js-output=/src/build/php-web.data.js',
    '--no-node',
    '--export-name=createPhpModule',
]);

echo "‣ Append filesystem data ...\n";
file_put_contents(
    "$buildDir/php-web.mjs",
    str_replace(
        '// --pre-jses',
        file_get_contents("$buildDir/php-web.data.js") . "\n\n// --pre-jses",
        file_get_contents("$buildDir/php-web.mjs")
    )
);

echo "‣ Writing index.html ...\n";
file_put_contents(
    "$buildDir/index.html",
    sprintf(
        <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>Rune Shop Example</title>
                <style>
                    html, body {
                        margin: 0;
                        padding: 0;
                    }
                    
                    #output {
                        border: none;
                        margin: 0;
                        padding: 0;
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        width: 100%%;
                        height: 100%%;
                    }
                </style>
                <script type="module">
                    const phpBinary = (await import('./php-web.mjs')).default;
                    const config = {
                        onAbort(reason) {
                            console.error('WASM aborted: ' + reason);
                        },
                        print(...args) {
                            document.getElementById('output').srcdoc += args.join('\n') + '\n';
                        },
                        printErr(...args) {
                            const out = args.join('\n') + '\n';
                            if (out !== '\n') console.error(out);
                        }
                    };
                    phpBinary(config).then(php => {
                        window.php = php;
                        php.ccall('phpw_run', 'void', ['string'], [%s]);
                    });
                </script>
            </head>
            <body>
                <iframe id="output"></iframe>
            </body>
        </html>
        HTML,
        json_encode(
            <<<'PHP'
            // TODO fake the request and stuff
            define('SHOW_EXAMPLE', true);
            require_once '/app/shop-example/public/index.php';
            PHP,
            JSON_THROW_ON_ERROR
        )
    )
);
