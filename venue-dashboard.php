<?php

declare(strict_types=1);

require __DIR__ . '/includes/crm.php';

if (
    !wz_is_logged_in()
    || wz_role() !== 'venue'
) {
    header(
        'Location: login.php?role=venue'
    );

    exit;
}

header(
    'Location: ' .
    wz_app_url('crm/venue/index.php')
);

exit;
