web: php artisan serve --host=0.0.0.0 --port=${PORT}
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=60
release: php artisan migrate --force && php artisan storage:link || true
