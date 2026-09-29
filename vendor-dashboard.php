<?php

declare(strict_types=1);

require __DIR__ . '/includes/crm.php';

if (
    !wz_is_logged_in()
    || wz_role() !== 'vendor'
) {
    header(
        'Location: login.php?role=vendor'
    );

    exit;
}

header(
    'Location: ' .
    wz_app_url('crm/vendor/index.php')
);

exit;
