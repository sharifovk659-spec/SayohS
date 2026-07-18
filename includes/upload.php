<?php

declare(strict_types=1);

/**
 * Secure image upload helper.
 *
 * @return array{ok:bool,file?:string,error?:string}
 */
function upload_image(array $file, string $folder, ?string $oldFile = null): array
{
    $allowedFolders = ['dishes', 'categories', 'gallery', 'settings', 'pages'];
    if (!in_array($folder, $allowedFolders, true)) {
        return ['ok' => false, 'error' => 'Недопустимая папка загрузки.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Файл не выбран.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки файла.'];
    }

    $maxBytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || (int) $file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'Размер файла не должен превышать 5 МБ.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректный файл.'];
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением.'];
    }

    $mime = $info['mime'] ?? '';
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowedMimes[$mime])) {
        return ['ok' => false, 'error' => 'Разрешены только JPG, PNG и WebP.'];
    }

    $origName = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'error' => 'Недопустимое расширение файла.'];
    }

    // Prefer MIME-derived extension
    $ext = $allowedMimes[$mime];

    $dir = realpath(__DIR__ . '/../uploads');
    if ($dir === false) {
        return ['ok' => false, 'error' => 'Папка uploads недоступна.'];
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

    // Resize / optional WebP
    $maxWidth = 1600;
    $saved = false;

    if (extension_loaded('gd')) {
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default => false,
        };

        if ($src !== false) {
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > $maxWidth) {
                $nw = $maxWidth;
                $nh = (int) max(1, round($h * ($maxWidth / $w)));
                $dst = imagecreatetruecolor($nw, $nh);
                if ($mime === 'image/png' || $mime === 'image/webp') {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            if (function_exists('imagewebp')) {
                $webpName = pathinfo($newName, PATHINFO_FILENAME) . '.webp';
                $webpPath = $targetReal . DIRECTORY_SEPARATOR . $webpName;
                if (@imagewebp($src, $webpPath, 82)) {
                    $newName = $webpName;
                    $dest = $webpPath;
                    $saved = true;
                }
            }

            if (!$saved) {
                $ok = match ($ext) {
                    'jpg' => @imagejpeg($src, $dest, 85),
                    'png' => @imagepng($src, $dest, 6),
                    'webp' => function_exists('imagewebp') ? @imagewebp($src, $dest, 82) : false,
                    default => false,
                };
                $saved = (bool) $ok;
            }
            imagedestroy($src);
        }
    }

    if (!$saved) {
        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Не удалось сохранить файл.'];
        }
    }

    if ($oldFile) {
        delete_upload($folder, $oldFile);
    }

    return ['ok' => true, 'file' => $newName];
}

function delete_upload(string $folder, string $file): void
{
    $allowedFolders = ['dishes', 'categories', 'gallery', 'settings', 'pages'];
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
