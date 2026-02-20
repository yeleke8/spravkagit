-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Фев 20 2026 г., 23:54
-- Версия сервера: 10.4.26-MariaDB
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `spravka`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `cat_id` int(11) UNSIGNED NOT NULL,
  `cat_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cat_slug` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `cat_parent_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`cat_id`, `cat_name`, `cat_slug`, `cat_parent_id`) VALUES
(1, 'Кафе и Рестораны', 'food', NULL),
(2, 'Красота и Спорт', 'beauty-sport', NULL),
(3, 'Медицина', 'health', NULL),
(4, 'Обучение', 'education', NULL),
(5, 'Развлечения и Досуг', 'entertainment', NULL),
(6, 'Магазины', 'shops', NULL),
(7, 'Услуги мастеров', 'services', NULL),
(8, 'Гостиницы и Туризм', 'tourism', NULL),
(9, 'Государственные и Социальные услуги', 'government-social-services', NULL),
(10, 'Транспорт и Логистика', 'transport-logistics', NULL),
(11, 'Кафе / Рестораны', 'restaurants', 1),
(12, 'Кофейни', 'coffee', 1),
(13, 'Фастфуд', 'fast-food', 1),
(14, 'Кондитерские', 'confectionery', 1),
(15, 'Салоны красоты', 'beauty', 2),
(16, 'Барбершопы', 'barbershop', 2),
(17, 'SPA, Массаж', 'spa', 2),
(18, 'Фитнес', 'fitness', 2),
(19, 'Бассейны', 'swimming-pools', 2),
(20, 'Аптеки', 'apteka', 3),
(21, 'Клиники', 'clinic', 3),
(22, 'Стоматологии', 'stomatologia', 3),
(23, 'Ветеринария (клиники и аптеки)', 'veterinary-clinics', 3),
(24, 'Детские сады', 'kindergartens', 4),
(25, 'Школы', 'schools', 4),
(26, 'Колледжи', 'colleges', 4),
(27, 'Университеты', 'universities', 4),
(28, 'Языковые школы', 'language-schools', 4),
(29, 'Подготовка к ЕНТ', 'ent-preparation', 4),
(30, 'Автошколы', 'driving-schools', 4),
(31, 'Кинотеатры', 'cinemas', 5),
(32, 'Торговые центры (ТЦ)', 'shopping-centers', 5),
(33, 'Боулинг и Бильярд', 'bowling-billiards', 5),
(34, 'Парки и зоны отдыха', 'parks-recreation', 5),
(35, 'Детские игровые центры', 'children-entertainment-centers', 5),
(36, 'Продукты', 'grocery-products', 6),
(37, 'Одежда, Обувь', 'clothing', 6),
(38, 'Мобильные аксессуары', 'mobile', 6),
(39, 'Бытовая техника', 'home-appliances', 6),
(40, 'Строительные материалы', 'construction', 6),
(41, 'Автозапчасти', 'auto-parts', 6),
(42, 'Цветы и Подарки', 'flowers', 6),
(43, 'Зоотовары', 'pet-shops', 6),
(44, 'Мебель', 'furniture', 6),
(45, 'Автосервисы (СТО)', 'auto-service', 7),
(46, 'Автомойки', 'car-wash', 7),
(47, 'Шиномонтаж', 'tire-fitting', 7),
(48, 'Сантехники и Электрики', 'plumbing', 7),
(49, 'Ремонт техники (Телефоны, ноутбуки)', 'repair', 7),
(50, 'Ателье и Химчистки', 'dry-cleaning', 7),
(51, 'Грузоперевозки', 'cargo-transport', 7),
(52, 'Исторические места', 'historical-places', 8),
(53, 'Отели', 'hotels', 8),
(54, 'Хостелы', 'hostels', 8),
(55, 'Зоны отдыха', 'recreation-areas', 8),
(56, 'Музеи', 'museums', 8),
(57, 'Сувенирные лавки', 'souvenirs', 8),
(58, 'ЦОНы (Госуслуги)', 'con-public-services', 9),
(59, 'Банки, Терминалы и Обменные пункты', 'banks-terminals', 9),
(60, 'Почта', 'post-office', 9),
(61, 'Нотариусы и Юридические консультации', 'notaries-legal', 9),
(62, 'Полиция и Экстренные службы', 'police-emergency', 9),
(63, 'ЖД и Автовокзалы и Аэропорт', 'stations-airport', 10),
(64, 'Прокат автомобилей и велосипедов', 'car-bike-rent', 10),
(65, 'АЗС / Электрозаправки / Газовые заправки', 'gas-stations', 10);

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `rating` int(1) DEFAULT 5,
  `comment` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - на модерации, 1 - одобрен',
  `created_at` datetime DEFAULT current_timestamp(),
  `owner_reply` text DEFAULT NULL COMMENT 'Ответ представителя заведения',
  `reply_created_at` datetime DEFAULT NULL COMMENT 'Дата ответа'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `comments`
--

INSERT INTO `comments` (`comment_id`, `user_id`, `post_id`, `rating`, `comment`, `is_approved`, `created_at`, `owner_reply`, `reply_created_at`) VALUES
(1, 1, 8, 1, 'Топовый парк', 1, '2026-02-06 11:03:26', NULL, NULL),
(23, 1, 1, 5, 'Шоу фонтанов просто невероятное! Ощущение, что попал в сказку 1001 ночи. Всем советую приходить вечером.', 1, '2026-02-08 19:20:00', 'Спасибо за ваш отзыв! Ждем вас снова!', '2026-02-09 10:00:00'),
(24, 4, 1, 4, 'Очень красиво, но в выходные слишком много людей. Трудно найти свободный столик в ресторанах.', 1, '2026-02-10 15:30:00', NULL, NULL),
(25, 1, 2, 5, 'Лучший кофе в городе. Атмосфера очень уютная, идеально для работы с ноутбуком.', 1, '2026-02-11 09:15:00', 'Рады, что вам у нас нравится! У нас как раз обновилось меню десертов.', '2026-02-11 11:00:00'),
(26, 4, 2, 3, 'Кофе вкусный, но ждали заказ 20 минут. Персонал не очень торопится.', 1, '2026-02-07 14:00:00', 'Приносим извинения за ожидание, исправимся!', '2026-02-07 16:00:00'),
(27, 6, 3, 5, 'Великое место с мощной энергетикой. Архитектура поражает воображение. Обязательно берите гида.', 1, '2026-02-01 12:00:00', NULL, NULL),
(28, 1, 4, 4, 'Хороший выбор продуктов, всегда свежая выпечка. Но на кассах иногда очереди.', 1, '2026-02-11 10:45:00', NULL, NULL),
(29, 4, 5, 5, 'Шикарный отель. Завтраки очень разнообразные, спа-зона на высшем уровне. Сервис соответствует бренду.', 1, '2026-02-05 08:30:00', 'Благодарим за выбор нашего отеля!', '2026-02-05 12:00:00'),
(30, 6, 6, 5, 'Быстро, вкусно и недорого. Бургеры всегда горячие. Каспи КР работает без проблем.', 1, '2026-02-10 18:00:00', NULL, NULL),
(31, 6, 7, 2, 'Купил телефон, через неделю начал глючить. В сервисном центре долго принимали заявку.', 1, '2026-02-09 11:00:00', 'Здравствуйте! Нам жаль, что вы столкнулись с такой ситуацией. Напишите нам номер чека.', '2026-02-09 13:45:00');

--
-- Триггеры `comments`
--
DELIMITER $$
CREATE TRIGGER `recalc_rating_delete` AFTER DELETE ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = OLD.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = OLD.post_id AND is_approved = 1)
    WHERE post_id = OLD.post_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `recalc_rating_insert` AFTER INSERT ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1)
    WHERE post_id = NEW.post_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `recalc_rating_update` AFTER UPDATE ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1)
    WHERE post_id = NEW.post_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `log_post_views`
--

CREATE TABLE `log_post_views` (
  `view_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `log_post_views`
