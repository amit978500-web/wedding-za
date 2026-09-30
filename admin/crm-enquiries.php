<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

header(
    'Location: ' .
    wz_app_url('admin/leads.php')
);

exit;
