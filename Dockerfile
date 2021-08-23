FROM composer:2.1

COPY fly50w-docker-wrapper.php /usr/bin/fly50w
COPY docker-entrypoint.sh /docker-entrypoint.sh

RUN composer create-project flylang/fly50w:dev-main /opt/fly50w; \
    chmod +x /usr/bin/fly50w; \
    chmod +x /docker-entrypoint.sh

WORKDIR /app

ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["fly50w"]