--

INSERT INTO `log_post_views` (`view_id`, `post_id`, `user_id`, `ip_address`, `viewed_at`) VALUES
(1, 3, NULL, 0x7f000001, '2026-02-14 17:13:10'),
(2, 5, NULL, 0x7f000001, '2026-02-14 17:13:17'),
(3, 1, NULL, 0x7f000001, '2026-02-14 17:14:40'),
(4, 3, NULL, 0x7f000001, '2026-02-14 17:21:19'),
(5, 3, NULL, 0x7f000001, '2026-02-14 17:34:40'),
(6, 3, NULL, 0x7f000001, '2026-02-15 06:01:09'),
(7, 1, NULL, 0x7f000001, '2026-02-15 06:10:14'),
(8, 3, NULL, 0x7f000001, '2026-02-15 06:44:00'),
(9, 3, NULL, 0x7f000001, '2026-02-15 06:51:29'),
(10, 3, NULL, 0x7f000001, '2026-02-15 07:00:55'),
(11, 3, NULL, 0x7f000001, '2026-02-15 07:05:59'),
(12, 5, NULL, 0x7f000001, '2026-02-15 07:06:04'),
(13, 10, NULL, 0x7f000001, '2026-02-15 07:06:13'),
(14, 3, NULL, 0x7f000001, '2026-02-15 10:07:49');

