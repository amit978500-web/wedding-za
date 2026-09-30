<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

wz_crm_require_role('venue');

header(
    'Location: ' .
    wz_app_url('crm/venue/leads.php')
);

exit;
