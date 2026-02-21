<?php
require_once 'headers.php';

// Только владельцы и админы могут загружать фото заведений
$user = authenticate($pdo);
if (!in_array($user['user_type'], ['owner', 'admin'])) {
    response(false, 'Доступ запрещен. Только для владельцев.', null, ['code' => 403]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, 'Разрешен только метод POST');
}

// Проверяем, был ли передан файл
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    response(false, 'Ошибка при загрузке файла или файл не выбран');
}

$file = $_FILES['photo'];

// 1. Проверка размера файла (максимум 10 МБ до сжатия)
$maxFileSize = 10 * 1024 * 1024; 
if ($file['size'] > $maxFileSize) {
    response(false, 'Размер файла превышает 10 МБ');
}

// 2. Строгая проверка MIME-типа (защита от вредоносных PHP скриптов)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedTypes)) {
    response(false, 'Неверный формат. Разрешены только JPG, PNG и WEBP.');
}

// 3. Создаем директорию, если ее нет
// Предполагается, что скрипты лежат в папке /api/, а картинки в /uploads/
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 4. Загружаем изображение в оперативную память для сжатия
$image = null;
switch ($mime) {
    case 'image/jpeg': $image = imagecreatefromjpeg($file['tmp_name']); break;
    case 'image/png':  $image = imagecreatefrompng($file['tmp_name']); break;
    case 'image/webp': $image = imagecreatefromwebp($file['tmp_name']); break;
}

if (!$image) {
    response(false, 'Не удалось обработать изображение (возможно файл поврежден)');
}

// 5. Вычисляем новые размеры. Максимум 1200px по большей стороне
$width = imagesx($image);
$height = imagesy($image);
$maxDim = 1200;

if ($width > $maxDim || $height > $maxDim) {
    $ratio = $width / $height;
    if ($ratio > 1) {
        $newWidth = $maxDim;
        $newHeight = $maxDim / $ratio;
    } else {
        $newHeight = $maxDim;
        $newWidth = $maxDim * $ratio;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Сохраняем прозрачность для PNG/WEBP
    if ($mime == 'image/png' || $mime == 'image/webp') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($image);
    $image = $newImage; // Заменяем оригинальное изображение сжатым
}

// 6. Конвертируем в формат WEBP для максимальной экономии трафика (если сервер поддерживает)
$extension = 'jpg';
if (function_exists('imagewebp')) {
    $extension = 'webp'; // WEBP весит на 30-50% меньше, чем JPG при том же качестве
} elseif ($mime === 'image/png') {
    $extension = 'png';
}

$filename = uniqid('place_') . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;
$relativePath = 'uploads/' . $filename; // Этот путь запишем в БД

// 7. Сохраняем файл
$saved = false;
if ($extension === 'webp') {
    $saved = imagewebp($image, $filepath, 80); // Качество 80%
} elseif ($extension === 'png') {
    $saved = imagepng($image, $filepath, 8); // Сжатие (0-9)
} else {
    $saved = imagejpeg($image, $filepath, 80); // Качество 80%
}

// Очищаем память
imagedestroy($image);

if ($saved) {
    response(true, 'Фото успешно загружено', [
        'photo_path' => $relativePath,
        'full_url' => $baseUrl . '/' . $relativePath
    ]);
} else {
    response(false, 'Ошибка при сохранении файла на сервере');
}
?>