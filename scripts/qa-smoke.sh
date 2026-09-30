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
  "/register.php?role=vendor"
  "/register.php?role=venue"
  "/login.php?role=host"
  "/login.php?role=vendor"
  "/login.php?role=venue"
  "/crm/customer/index.php"
  "/crm/vendor/index.php"
  "/crm/venue/index.php"
  "/crm/venue/leads.php"
  "/crm/venue/leads-new.php"
  "/crm/venue/leads-followups.php"
  "/crm/venue/leads-site-visits.php"
  "/crm/venue/leads-lost.php"
  "/crm/venue/functions.php"
  "/crm/venue/functions-upcoming.php"
  "/crm/venue/functions-calendar.php"
  "/crm/venue/bookings.php"
  "/crm/venue/payments.php"
  "/crm/venue/reports.php"
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
