<?php

function runCode(string $code): string
{
    return
        file_get_contents(
            "https://play-f5w-01.flyos.top/api/play/",
            context: stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json",
                    "content" => json_encode(
                        [
                            "version" => 1,
                            "source" => base64_encode($code)
                        ]
                    )
                ]
            ])
        );
}
