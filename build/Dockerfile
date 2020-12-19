FROM alpine:3.12

RUN apk add --no-cache php7-cli php7-fpm php7-json php7-openssl php7-phar php7-mbstring php7-pdo_sqlite php7-curl php7-simplexml php7-gd nginx supervisor

COPY build/prod/ /
RUN mkdir -p /run/nginx

ENV APP_ENV=production
ENV DB_NAME=mysql:dbname=sebezh_gid;host=127.0.0.1
WORKDIR /app

COPY . /app

CMD ["/usr/bin/supervisord", "-nc", "/etc/supervisord.conf"]
EXPOSE 80/tcp
