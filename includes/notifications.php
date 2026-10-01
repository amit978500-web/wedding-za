<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function wz_notification_default_preferences(): array
{
    return [
        'email_enabled' => false,
        'push_enabled' => false,
        'storage_ready' => false,
    ];
}

function wz_notification_preferences(int $userId): array
{
    $defaults = wz_notification_default_preferences();
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return $defaults;
    }

    try {
        $statement = $pdo->prepare(
            'SELECT email_enabled, push_enabled
             FROM notification_preferences
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        $row = $statement->fetch();

        return [
            'email_enabled' => !empty($row['email_enabled']),
            'push_enabled' => !empty($row['push_enabled']),
            'storage_ready' => true,
        ];
    } catch (Throwable $exception) {
        return $defaults;
    }
}

function wz_notification_update_preferences(
    int $userId,
    bool $emailEnabled,
    bool $pushEnabled
): array {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return [
            'ok' => false,
            'message' => 'Notification preferences are unavailable.',
        ];
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO notification_preferences (
                user_id,
                email_enabled,
                push_enabled
            ) VALUES (
                :user_id,
                :email_enabled,
                :push_enabled
            )
            ON DUPLICATE KEY UPDATE
                email_enabled = VALUES(email_enabled),
                push_enabled = VALUES(push_enabled)'
        );

        $statement->execute([
            'user_id' => $userId,
            'email_enabled' => $emailEnabled ? 1 : 0,
            'push_enabled' => $pushEnabled ? 1 : 0,
        ]);

        return [
            'ok' => true,
            'message' => 'Notification preferences saved.',
        ];
    } catch (Throwable $exception) {
        error_log(
            'Wedding Za notification preference update failed: '
            . $exception->getMessage()
        );

        return [
            'ok' => false,
            'message' => 'Run the latest database upgrade before enabling email or push alerts.',
        ];
    }
}

function wz_notification_delivery_config(): array
{
    $config = wz_config()['notifications'] ?? [];

    return [
        'webhook_url' => trim(
            (string)($config['delivery_webhook_url'] ?? '')
        ),
        'webhook_token' => trim(
            (string)($config['delivery_webhook_token'] ?? '')
        ),
        'email_enabled' => !empty($config['email_enabled']),
        'push_enabled' => !empty($config['push_enabled']),
        'from_email' => trim(
            (string)($config['from_email'] ?? '')
        ),
        'from_name' => trim(
            (string)($config['from_name'] ?? 'Wedding Za')
        ) ?: 'Wedding Za',
    ];
}

function wz_notification_delivery_status(): array
{
    $config = wz_notification_delivery_config();

    return [
        'provider_configured' => $config['webhook_url'] !== '',
        'email_available' => $config['webhook_url'] !== ''
            && $config['email_enabled'],
        'push_available' => $config['webhook_url'] !== ''
            && $config['push_enabled'],
    ];
}

function wz_notification_log_delivery(
    ?int $notificationId,
    int $userId,
    string $channel,
    string $status,
    ?int $responseCode = null,
    ?string $errorMessage = null
): void {
    $pdo = wz_db();

    if (!$pdo || $userId <= 0) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO notification_delivery_log (
                notification_id,
                user_id,
                channel,
                status,
                provider,
                response_code,
                error_message
            ) VALUES (
                :notification_id,
                :user_id,
                :channel,
                :status,
                :provider,
                :response_code,
                :error_message
            )'
        );

        $statement->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'channel' => $channel,
            'status' => $status,
            'provider' => 'webhook',
            'response_code' => $responseCode,
            'error_message' => $errorMessage !== null
                ? mb_substr($errorMessage, 0, 500)
                : null,
        ]);
    } catch (Throwable $exception) {
        // External delivery logging must never interrupt CRM workflows.
    }
}

