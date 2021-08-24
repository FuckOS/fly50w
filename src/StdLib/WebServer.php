<?php

namespace Fly50w\StdLib;

use Fly50w\VM\VM;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class WebServer extends LibraryBase
{
    public function init(VM $vm)
    {
    }

    #[FunctionName('http_server')]
    public function httpServer(array $args, VM $vm)
    {
        $addr = $args[0];
        $router = $args[1];
        $socket = new SocketServer($addr);
        $http = new HttpServer(function (
            ServerRequestInterface $req
        ) use ($router, $vm): ResponseInterface {
            $path = $req->getUri()->getPath();
            foreach ($router as $k => $v) {
                if (str_starts_with($path, $k)) {
                    $params = [
                        'server_params' => $req->getServerParams(),
                        'body' => $req->getBody()->__toString(),
                        'headers' => $req->getHeaders(),
                        'uri' => $req->getUri()->__toString(),
                        'query' => $req->getQueryParams()
                    ];
                    return $v([$params], $vm);
                }
            }
            return new Response(
                404,
                ["Content-Type" => "text/plain"],
                "404 Not Found\r\n"
            );
        });
        $http->listen($socket);
    }

    #[FunctionName('http_response')]
    public function httpResponse(array $args, VM $vm)
    {
        $status = isset($args[0]) ? $args[0] : 200;
        $headers = isset($args[1]) ? $args[1] : [];
        $body = isset($args[2]) ? $args[2] : '';
        $version = isset($args[3]) ? $args[3] : '1.1';
        $reason = isset($args[4]) ? $args[4] : null;
        return new Response($status, $headers, $body, $version, $reason);
    }
}
