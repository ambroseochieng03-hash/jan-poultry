#!/usr/bin/env bash
# Interactive .env writer. Secrets are typed silently: never echoed, never in
# shell history, never pasted into chat.   Usage: bash tools/make-env.sh [path]
# If DBPASS is exported in your shell it is used as DB_PASSWORD (not asked).
set -euo pipefail
OUT="${1:-.env}"
if [ -e "$OUT" ]; then
  read -r -p "$OUT already exists. Overwrite? [y/N] " a
  [[ "$a" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }
fi
umask 077
ask()  { local v; read -r -p "$1${2:+ [$2]}: " v; printf '%s' "${v:-${2:-}}"; }
asks() { local v; read -r -s -p "$1: " v; echo >&2; printf '%s' "$v"; }
need() { [ -n "$2" ] || { echo "$1 cannot be empty." >&2; exit 1; }; }

APP_ENV=$(ask APP_ENV production)
APP_URL=$(ask APP_URL https://janpoultry.luxempire.co.ke)
SUPER_ADMIN_EMAIL=$(ask SUPER_ADMIN_EMAIL)
DB_HOST=$(ask DB_HOST 127.0.0.1)
DB_NAME=$(ask DB_NAME janpoultry)
DB_USER=$(ask DB_USER janpoultry)
DB_PASSWORD="${DBPASS:-$(asks DB_PASSWORD)}"
GOOGLE_MAPS_API_KEY=$(asks GOOGLE_MAPS_API_KEY)
GOOGLE_CLIENT_ID=$(ask GOOGLE_CLIENT_ID "")
MAIL_HOST=$(ask MAIL_HOST)
MAIL_PORT=$(ask MAIL_PORT 587)
MAIL_USERNAME=$(ask MAIL_USERNAME)
MAIL_PASSWORD=$(asks MAIL_PASSWORD)
MAIL_ENCRYPTION=$(ask MAIL_ENCRYPTION tls)
MAIL_FROM_EMAIL=$(ask MAIL_FROM_EMAIL)
MAIL_FROM_NAME=$(ask MAIL_FROM_NAME "JAN Poultry")
R2_ENDPOINT=$(ask R2_ENDPOINT)
R2_BUCKET=$(ask R2_BUCKET janpoultry-media)
R2_ACCESS_KEY_ID=$(asks R2_ACCESS_KEY_ID)
R2_SECRET_ACCESS_KEY=$(asks R2_SECRET_ACCESS_KEY)
MEDIA_BASE_URL=$(ask MEDIA_BASE_URL https://jpmedia.luxempire.co.ke)
MEDIA_DRIVER=$(ask MEDIA_DRIVER r2)

need APP_URL "$APP_URL"; need DB_NAME "$DB_NAME"; need DB_USER "$DB_USER"; need DB_PASSWORD "$DB_PASSWORD"
case "$APP_ENV" in development|production) ;; *) echo "APP_ENV must be development or production." >&2; exit 1;; esac
if [ "$APP_ENV" = production ] && [[ "$APP_URL" != https://* ]]; then echo "APP_URL must start with https:// in production." >&2; exit 1; fi

{
  echo "APP_ENV=$APP_ENV";           echo "APP_URL=${APP_URL%/}";   echo "SUPER_ADMIN_EMAIL=$SUPER_ADMIN_EMAIL"
  echo "DB_HOST=$DB_HOST";           echo "DB_NAME=$DB_NAME";       echo "DB_USER=$DB_USER";  echo "DB_PASSWORD=$DB_PASSWORD"
  echo "GOOGLE_MAPS_API_KEY=$GOOGLE_MAPS_API_KEY"
  echo "GOOGLE_CLIENT_ID=$GOOGLE_CLIENT_ID"
  echo "MAIL_HOST=$MAIL_HOST";       echo "MAIL_PORT=$MAIL_PORT";   echo "MAIL_USERNAME=$MAIL_USERNAME"; echo "MAIL_PASSWORD=$MAIL_PASSWORD"
  echo "MAIL_ENCRYPTION=$MAIL_ENCRYPTION"; echo "MAIL_FROM_EMAIL=$MAIL_FROM_EMAIL"; echo "MAIL_FROM_NAME=$MAIL_FROM_NAME"
  echo "REDIS_HOST=127.0.0.1";       echo "REDIS_PORT=6379";         echo "REDIS_DB=3"
  echo "NATS_URL=nats://127.0.0.1:4222"
  echo "R2_ENDPOINT=$R2_ENDPOINT";   echo "R2_BUCKET=$R2_BUCKET"
  echo "R2_ACCESS_KEY_ID=$R2_ACCESS_KEY_ID"; echo "R2_SECRET_ACCESS_KEY=$R2_SECRET_ACCESS_KEY"
  echo "MEDIA_BASE_URL=${MEDIA_BASE_URL%/}"
  echo "MEDIA_DRIVER=$MEDIA_DRIVER"; echo "MEDIA_INCOMING_DIR=/var/lib/janpoultry/incoming"
} > "$OUT"
chmod 600 "$OUT"
echo "Wrote $OUT (mode 600)."
