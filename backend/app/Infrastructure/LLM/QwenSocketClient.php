<?php

namespace App\Infrastructure\LLM;


class QwenSocketClient
{

    protected string $host = "127.0.0.1";
    protected int $port = 5555;


    public function chat(
        string $prompt
    ): string {


        $socket = socket_create(
            AF_INET,
            SOCK_STREAM,
            SOL_TCP
        );


        if (!$socket) {
            throw new \Exception(
                socket_strerror(socket_last_error())
            );
        }


        socket_set_option(
            $socket,
            SOL_SOCKET,
            SO_RCVTIMEO,
            [
                "sec" => 120,
                "usec" => 0
            ]
        );


        if (
            !socket_connect(
                $socket,
                $this->host,
                $this->port
            )
        ) {

            throw new \Exception(
                socket_strerror(
                    socket_last_error($socket)
                )
            );

        }


        $payload = json_encode(
            [
                "prompt" => $prompt
            ],
            JSON_UNESCAPED_UNICODE
        );


        socket_write(
            $socket,
            $payload,
            strlen($payload)
        );


        $response = '';

        while (true) {

            $chunk = socket_read(
                $socket,
                4096
            );


            if (
                $chunk === false ||
                $chunk === ''
            ) {
                break;
            }


            $response .= $chunk;

        }


        socket_close(
            $socket
        );


        $data = json_decode(
            $response,
            true
        );


        if (
            isset($data['error'])
        ) {

            throw new \Exception(
                $data['error']
            );

        }


        return $data['content'] ?? '';

    }

}
