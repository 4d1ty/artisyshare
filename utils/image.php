<?php

function generate_thumbnail(
    string $sourcePath,
    string $destPath,
    int $maxWidth = 300
): bool {
    $info = getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    [$width, $height, $type] = $info;

    $ratio = $width / $height;
    $newWidth = $maxWidth;
    $newHeight = (int) ($maxWidth / $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG/WebP
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled(
        $thumb,
        $src,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    $result = match ($type) {
        IMAGETYPE_JPEG => imagejpeg($thumb, $destPath, 85),
        IMAGETYPE_PNG  => imagepng($thumb, $destPath),
        IMAGETYPE_WEBP => imagewebp($thumb, $destPath, 85),
        default => false
    };

    unset($src);
    unset($thumb);

    return $result;
}
function is_valid_image(string $filePath): bool
{
    $info = getimagesize($filePath);
    if (!$info) {
        return false;
    }

    [$width, $height, $type] = $info;

    $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!in_array($type, $validTypes, true)) {
        return false;
    }

    // Optional: Check for reasonable dimensions
    if ($width < 10 || $height < 10 || $width > 10000 || $height > 10000) {
        return false;
    }

    return true;
}

function strip_exif_data(string $sourcePath, string $destPath): bool
{
    $info = getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($sourcePath);
            $result = imagejpeg($image, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($sourcePath);
            $result = imagepng($image, $destPath);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($sourcePath);
            $result = imagewebp($image, $destPath, 85);
            break;
        default:
            return false;
    }

    unset($image);

    return $result;
}
