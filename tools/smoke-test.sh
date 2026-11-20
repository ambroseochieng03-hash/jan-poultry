#!/usr/bin/env bash
# JAN Poultry smoke test - no database changes needed.
#   Terminal 1:  php -S 127.0.0.1:8080 -t public tools/dev-router.php
#   Terminal 2:  bash tools/smoke-test.sh            (or: bash tools/smoke-test.sh http://127.0.0.1:8080)
B="${1:-http://127.0.0.1:8080}"
JAR="$(mktemp)"; trap 'rm -f "$JAR"' EXIT
pass=0; fail=0
tok()  { tr '\n' ' ' | grep -oP 'name="csrf_token"\s+value="\K[^"]+' | head -1; }
code() { curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}" "$@"; }
chk()  { if [[ "$3" == *"$2"* ]]; then echo "  PASS  $1"; pass=$((pass+1)); else echo "  FAIL  $1  (wanted '$2', got '$3')"; fail=$((fail+1)); fi; }

echo "## Clean URLs and hidden internals"
chk "/ loads"                       "200"  "$(code $B/)"
chk "/login loads"                  "200"  "$(code $B/login)"
chk "/register loads"               "200"  "$(code $B/register)"
chk "/login.php is a 404"           "404"  "$(code $B/login.php)"
chk "/index.php is a 404"           "404"  "$(code $B/index.php)"
chk "/.env is a 404"                "404"  "$(code $B/.env)"
chk "/schema.sql is a 404"          "404"  "$(code $B/schema.sql)"
chk "/auth/login.php is a 404"      "404"  "$(code $B/auth/login.php)"
chk "unknown page is a 404"         "404"  "$(code $B/nope)"
chk "/register has its stylesheet"  "auth.css" "$(curl -s $B/register)"

echo "## Guest login"
T=$(curl -s -c "$JAR" -b "$JAR" $B/guest-login | tok)
chk "form carries a CSRF token"     "x"    "${T:+x}"
chk "no token -> refused"           "/guest-login" "$(code -c "$JAR" -b "$JAR" -X POST -d 'name=Jane&phone=0712345678' $B/guest-login)"
chk "bad phone -> refused"          "/guest-login" "$(code -c "$JAR" -b "$JAR" -X POST --data-urlencode "csrf_token=$T" -d 'name=Jane+Doe&phone=abc' $B/guest-login)"
chk "script tag as name -> refused" "/guest-login" "$(code -c "$JAR" -b "$JAR" -X POST --data-urlencode "csrf_token=$T" --data-urlencode 'name=<script>x</script>' -d 'phone=0712345678' $B/guest-login)"
chk "foreign Origin -> refused"     "/guest-login" "$(code -c "$JAR" -b "$JAR" -X POST -H 'Origin: http://evil.example' --data-urlencode "csrf_token=$T" -d 'name=Jane+Doe&phone=0712345678' $B/guest-login)"
chk "valid guest -> dashboard"      "/dashboard"   "$(code -c "$JAR" -b "$JAR" -X POST --data-urlencode "csrf_token=$T" -d 'name=Jane+Doe&phone=0712345678' $B/guest-login)"

echo "## Strangers cannot reach admin actions"
J2="$(mktemp)"; T2=$(curl -s -c "$J2" -b "$J2" $B/login | tok)
chk "product-create-ajax -> 401"    "401"  "$(code -c "$J2" -b "$J2" -X POST --data-urlencode "csrf_token=$T2" -d 'name=x' $B/admin/product-create-ajax)"
chk "order-status -> bounced"       "302"  "$(code -X POST -d 'order_id=1&status=accepted' $B/admin/order-status)"
chk "/admin/users -> bounced"       "302"  "$(code $B/admin/users)"
rm -f "$J2"

echo; echo "RESULT: $pass passed, $fail failed"; [ "$fail" -eq 0 ]
