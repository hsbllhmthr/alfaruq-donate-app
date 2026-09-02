#!/bin/bash
set -e

PORT="${PORT:-80}"

echo "Menyiapkan Apache untuk mendengarkan di port $PORT..."
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# Menjalankan migrasi database otomatis
echo "Menjalankan migrasi database (migrate.php)..."
php /var/www/html/migrate.php || echo "Peringatan: Migrasi DB belum berhasil dijalankan."

echo "Menjalankan Apache web server..."
exec apache2-foreground
