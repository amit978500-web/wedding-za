#!/usr/bin/env sh
cd "$(dirname "$0")"
echo "Wedding Za running at http://localhost:8088"
php -S localhost:8088
