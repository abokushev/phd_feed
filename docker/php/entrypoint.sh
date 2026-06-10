#!/bin/sh
set -e

echo "==> Устанавливаем зависимости Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Выставляем права на runtime/ и web/assets/..."
mkdir -p runtime web/assets web/uploads/documents
chmod -R 777 runtime web/assets web/uploads

echo "==> Ждём MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASSWORD}');" 2>/dev/null; do
  echo "   MySQL ещё не готов, ждём 2 секунды..."
  sleep 2
done
echo "   MySQL готов."

echo "==> Применяем миграции..."
php yii migrate --interactive=0 || echo "ВНИМАНИЕ: миграции завершились с ошибкой, проверьте логи"

echo "==> Запускаем PHP-FPM..."
exec "$@"
