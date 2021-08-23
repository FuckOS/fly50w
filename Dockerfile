FROM composer:latest

RUN mkdir /opt/fly50w/; \
    cd /opt/fly50w; \
    composer require flylang/fly50w:dev-master; \
    ln -s /usr/bin/fly50w /opt/fly50w/fly50w; \
    chmod +x /usr/bin/fly50w

COPY docker-entrypoint.sh /docker-entrypoint.sh

WORKDIR /app

ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["fly50w"]
