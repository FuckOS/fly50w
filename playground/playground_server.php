<?php

use Fly50w\Facade;
use Fly50w\VM\VM;

if (!file_exists('vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';
else require_once 'vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fly50w Playground API</title>
    </head>

    <body>
        <style>
            body {
                background-color: black;
            }

            #main {
                padding: 5px 10%;
                font-size: 15px;
                color: #ddd;
            }

            span {
                background-color: #111;
                padding-top: 0px;
                padding-left: 4px;
                padding-right: 9px;
                padding-bottom: 0px;
                border-radius: 5px;
                font-style: italic;
            }

            pre {
                color: dodgerblue;
            }
        </style>
        <div id="main">
            <br>
            <code>
                <h1>This is <span>Fly50w</span> playground service.</h1>
                <hr>
                <p>Visit <a href="https://github.com/FuckOS/fly50w">GitHub</a> for source code.</p>
                <h2>API Doc</h2>
                <p>The playground has a very simple API.</p>
                <p>API Endpoint: <span>https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?></span></p>
                <h3>API Description</h3>
                <p>Request with POST method.</p>
                <p>Pass post data using <span>application/json</span>.</p>
                <h5>Parameters: </h5>
                <ul>
                    <li><span>version</span>: Please pass integer <span>1</span> at this time.</li>
                    <li><span>source</span>: Please pass base64-encoded source code.</li>
                </ul>
                <p>When an error occured, the server will show respond code other than <span>200</span>.</p>
                <h5>Response: </h5>
                <p>With base64-encoded string: program <span>STDOUT</span>.</p>
                <h3>SDK</h3>
                <p>You can see it for yourself.</p>
                <h5>PHP</h5>
                <pre>function runCode(string $code): string
{
    return
        file_get_contents(
            "https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
            context: stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json",
                    "content" => json_encode(
                        [
                            "version" => 1,
                            "source" => $code
                        ]
                    )
                ]
            ])
        );
}</pre>
                <h5>JS fetch</h5>
                <pre>runCode = async (code) => {
    msg = await fetch('https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>', {  
       method: 'POST',
       credentials: 'include',
       headers: {
          'Content-Type': 'application/json;charset=UTF-8'
       },
       body: JSON.stringify({version: 1, source: code})
    });
    txt = await msg.text();
    return txt;
}</pre>
                <h2>Contact</h2>
                <p>Email to <a href="mailto:xtl@xtlsoft.top">administrator</a>.</p>
                <h2>Copyright</h2>
                <p>漏 FuckOS Organization <?= date('Y') ?></p>
            </code>
        </div>
    </body>

    </html>
<?php
    die();
}

$req = json_decode(file_get_contents('php://input'), 1);

if (
    !$req ||
    !isset($req['version']) ||
    !isset($req['source'])
) {
    http_response_code(400);
    die("400 Bad Request");
}

if ('1' != $req['version']) {
    http_response_code(404);
    die("Version {$req['version']} not supported");
}

$code = $req['source'];

$rslt = '';

$facade = new Facade();
$facade->getVM()->states['print'] = function (array $args, VM $vm) use (&$rslt) {
    foreach ($args as $arg) {
        if (is_string($arg)) {
            $rslt .= $arg;
        } else {
            $rslt .= var_export($arg, true);
        }
    }
};

try {
    $facade->run($code, 'PLAYGROUND_CODE');
} catch (\Exception $e) {
    $rslt .= 'ERROR: ' . $e->getMessage();
} catch (\Error $e) {
    $rslt .= 'ERROR: ' . $e->getMessage();
}

echo $rslt;
