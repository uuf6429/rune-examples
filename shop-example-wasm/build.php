<?php /** @noinspection PhpUnhandledExceptionInspection */

function runCommand(string|array $cmd): void
{
    is_array($cmd) && ($cmd = implode(' ', $cmd));
    echo "‣ Running $cmd ...\n";
    passthru($cmd);
}

$buildDir = __DIR__ . '/.build';
$router = '/app/shop-example/public/index.php';
if (!is_dir($buildDir) && !mkdir($buildDir, 0777, true) && !is_dir($buildDir)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $buildDir));
}

runCommand('docker create --name=php-wasm-builder soyuka/php-wasm:latest');
runCommand('docker cp php-wasm-builder:/build/php-web.mjs ' . escapeshellarg($buildDir));
runCommand('docker cp php-wasm-builder:/build/php-web.wasm ' . escapeshellarg($buildDir));
runCommand('docker rm php-wasm-builder');
runCommand([
    'docker run',
    '--volume ' . escapeshellarg(dirname(__DIR__) . ':/project'),
    'soyuka/php-wasm:latest',
    'python3 /emsdk/upstream/emscripten/tools/file_packager.py',
    '/project/shop-example-wasm/.build/php-web.data',
    '--use-preload-cache --lz4 --preload',
    '/project/shop-example@/app/shop-example',
    '/project/vendor@/app/vendor',
    '--js-output=/project/shop-example-wasm/.build/php-web.data.js',
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

echo "‣ Generating index.html ...\n";
ob_start();
?><!DOCTYPE html>
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
                width: 100%;
                height: 100%;
            }
        </style>
        <script type="module">
            const binary = (await import('./php-web.mjs')).default;
            const router = '<?= $router ?>';
            let outputBuffer = '';
            const php = await binary({
                onAbort(reason) {
                    console.error('WASM aborted: ' + reason);
                },
                print(line) {
                    outputBuffer += line + '\n';
                },
                printErr(...args) {
                    const out = args.join('\n') + '\n';
                    if (out !== '\n') console.error(out);
                }
            });
            const exec = (serverVar = {}, postVar = {}) => {
                serverVar = Object.assign({
                    SERVER_NAME: 'PHP-WASM',
                    SERVER_ADDR: '127.0.0.1',
                    SERVER_PORT: '80',
                    SERVER_PROTOCOL: 'HTTP/1.1',
                    REMOTE_ADDR: '127.0.0.1',
                    REMOTE_PORT: '80',
                    REQUEST_METHOD: 'GET',
                    REQUEST_URI: '/',
                    CONTENT_TYPE: '',
                }, serverVar);
                // FIXME URLSearchParams does not handle files
                postVar = new URLSearchParams(postVar).toString();

                outputBuffer = '';
                php.ccall('phpw_run', 'void', ['string'], [
                    // language=injectablephp
                    `
                    parse_str('${postVar}', $_POST);
                    $_SERVER = array_merge($_SERVER, json_decode('${JSON.stringify(serverVar)}', true));

                    $_SERVER['HTTP_HOST'] = "localhost:{$_SERVER['SERVER_PORT']}";
                    $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
                    parse_str($_SERVER['QUERY_STRING'], $_GET);
                    $_REQUEST = array_merge($_GET, $_POST);

                    require_once('${router}');
                    `
                    // language=
                ]);

                const formHandler = document.getElementById('formHandler').outerHTML;
                document.getElementById('output').srcdoc = outputBuffer + formHandler;
            };
            window.onSubmitForm = (method, url, encoding, payload) => {
                const urlAndHash = url.split('#', 2);

                if (method === 'GET') {
                    urlAndHash[0] += (urlAndHash[0].includes('?') ? '&' : '?') + new URLSearchParams(payload).toString();
                    payload = {};
                }

                if (urlAndHash[1] !== undefined) {
                    /** @var {HTMLIFrameElement} frame */
                    const frame = document.getElementById('output');
                    frame.onload = () => {
                        const elem = frame.contentWindow.document.getElementById(urlAndHash[1]);
                        elem && elem.scrollIntoView();
                    };
                }

                exec({
                    REQUEST_METHOD: method,
                    REQUEST_URI: urlAndHash[0],
                    CONTENT_TYPE: encoding,
                }, payload);
            }

            exec();
        </script>
        <script id="formHandler">
            document.addEventListener('submit', (event) => {
                // TODO The code below should be skipped for external urls

                event.preventDefault();

                /** @var {HTMLFormElement} form */
                const form = event.target;
                window.top.onSubmitForm(
                    form.method.toUpperCase(),
                    form.action,
                    form.enctype,
                    Array.from(new FormData(form))
                );
            });
        </script>
    </head>
    <body>
        <iframe id="output"></iframe>
    </body>
</html><?php
file_put_contents("$buildDir/index.html", ob_get_clean());

echo "‣ Copying static assets ...\n";
copy(__DIR__ . '/../shop-example/public/rune.js', "$buildDir/rune.js");
copy(__DIR__ . '/../shop-example/public/rune.css', "$buildDir/rune.css");
