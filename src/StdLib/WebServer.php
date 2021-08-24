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
        $http = new HttpServer([$this, 'handleRequest']);
        $http->listen($socket);
    }

    public function handleRequest(ServerRequestInterface $req): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'text/plain'], "200 OK\r\n");
    }
}
