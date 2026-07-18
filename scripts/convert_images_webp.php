<?php
declare(strict_types=1);

$root = __DIR__ . '/../assets/images';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }
    $src = $file->getPathname();
    $out = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
    if (!$out || is_file($out)) {
        continue;
    }
    $im = $ext === 'png' ? @imagecreatefrompng($src) : @imagecreatefromjpeg($src);
    if (!$im) {
        echo "fail-read $src\n";
        continue;
    }
    imagepalettetotruecolor($im);
    imagealphablending($im, true);
    imagesavealpha($im, true);
    if (@imagewebp($im, $out, 82)) {
        echo "ok " . basename($out) . "\n";
        $count++;
    } else {
        echo "fail-webp $src\n";
    }
    imagedestroy($im);
}
echo "converted=$count\n";
