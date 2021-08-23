FROM composer:2.1

COPY . /app/
RUN cd /app/ && composer install

ENV FLYLANG_PLAYGROUND_PORT=28111

WORKDIR /app

ENTRYPOINT php -S 0.0.0.0:$FLYLANG_PLAYGROUND_PORT playground_server.php

