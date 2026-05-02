FROM php:8.2-apache
# G7(라라벨 10 이상) 권장 사양인 PHP 8.2 이상을 추천합니다.

# 공식 composer 이미지에서 실행 파일만 복사해오기 (가장 깔끔함)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 필수 시스템 라이브러리 설치 (GD, Zip, Intl 등)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip bcmath


RUN docker-php-ext-install pdo pdo_mysql mysqli

# 프론트엔드 Node.js 및 npm, Bim 설치 (NodeSource 이용 - 최신 LTS 버전)
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun

ENV PATH="/root/.bun/bin:${PATH}"

# Apache Rewrite 모듈 활성화 (라라벨 라우팅 필수)
RUN a2enmod rewrite


# Apache 기본 설정 변경 (DocumentRoot를 /public으로 설정해야 보안 및 라라벨 구조에 맞음)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html