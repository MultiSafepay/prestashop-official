FROM prestashop/prestashop:1.7

ENV DOCKER_ID=1000
RUN usermod -u ${DOCKER_ID} www-data && groupmod -g ${DOCKER_ID} www-data
RUN chown -R www-data:www-data /tmp /var/www/html

# XDebug extension
RUN pecl install xdebug-3.1.0 \
    && docker-php-ext-enable xdebug

RUN { \
        echo 'xdebug.mode=debug'; \
        echo 'xdebug.start_with_request=trigger'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.idekey=PHPSTORM'; \
	} | tee -a "/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

RUN { \
		echo '<FilesMatch \.php$>'; \
		echo '\tSetHandler application/x-httpd-php'; \
		echo '</FilesMatch>'; \
		echo; \
		echo 'DirectoryIndex disabled'; \
		echo 'DirectoryIndex index.php index.html'; \
		echo; \
		echo '<Directory /var/www/html>'; \
		echo '\tOptions FollowSymLinks Indexes'; \
		echo '\tAllowOverride All'; \
		echo '\tSetEnvIf X-Forwarded-Proto https HTTPS=on'; \
		echo '\tSetEnvIf X-Forwarded-Host ^(.+) HTTP_X_FORWARDED_HOST=$1'; \
		echo '\tRequestHeader set Host %{HTTP_X_FORWARDED_HOST}e env=HTTP_X_FORWARDED_HOST'; \
		echo '</Directory>'; \
        } | tee "/etc/apache2/conf-available/docker-php.conf" \
	&& a2enconf docker-php

RUN a2enmod rewrite headers

# Install mhsendmail and enable Mailhog
RUN curl -LkSso /usr/bin/mhsendmail 'https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64'&& \
chmod 0755 /usr/bin/mhsendmail && \
echo 'sendmail_path = "/usr/bin/mhsendmail --smtp-addr=mailhog:1025"' >> /usr/local/etc/php/php.ini;

# Set recommended PHP settings by PrestaShop documentation
RUN sed -i "s/max_execution_time = 30/max_execution_time = 300/g" /usr/local/etc/php/php.ini && \
    sed -i "s/memory_limit = 256M/memory_limit = 512M/g" /usr/local/etc/php/php.ini && \
    sed -i "s/max_input_time = 60/max_input_time = 300/g" /usr/local/etc/php/php.ini;

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