-- --------------------------------------------------------

--
-- Структура таблицы `post`
--

CREATE TABLE `post` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Название',
  `psevdonim` text NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Описание',
  `address` text NOT NULL COMMENT 'Адрес',
  `coordinates` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `worktime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`worktime`)),
  `photo` varchar(255) DEFAULT 'uploads/default.jpg' COMMENT 'главное фото',
  `views` int(11) DEFAULT 0 COMMENT 'Количество просмотров',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Создан',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Изменен',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 - модерация/черновик, 1 - опубликовано, 2 - удален',
  `rating_avg` decimal(3,2) DEFAULT 0.00 COMMENT 'Рейтинг',
  `rating_count` int(11) DEFAULT 0 COMMENT 'Количество оценок',
  `owner_id` int(11) DEFAULT 2 COMMENT 'ID владельца (users.user_id)',
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Специфичные характеристики (JSON)' CHECK (json_valid(`attributes`)),
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Контакты и соцсети (JSON)' CHECK (json_valid(`contacts`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `post`
--

INSERT INTO `post` (`post_id`, `title`, `psevdonim`, `slug`, `description`, `address`, `coordinates`, `latitude`, `longitude`, `worktime`, `photo`, `views`, `created_at`, `updated_at`, `status`, `rating_avg`, `rating_count`, `owner_id`, `attributes`, `contacts`) VALUES
(1, 'Karavansaray Turkistan', 'karavansaray, каравансарай, керуенсарай, караван, туркестан, түркістан, karavan, caravan, керуен, сарай, сарайы, сарайда, шоу, аквашоу, театр, летающий, фонтаны, лодки, қайық, қайықтар, отель, қонақүй, трц, молл, mall, shopping, базар, рынок, дүкендер, дукендер, turkistan, turkestan, karavansarai, caravansaray, caravansary, летающийтеатр, алтынсамұрық, самрук, samruk, сушоуы, аквашоу', 'karavansaray', 'Уникальный туристический комплекс в восточном стиле. Здесь есть отели, рестораны, и проводится водное шоу. Отличное место для прогулок всей семьей.', 'пр. Б. Саттарханова, 20А', '', '43.29650000', '68.25260000', '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-24:00\", \"sun\": \"closed\"}', 'https://avatars.mds.yandex.net/get-altay/4234257/2a00000177f727b549a92db6c354c5730ead/XXL', 1156, '2026-02-06 10:19:55', '2026-02-20 23:49:36', 1, '4.50', 2, 3, '{\"avg_check\": 8000, \"cuisine\": \"Восточная, Турецкая\", \"has_delivery\": 0, \"has_vip\": 1}', '{\"phone\": \"+7 (725) 333-33-33\", \"whatsapp\": \"87010000001\", \"instagram\": \"@karavansaray\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(2, 'Global Coffee', 'global, globalcoffee, глобал, кофе, кофейня, глобалкофе, глобал-кофе, коффе, кофи, coffi, coffe, coffee, coffeehouse, кофехана, кофеханасы, кофеханада, кофесі, кофеси, кофе-бар, кофебар, takeaway, кофешка, кафе, кафейня, бариста, глобалла, глобалға, глобалга, глобалда, латте, раф, капучино, айс, ice, салем, бра, bro', 'globalcoffee', 'Отличное место для завтраков и встречи с друзьями. Вкусный кофе, десерты и уютная атмосфера.', 'ул. Тауке хана, 15', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSWA0w2D5Tbb6Dg7z_H08Of2RFV2wOiluhY1g&s', 86, '2026-02-06 10:19:55', '2026-02-14 19:20:27', 1, '4.00', 2, 3, '{\"avg_check\": 3500, \"cuisine\": \"Кофейня, Европейская\", \"has_delivery\": 1, \"has_vip\": 0}', '{\"phone\": \"+7 (777) 111-22-33\", \"whatsapp\": \"87771112233\", \"instagram\": \"@globalcoffee_turk\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(3, 'Мавзолей Ходжи Ахмеда Ясави', 'мавзолей, кесене, ясави, яссауи, ясауи, яссави, иассауи, иасауи, ходжа, қожа, кожа, ахмет, ахмед, ahmet, ahmed, yasawi, yassawi, yasavi, yassavi, yesevi, кесенесі, кесенеси, мавзолейі, мавзолейи, мазар, мазары, азрет, хазрет, хазірет, султан, түркістан, туркестан, turkistan, turkestan, зиярат, тәуап, мечеть, мешіт, mosque, tomb, shrine, комплекс, музей, иасави, иассави мешіт мешіті қыдыратын жер', 'hojaahmedyassaui', 'Главная достопримечательность Туркестана, объект всемирного наследия ЮНЕСКО. Священное место для паломников.', 'Центр города', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"closed\", \"sun\": \"closed\"}', 'https://tengrinews.kz/userdata/news/2024/news_549025/thumb_m/photo_487452.jpg', 514, '2026-02-06 10:19:55', '2026-02-15 13:07:49', 1, '5.00', 1, 2, NULL, '{\"phone\": \"-\", \"whatsapp\": \"-\", \"instagram\": \"-\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(4, 'Magnum Super', 'magnum, магнум, супермаркет, дүкен, дукен, магнүм, magnim, magnumm, magum, magnun, супер, маркет, магазин, продуктовый, гипермаркет, дүкені, дукени, супермаркеті, супермаркети, supermarket, market, shop, store, food, магнумда, магнумга, магнумка, магнумды, магнумнан, магнум-супер, magnumsuper, супермагнум', 'magnumsuper', 'Большой супермаркет с широким ассортиментом продуктов, бытовой химии и товаров для дома. Всегда свежие овощи и фрукты.', 'ул. Амира Тимура, 5', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://avatars.mds.yandex.net/get-altay/1132477/2a00000188b0ccc812c7648b319429fe3242/L_height', 121, '2026-02-06 10:19:55', '2026-02-14 19:05:21', 1, '4.00', 1, 2, NULL, '{\"phone\": \"+7 (725) 222-44-55\", \"whatsapp\": \"-\", \"instagram\": \"@magnum_kz\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(5, 'Rixos Turkistan', 'rixos, риксос, туркестан, түркістан, туркестан, туркістан, turkistan, turkestan, turkystan, riksos, rixsos, отель, гостиница, қонақүй, конакуй, қонақ, отельі, отели, resort, spa, спа, шипажай, демалыс, люкс, luxury, 5stars, 5звезд, риксоста, риксоска, туркестанда, туркестанга, туркистан, риксус, rixus, ryxos үй үйі переночевать', 'rixosturkestan', 'Роскошный отель с ресторанами высокой кухни, спа-центром и бассейном. Идеально для деловых встреч и отдыха.', 'пр. Б. Саттарханова, 1', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://cf.bstatic.com/xdata/images/hotel/max500/802682536.jpg?k=ddba355ccbfee3a08379f37b5e382d0816ddf3939487f0a3a6ec4727381bd484&o=&hp=1', 204, '2026-02-06 10:19:55', '2026-02-15 10:06:04', 1, '5.00', 1, 2, '{\"stars_count\": 5, \"check_in_time\": \"14:00\", \"check_out_time\": \"12:00\", \"breakfast_included\": 1}', '{\"phone\": \"+7 (725) 334-88-88\", \"whatsapp\": \"-\", \"instagram\": \"@rixosturkistan\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(6, 'Salam Bro', 'salam, salambro, саламбро, салам, бро, салаам, саламброу, salambrow, соламбро, саланбро, бургер, бургеры, burger, burgers, фастфуд, fastfood, еда, тамақ, тамак, дәмхана, дамхана, кафе, закусочная, халал, halal, халяль, халял, куры, крылышки, чикен, chicken, саламда, саламға, саламга, саламнан, лаваш тамақ', 'salambro', 'Популярная сеть фаст-фуда. Вкусные бургеры, хот-доги и наггетсы по доступным ценам.', 'мкр. Отрар, 10', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR1fJUMeaT7bubgB7OeAbOYD9ZRHPdasGRhMA&s', 95, '2026-02-06 10:19:55', '2026-02-14 15:41:27', 0, '5.00', 1, 2, '{\"avg_check\": 1800, \"cuisine\": \"Фастфуд, Бургеры\", \"has_delivery\": 1, \"has_vip\": 0}', '{\"phone\": \"+7 (707) 999-88-77\", \"whatsapp\": \"87079998877\", \"instagram\": \"@salambro_turkestan\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(7, 'Sulpak', 'sulpak, сулпак, sulpag, сулпаг, sulbak, сулбак, solpak, солпак, slupak, сулпакк, sulpakk, сулпака, сулпаке, сулпакта, сулпақ, сулпақты, сулпақта, дүкен, дүкені, дукен, дукени, техника, электроника, электроникасы, магазин, бытовая, бытовка, быттехника, store, shop, market, electronic, техмаркет, сулпакка', 'sulpak', 'Магазин бытовой техники и электроники. Широкий выбор смартфонов, ноутбуков и телевизоров.', 'ТЦ Karavan', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://static-pano.maps.yandex.ru/v1/?panoid=1480818731_788211508_23_1703148932&size=500%2C240&azimuth=-28&tilt=10&api_key=maps&signature=QLI7VpiwBy0Kg3Mpf632nha2TVJd86hgZk814Vffw3o=', 60, '2026-02-06 10:19:55', '2026-02-14 15:40:54', 1, '2.00', 1, 2, NULL, '{\"phone\": \"3210\", \"whatsapp\": \"-\", \"instagram\": \"@sulpak\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(8, 'Парк Женис', 'жеңіс, женис, женіс, zhenis, jenis, genis, zhenys, jenys, zenis, zhenish, jenish, саябақ, саябағы, саябагы, саябак, саябакы, саябақта, саябаққа, парк, паркі, паркы, парке, парку, парка, park, parki, parky, sayabaq, sayabak, sayabagy, бағы, багы, сквер, бақы, бакы', 'parkzhenis', 'Зеленый парк в центре города с фонтанами и лавочками. Отличное место для вечерних прогулок.', 'ул. Есим хана', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://www.matritca.kz/uploads/posts/2021-06/1623744698_3fcf8a3c-e99c-42ef-8a62-58486c017076.jpg', 117, '2026-02-06 10:19:55', '2026-02-14 15:37:55', 1, '1.00', 1, 2, NULL, '{\"phone\": \"-\", \"whatsapp\": \"-\", \"instagram\": \"-\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(9, 'МКТУ им. Х. А. Ясави', 'мкту, iktu, ayu, ayü, ясави, яссави, ясауи, яссауи, иассауи, иасауи, иассави, иасави, yasawi, yassawi, yasavi, yassavi, yesevi, yesewi, iassawi, iasawi, университет, университеті, университети, универ, university, universitet, universiteti, ясавидің, яссауидің, яссауидін, ходжа, қожа, кожа, ахмет, ахмед, ahmet, ahmed, hoca, hodja', 'mktu-yasavi', 'Международный казахско-турецкий университет имени Ходжи Ахмеда Ясави. Первый вуз со статусом международного в Казахстане.', 'пр. Б. Саттарханова, 29', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'uploads/default.jpg', 0, '2026-02-11 09:30:53', '2026-02-14 15:55:50', 1, '0.00', 0, 2, '{\"status_type\": \"Международный университет\", \"has_grants\": 1, \"has_dormitory\": 1, \"degree_types\": \"Бакалавриат, Магистратура, Докторантура, Резидентура\", \"language_instruction\": \"Казахский, Русский, Английский, турецкий\", \"has_license\": 1}', '{\"phone\": \"+7 (72533) 3-33-33\", \"whatsapp\": \"\", \"instagram\": \"@ayu_edu_kz\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(10, 'Аптека Europharma', 'europharma, еврофарма, еурофарма, eurofarm, eurofarma, evropharma, evrofarma, еврофарм, еурофарм, эврофарма, эурофарма, еурфарма, дәріхана, дарихана, дәріханасы, дариханасы, аптека, аптеки, pharmacy, pharma, farm, europarma, ефрофарма, европарма, еуропарма, дәрі, дари, лекарства', 'europharma-1', NULL, 'ул. Кожанова, 12', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'https://ams2-cdn.2gis.com/previews/1222441214591107072/f06c9145-58f8-4fd0-b087-3e86a12fda24/3/ru/328x170', 47, '2026-02-11 10:20:29', '2026-02-15 15:08:04', 1, '0.00', 0, 2, NULL, '{\"phone\": \"\", \"whatsapp\": \"\", \"instagram\": \"\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(11, 'Детский мир', 'детский, децкий, дедский, детскый, детски, мир, миры, мира, детмир, балалар, балаларга, балаларға, әлемі, алеми, алемы, әлеумет, дүкені, дукени, дүкен, дукен, магазин, магазині, магазини, detskiy, detskii, detmir, detsky, detckiy, balalar, alemi, alemy, duken, dukeni, shop, store, kids, baby', 'detskiy-mir', NULL, 'ТЦ Altyn Orda', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'uploads/default.jpg', 31, '2026-02-11 10:20:29', '2026-02-14 19:30:19', 1, '0.00', 0, 2, NULL, '{\"phone\": \"\", \"whatsapp\": \"\", \"instagram\": \"\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}'),
(12, 'Кинотеатр Cinema 3D', 'cinema, синема, 3d, 3д, кинотеатр, кинотеатры, кино, синима, cinima, kinema, sinema, триде, триди, киношка, кинозалы, кинотеатрында, кинотеатрына, movie, theater, theatre, film, синема3д, cinema3d, синемад, синема3, кинозал, экран, сеанс, билеты', 'cinema-3d', NULL, 'пр. Тауке Хана, 100', '', NULL, NULL, '{\"mon\": \"09:00-21:00\", \"tue\": \"09:00-21:00\", \"wed\": \"09:00-21:00\", \"thu\": \"09:00-21:00\", \"fri\": \"09:00-21:00\", \"sat\": \"10:00-18:00\", \"sun\": \"closed\"}', 'uploads/default.jpg', 151, '2026-02-11 10:20:29', '2026-02-14 19:46:38', 1, '0.00', 0, 2, NULL, '{\"phone\": \"\", \"whatsapp\": \"\", \"instagram\": \"\", \"maps\": {\"2gis\": \"\", \"yandex\": \"\"}}');

-- --------------------------------------------------------

--
-- Структура таблицы `s_categories`
--

CREATE TABLE `s_categories` (
  `post_id` int(11) NOT NULL,
  `cat_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `s_categories`
