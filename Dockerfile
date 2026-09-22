FROM wordpress:latest

# Устанавливаем расширения PDO и PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql
RUN echo "file_uploads = On\nmemory_limit = 256M\nupload_max_filesize = 64M\npost_max_size = 64M\nmax_execution_time = 600" > /usr/local/etc/php/conf.d/custom.ini