function wz_notification_post_webhook(
    string $url,
    string $token,
    array $payload
): array {
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    if ($json === false) {
        return [
            'ok' => false,
            'status' => null,
            'error' => 'Could not encode notification payload.',
        ];
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Wedding-Za-Notifications/1.0',
    ];

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if (function_exists('curl_init')) {
        $handle = curl_init($url);

        curl_setopt_array(
            $handle,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_FOLLOWLOCATION => false,
            ]
        );

        $response = curl_exec($handle);
        $status = (int)curl_getinfo(
            $handle,
            CURLINFO_RESPONSE_CODE
        );
        $error = curl_error($handle);

        curl_close($handle);

        return [
            'ok' => $response !== false
                && $status >= 200
                && $status < 300,
            'status' => $status ?: null,
            'error' => $error !== ''
                ? $error
                : (
                    $status >= 200 && $status < 300
                        ? null
                        : 'Notification webhook returned HTTP ' . $status . '.'
                ),
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $json,
            'timeout' => 6,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents(
        $url,
        false,
        $context
    );

    $status = null;

    foreach ($http_response_header ?? [] as $header) {
        if (
            preg_match(
                '/^HTTP\/\S+\s+(\d{3})/',
                $header,
                $matches
            )
        ) {
            $status = (int)$matches[1];
            break;
        }
    }

    return [
        'ok' => $response !== false
            && $status !== null
            && $status >= 200
            && $status < 300,
        'status' => $status,
        'error' => $response === false
            ? 'Notification webhook request failed.'
            : (
                $status !== null
                && ($status < 200 || $status >= 300)
                    ? 'Notification webhook returned HTTP ' . $status . '.'
                    : null
            ),
    ];
}

function wz_notification_dispatch_external(
    int $notificationId,
    int $userId,
    string $type,
    string $title,
    ?string $body,
    ?string $actionUrl
): void {
    $config = wz_notification_delivery_config();
    $preferences = wz_notification_preferences($userId);

    if (
        $config['webhook_url'] === ''
        || !$preferences['storage_ready']
    ) {
        return;
    }

    $channels = [];

    if (
        $preferences['email_enabled']
        && $config['email_enabled']
    ) {
        $channels[] = 'email';
    }

    if (
        $preferences['push_enabled']
        && $config['push_enabled']
    ) {
        $channels[] = 'push';
    }

    if (!$channels) {
        return;
    }

    $pdo = wz_db();

    if (!$pdo) {
        return;
    }

    $statement = $pdo->prepare(
        'SELECT id, name, email, role
         FROM users
         WHERE id = :id
         LIMIT 1'
    );

    $statement->execute([
        'id' => $userId,
    ]);

    $user = $statement->fetch();

    if (!$user) {
        return;
    }

    $payload = [
        'event' => 'wedding_za.notification',
        'notification' => [
            'id' => $notificationId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl !== null
                ? wz_app_url($actionUrl)
                : null,
        ],
        'recipient' => [
            'user_id' => $userId,
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ],
        'channels' => $channels,
        'sender' => [
            'name' => $config['from_name'],
            'email' => $config['from_email'],
        ],
        'sent_at' => date('c'),
    ];

    $result = wz_notification_post_webhook(
        $config['webhook_url'],
        $config['webhook_token'],
        $payload
    );

    foreach ($channels as $channel) {
        wz_notification_log_delivery(
            $notificationId,
            $userId,
            $channel,
            $result['ok'] ? 'sent' : 'failed',
            $result['status'],
            $result['error']
        );
    }

    if (!$result['ok']) {
        error_log(
            'Wedding Za external notification delivery failed: '
            . (string)($result['error'] ?? 'Unknown error')
        );
    }
}

function wz_notification_send(
    int $userId,
    string $type,
    string $title,
    ?string $body = null,
    ?string $actionUrl = null
): ?int {
    $pdo = wz_db();

    if (
        !$pdo
        || $userId <= 0
        || trim($title) === ''
    ) {
        return null;
    }

    $statement = $pdo->prepare(
        'INSERT INTO user_notifications (
            user_id,
            type,
            title,
            body,
            action_url
        ) VALUES (
            :user_id,
            :type,
            :title,
            :body,
            :action_url
        )'
    );

    $statement->execute([
        'user_id' => $userId,
        'type' => mb_substr(trim($type), 0, 60),
        'title' => mb_substr(trim($title), 0, 180),
        'body' => $body !== null
            ? mb_substr(trim($body), 0, 500)
            : null,
        'action_url' => $actionUrl !== null
            ? mb_substr(trim($actionUrl), 0, 500)
            : null,
    ]);

    $notificationId = (int)$pdo->lastInsertId();

    wz_notification_dispatch_external(
        $notificationId,
        $userId,
        $type,
        $title,
        $body,
        $actionUrl
    );

    return $notificationId;
}
