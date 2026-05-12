#!/bin/sh

# Solo corremos las migraciones para asegurar que las tablas existan
# Si fallan, no bloqueamos el arranque del servidor
php artisan migrate --force || echo "Las migraciones ya estaban al día o hubo un aviso."

# Comentamos el seeding para evitar errores de Faker en el arranque
# php artisan db:seed --force
