<?php

declare(strict_types=1);

/**
 * Secure image upload helper.
 * Accepts JPG / JPEG / PNG / WebP / GIF from phone or PC.
 * Any pixel size is OK — oversized files are only downscaled (never cropped).
 *
 * @return array{ok:bool,file?:string,error?:string}
 */
function upload_image(array $file, string $folder, ?string $oldFile = null): array
{
    $allowedFolders = ['dishes', 'categories', 'gallery', 'settings', 'pages', 'banners'];
    if (!in_array($folder, $allowedFolders, true)) {
        return ['ok' => false, 'error' => 'Недопустимая папка загрузки.'];
    }

    $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Файл не выбран.'];
    }
    if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Файл слишком большой для сервера. Уменьшите фото или сохраните как JPG/WebP.'];
    }
    if ($errCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки файла (код ' . $errCode . ').'];
    }

    $maxMb = $folder === 'banners' ? 12 : 5;
    $maxBytes = $maxMb * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        return ['ok' => false, 'error' => 'Пустой файл.'];
    }
    if ($size > $maxBytes) {
        return ['ok' => false, 'error' => 'Размер файла не должен превышать ' . $maxMb . ' МБ.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректный файл.'];
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'error' => 'Это не изображение. Используйте JPG, PNG, WebP или GIF (не HEIC).'];
    }

    $mime = strtolower((string) ($info['mime'] ?? ''));
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowedMimes[$mime])) {
        return ['ok' => false, 'error' => 'Разрешены JPG, PNG, WebP и GIF. HEIC с iPhone сохраните как JPG.'];
    }

    // Trust MIME over filename (phone cameras often use odd names / no extension)
    $ext = $allowedMimes[$mime];

    $dir = realpath(__DIR__ . '/../uploads');
    if ($dir === false) {
        $uploadsPath = __DIR__ . '/../uploads';
        if (!is_dir($uploadsPath) && !@mkdir($uploadsPath, 0775, true)) {
            return ['ok' => false, 'error' => 'Папка uploads недоступна.'];
        }
        $dir = realpath($uploadsPath);
        if ($dir === false) {
            return ['ok' => false, 'error' => 'Папка uploads недоступна.'];
        }
    }

    $targetDir = $dir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['ok' => false, 'error' => 'Не удалось создать папку загрузки.'];
    }

    $targetReal = realpath($targetDir);
    if ($targetReal === false || !str_starts_with($targetReal, $dir)) {
        return ['ok' => false, 'error' => 'Некорректный путь загрузки.'];
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $targetReal . DIRECTORY_SEPARATOR . $newName;

    // Downscale only if huge — never crop, any ratio (wide / tall / square) is fine
    $maxWidth = $folder === 'banners' ? 1600 : 1600;
    $maxHeight = $folder === 'banners' ? 1200 : 0;
    $saved = false;
    $preserveAlpha = in_array($mime, ['image/png', 'image/webp', 'image/gif'], true);

    if (extension_loaded('gd')) {
        $src = match ($mime) {
            'image/jpeg', 'image/pjpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            'image/gif' => @imagecreatefromgif($tmp),
            default => false,
        };

        if ($src !== false) {
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = 1.0;
            if ($w > $maxWidth) {
                $scale = min($scale, $maxWidth / max(1, $w));
            }
            if ($maxHeight > 0 && $h > $maxHeight) {
                $scale = min($scale, $maxHeight / max(1, $h));
            }

            if ($scale < 1.0) {
                $nw = (int) max(1, round($w * $scale));
                $nh = (int) max(1, round($h * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                if ($preserveAlpha) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    if ($transparent !== false) {
                        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                    }
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            // Keep PNG/GIF as-is (alpha). Convert JPG → WebP when possible for size.
            $convertToWebp = !$preserveAlpha && function_exists('imagewebp') && $folder === 'banners';
            if ($convertToWebp) {
                $webpName = pathinfo($newName, PATHINFO_FILENAME) . '.webp';
                $webpPath = $targetReal . DIRECTORY_SEPARATOR . $webpName;
                if (@imagewebp($src, $webpPath, 85)) {
                    $newName = $webpName;
                    $dest = $webpPath;
                    $saved = true;
                }
            }

            if (!$saved) {
                $ok = match ($ext) {
                    'jpg' => @imagejpeg($src, $dest, 88),
                    'png' => @imagepng($src, $dest, 6),
                    'webp' => function_exists('imagewebp') ? @imagewebp($src, $dest, 85) : false,
                    'gif' => @imagegif($src, $dest),
                    default => false,
                };
                $saved = (bool) $ok;
            }
            imagedestroy($src);
        }
    }

    if (!$saved) {
        if (!@move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Не удалось сохранить файл.'];
        }
    }

    if ($oldFile !== null && $oldFile !== '' && $oldFile !== $newName) {
        delete_upload($folder, $oldFile);
    }

    return ['ok' => true, 'file' => $newName];
}

function delete_upload(string $folder, string $file): void
{
    $allowedFolders = ['dishes', 'categories', 'gallery', 'settings', 'pages', 'banners'];
    if (!in_array($folder, $allowedFolders, true)) {
        return;
    }

    $file = basename($file);
    if ($file === '' || $file === '.' || $file === '..') {
        return;
    }

    $path = __DIR__ . '/../uploads/' . $folder . '/' . $file;
    $real = realpath($path);
    $base = realpath(__DIR__ . '/../uploads/' . $folder);
    if ($real && $base && str_starts_with($real, $base) && is_file($real)) {
        @unlink($real);
    }
}
