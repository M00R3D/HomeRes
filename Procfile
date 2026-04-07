web: php artisan config:cache && php artisan migrate --force && (php artisan storage:link || true) && php artisan serve --host=0.0.0.0 --port=${PORT}
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=60
