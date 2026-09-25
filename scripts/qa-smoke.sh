#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8088}"

routes=(
  "/"
  "/vendors.php"
  "/event.php?type=Wedding"
  "/city.php?city=Jaipur"
  "/blog.php"
  "/article.php?id=jaipur-venues"
  "/planner.php"
  "/invites.php"
  "/register.php?role=host"
  "/admin/login.php"
  "/sitemap.php"
)

for route in "${routes[@]}"; do
  echo "Checking ${route}"

  curl     --fail     --silent     --show-error     --location     "${BASE_URL}${route}"     > /tmp/wz-response.txt

  if [[ ! -s /tmp/wz-response.txt ]]; then
    echo "Empty response for ${route}"
    exit 1
  fi
done

curl   --fail   --silent   --show-error   --header "X-WZ-Health-Token: ci-health-token"   "${BASE_URL}/api/health.php"   | grep '"ok":true'

echo "Wedding Za HTTP smoke tests passed."
