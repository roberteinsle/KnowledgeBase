#!/bin/sh
# OAuth keys for the MCP server (Laravel Passport).
# Preferred: PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY as Coolify environment
# variables. Otherwise the keys are generated once into the storage volume and
# reused on every start (changing them signs out all connected AI apps).
cd /var/www/html || exit 1

if [ -n "$PASSPORT_PRIVATE_KEY" ] && [ -n "$PASSPORT_PUBLIC_KEY" ]; then
    exit 0
fi

if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating Passport keys in storage/ (first start)."
    php artisan passport:keys --no-interaction --force >/dev/null || echo "⚠️  Could not generate Passport keys; MCP sign-in will not work." >&2
fi
