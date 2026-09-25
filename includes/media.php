<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function wz_media_directory(): string
{
    return WZ_ROOT . '/uploads/media';
}

function wz_media_allowed_types(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
}

function wz_media_upload(
    array $file,
    string $altText = ''
): array {
    $pdo = wz_db();

    if (!$pdo) {
        return [
            'ok' => false,
            'message' => 'Database is required for media uploads.',
        ];
    }

    if (
        empty($file)
        || !isset($file['error'])
        || (int)$file['error'] !== UPLOAD_ERR_OK
    ) {
        return [
            'ok' => false,
            'message' => 'Please choose a valid image file.',
        ];
    }

    $temporaryPath = (string)$file['tmp_name'];

    if (!is_uploaded_file($temporaryPath)) {
        return [
            'ok' => false,
            'message' => 'The uploaded file could not be verified.',
        ];
    }

    $fileSize = (int)($file['size'] ?? 0);

    if (
        $fileSize <= 0
        || $fileSize > 8 * 1024 * 1024
    ) {
        return [
            'ok' => false,
            'message' => 'Images must be smaller than 8 MB.',
        ];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string)$finfo->file($temporaryPath);

    $allowedTypes = wz_media_allowed_types();

    if (!isset($allowedTypes[$mimeType])) {
        return [
            'ok' => false,
            'message' => 'Only JPG, PNG and WebP images are allowed.',
        ];
    }

    $imageInfo = @getimagesize($temporaryPath);

    if (!$imageInfo) {
        return [
            'ok' => false,
            'message' => 'The file is not a valid image.',
        ];
    }

    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);

    if (
        $width < 300
        || $height < 300
    ) {
        return [
            'ok' => false,
            'message' => 'Images must be at least 300 × 300 pixels.',
        ];
    }

    $directory = wz_media_directory();

    if (
        !is_dir($directory)
        && !mkdir(
            $directory,
            0775,
            true
        )
        && !is_dir($directory)
    ) {
        return [
            'ok' => false,
            'message' => 'The media directory could not be created.',
        ];
    }

    $extension = $allowedTypes[$mimeType];
    $storedName = bin2hex(
        random_bytes(18)
    ) . '.' . $extension;

    $targetPath = $directory . '/' . $storedName;

    if (!move_uploaded_file(
        $temporaryPath,
        $targetPath
    )) {
        return [
            'ok' => false,
            'message' => 'The image could not be saved.',
        ];
    }

    $relativePath = 'uploads/media/' . $storedName;

    $statement = $pdo->prepare(
        'INSERT INTO media_assets (
            uploaded_by,
            original_name,
            stored_name,
            relative_path,
            mime_type,
            file_size,
            width,
            height,
            alt_text
        ) VALUES (
            :uploaded_by,
            :original_name,
            :stored_name,
            :relative_path,
            :mime_type,
            :file_size,
            :width,
            :height,
            :alt_text
        )'
    );

    $statement->execute([
        'uploaded_by' => wz_user()['id'] ?? null,
        'original_name' => substr(
            basename(
                (string)($file['name'] ?? 'image')
            ),
            0,
            255
        ),
        'stored_name' => $storedName,
        'relative_path' => $relativePath,
        'mime_type' => $mimeType,
        'file_size' => $fileSize,
        'width' => $width,
        'height' => $height,
        'alt_text' => substr(
            trim($altText),
            0,
            255
        ),
    ]);

    $mediaId = (int)$pdo->lastInsertId();

    wz_audit(
        'media.uploaded',
        'media_asset',
        $mediaId,
        [
            'relative_path' => $relativePath,
            'mime_type' => $mimeType,
        ]
    );

    return [
        'ok' => true,
        'id' => $mediaId,
        'path' => $relativePath,
        'url' => wz_app_url($relativePath),
        'width' => $width,
        'height' => $height,
        'message' => 'Image uploaded successfully.',
    ];
}

function wz_vendor_append_media(
    int $userId,
    string $relativePath
): bool {
    $pdo = wz_db();

    if (!$pdo) {
        return false;
    }

    $statement = $pdo->prepare(
        'SELECT id, gallery_json, hero_image_url
         FROM vendor_profiles
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    $profile = $statement->fetch();

    if (!$profile) {
        return false;
    }

    $gallery = json_decode(
        (string)($profile['gallery_json'] ?? '[]'),
        true
    );

    if (!is_array($gallery)) {
        $gallery = [];
    }

    if (!in_array(
        $relativePath,
        $gallery,
        true
    )) {
        $gallery[] = $relativePath;
    }

    $gallery = array_slice(
        $gallery,
        -20
    );

    $heroImage = trim(
        (string)($profile['hero_image_url'] ?? '')
    );

    if ($heroImage === '') {
        $heroImage = $relativePath;
    }

    $update = $pdo->prepare(
        'UPDATE vendor_profiles
         SET gallery_json = :gallery_json,
             hero_image_url = :hero_image_url
         WHERE id = :id'
    );

    $update->execute([
        'gallery_json' => json_encode($gallery),
        'hero_image_url' => $heroImage,
        'id' => (int)$profile['id'],
    ]);

    return true;
}