--

INSERT INTO `s_categories` (`post_id`, `cat_id`) VALUES
(1, 11),
(1, 12),
(1, 17),
(2, 12),
(2, 16),
(3, 20),
(3, 52),
(4, 24),
(4, 36),
(5, 17),
(5, 22),
(5, 53),
(6, 13),
(6, 19),
(7, 39),
(7, 52),
(8, 34),
(8, 45),
(9, 27);

-- --------------------------------------------------------

--
-- Структура таблицы `s_favorites`
--

CREATE TABLE `s_favorites` (
  `favorites_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `s_favorites`
--

INSERT INTO `s_favorites` (`favorites_id`, `user_id`, `post_id`) VALUES
(1, 1, 8),
(2, 2, 1),
(3, 2, 3),
(4, 2, 5),
(6, 2, 11),
(7, 2, 12);

-- --------------------------------------------------------

--
-- Структура таблицы `s_tags`
--

CREATE TABLE `s_tags` (
  `post_id` int(11) NOT NULL,
  `attr_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `s_tags`
--

INSERT INTO `s_tags` (`post_id`, `attr_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 3),
(2, 5),
(4, 5),
(4, 6),
(4, 8),
(5, 1),
(5, 2),
(5, 3),
(5, 8),
(6, 5),
(7, 5),
(7, 6),
(7, 7),
(7, 8);

-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

CREATE TABLE `tags` (
  `attr_id` int(11) UNSIGNED NOT NULL,
  `attr_name` varchar(255) NOT NULL,
  `attr_icon` varchar(255) DEFAULT NULL COMMENT 'Класс иконки или ссылка'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `tags`
--

INSERT INTO `tags` (`attr_id`, `attr_name`, `attr_icon`) VALUES
(1, 'Wi-Fi', 'fa-wifi'),
(2, 'Парковка', 'fa-parking'),
(3, 'Оплата картой', 'fa-credit-card'),
(4, 'Халяль', 'fa-check'),
(5, 'Каспи QR', 'fa-qrcode'),
(6, 'Kaspi RED', 'fa-tag'),
(7, 'Kaspi Kredit', 'fa-percent'),
(8, 'Halyq POS', 'fa-credit-card'),
(9, 'Летняя терраса', 'fa-umbrella'),
(10, 'Детская зона', 'fa-child'),
(11, 'VIP-кабинки', 'fa-star'),
(12, 'Живая музыка', 'fa-music'),
(13, 'Бизнес-ланч', 'fa-utensils'),
(14, 'Доступ для инвалидов', 'fa-wheelchair'),
(15, 'Кондиционер', 'fa-snowflake'),
(16, 'Намазхана', 'fa-mosque'),
(17, 'Примерочная', 'fa-tshirt'),
(18, 'Гарантия', 'fa-shield-alt'),
(19, 'Выезд на дом', 'fa-truck'),
(20, 'Круглосуточно (24/7)', 'fa-clock');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `login` varchar(255) NOT NULL COMMENT 'логин',
  `password` varchar(255) NOT NULL COMMENT 'Пароль',
  `user_type` enum('admin','user','owner') NOT NULL DEFAULT 'user' COMMENT 'Тип пользователя',
  `user_name` varchar(255) NOT NULL,
  `user_phone` varchar(255) NOT NULL COMMENT 'Номер телефона',
  `registereddate` datetime DEFAULT current_timestamp() COMMENT 'Дата регистраций',
  `lastonline` datetime DEFAULT current_timestamp() COMMENT 'В сети',
  `api_key` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `login`, `password`, `user_type`, `user_name`, `user_phone`, `registereddate`, `lastonline`, `api_key`) VALUES
(1, 'user', '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', 'user', 'Пайдаланушы 1', '+77011112233', '2026-02-06 10:32:26', '2026-02-06 10:32:26', ''),
(2, 'admin', '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', 'admin', 'Админ', '+77011112233', '2026-02-11 10:20:18', '2026-02-14 19:15:26', ''),
(3, 'owner', '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', 'owner', 'Бизнесмен 1', '+77025556677', '2026-02-11 10:20:18', '2026-02-14 19:01:42', ''),
(4, 'user2', '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', 'user', 'Пайдаланушы 2', '+77011112233', '2026-02-06 10:32:26', '2026-02-06 10:32:26', ''),
(6, 'user3', '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', 'user', 'Пайдаланушы 3', '+77011112233', '2026-02-06 10:32:26', '2026-02-06 10:32:26', '');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`),
  ADD UNIQUE KEY `idx_cat_slug` (`cat_slug`),
  ADD KEY `idx_categories_parent_id` (`cat_parent_id`);

--
-- Индексы таблицы `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD UNIQUE KEY `unique_user_review` (`user_id`,`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `title_id` (`post_id`);

--
-- Индексы таблицы `log_post_views`
--
ALTER TABLE `log_post_views`
  ADD PRIMARY KEY (`view_id`),
  ADD KEY `idx_post_date` (`post_id`,`viewed_at`),
  ADD KEY `fk_log_post_views_user` (`user_id`);

--
-- Индексы таблицы `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `idx_post_slug` (`slug`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `idx_post_rating` (`rating_avg`),
  ADD KEY `idx_post_views` (`views`),
  ADD KEY `idx_post_status` (`status`);
ALTER TABLE `post` ADD FULLTEXT KEY `title` (`title`,`psevdonim`);

--
-- Индексы таблицы `s_categories`
--
ALTER TABLE `s_categories`
  ADD PRIMARY KEY (`post_id`,`cat_id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Индексы таблицы `s_favorites`
--
ALTER TABLE `s_favorites`
  ADD PRIMARY KEY (`favorites_id`),
  ADD UNIQUE KEY `user_title` (`user_id`,`post_id`),
  ADD KEY `title_id` (`post_id`);

--
-- Индексы таблицы `s_tags`
--
ALTER TABLE `s_tags`
  ADD PRIMARY KEY (`post_id`,`attr_id`),
  ADD KEY `pa_attr_fk` (`attr_id`);

--
-- Индексы таблицы `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`attr_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT для таблицы `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `log_post_views`
--
ALTER TABLE `log_post_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `post`
--
ALTER TABLE `post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `s_favorites`
--
ALTER TABLE `s_favorites`
  MODIFY `favorites_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `tags`
--
ALTER TABLE `tags`
  MODIFY `attr_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_parent` FOREIGN KEY (`cat_parent_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `log_post_views`
--
ALTER TABLE `log_post_views`
  ADD CONSTRAINT `fk_log_post_views_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_post_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `s_categories`
--
ALTER TABLE `s_categories`
  ADD CONSTRAINT `s_categories_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `s_categories_ibfk_2` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `s_favorites`
--
ALTER TABLE `s_favorites`
  ADD CONSTRAINT `s_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `s_favorites_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `s_tags`
--
ALTER TABLE `s_tags`
  ADD CONSTRAINT `pa_attr_fk` FOREIGN KEY (`attr_id`) REFERENCES `tags` (`attr_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pa_post_fk` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
