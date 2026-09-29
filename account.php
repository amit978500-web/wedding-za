<?php

declare(strict_types=1);

require __DIR__ . '/includes/crm.php';

if (!wz_is_logged_in()) {
    header(
        'Location: login.php?role=host'
    );

    exit;
}

header(
    'Location: ' .
    wz_app_url(
        wz_crm_portal_path(
            wz_role() ?? 'host'
        )
    )
);

exit;
