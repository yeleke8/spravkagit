<?php
require_once 'headers.php';

// Этот эндпоинт не требует авторизации. 
// Android-приложение должно вызывать его при Splash Screen (загрузке).

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $config = [
        // Версионирование
        'android_min_version' => 1, // Если версия юзера ниже, просим обновиться
        'android_latest_version' => 1, // Текущая актуальная версия
        'force_update' => false, // Флаг обязательного обновления
        
        // Ссылки
        'privacy_policy_url' => 'https://yoursite.kz/privacy-policy',
        'terms_of_service_url' => 'https://yoursite.kz/terms',
        'support_telegram' => '@spravka_support',
        'support_whatsapp' => '87001112233',
        
        // Различные настройки
        'default_radius_km' => 10, // Радиус поиска по умолчанию
        'cache_ttl_hours' => 24, // Подсказка приложению, как долго кэшировать категории
        
        // Временная метка сервера (полезно для синхронизации)
        'server_time' => time(),
        'server_timezone' => date_default_timezone_get()
    ];

    response(true, 'App Config', $config);
} else {
    response(false, 'Method Not Allowed');
}
?>