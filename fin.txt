<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) $errors[] = 'Введите логин и пароль';

    if (!$errors) {
        
        if ($login === 'admin' && $password === 'restaurant') {
            $_SESSION['is_admin'] = true;
            header('Location: admin_panel.php');
            exit;
        } else {
            $errors[] = 'Неверный логин или пароль администратора';
        }
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Логин администратора</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
</form>
<?php include 'footer.php'; ?>

<?php
session_start();
unset($_SESSION['is_admin']);
header('Location: admin_login.php');
exit;

<?php
session_start();
require_once 'connect.php'; 
include __DIR__ . '/header.php'; 

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];

    $allowed_statuses = [
        'Обрабатывается' => 'Подтверждено',
        'Посещение состоялось' => 'Посещение состоялось',
        'Отменено' => 'Отменено'
    ];

    if (array_key_exists($status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        if ($stmt->execute()) {
            $message = "Статус бронирования обновлен.";
        } else {
            $error = "Ошибка при обновлении статуса: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error = "Недопустимый статус.";
    }
}

$query = "
    SELECT b.*, u.login AS user_login
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.date_time DESC
";
$result = $conn->query($query);
$bookings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$statuses = [
    'Обрабатывается' => 'Подтверждено',
    'Посещение состоялось' => 'Посещение состоялось',
    'Отменено' => 'Отменено'
];
?>

<div class="container mt-5">
    <h2>Панель администратора</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Дата и время</th>
                <th>Гостей</th>
                <th>Телефон</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings): ?>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['id']) ?></td>
                        <td><?= htmlspecialchars($booking['user_login'] ?? 'Неизвестен') ?></td>
                        <td><?= htmlspecialchars($booking['date_time']) ?></td>
                        <td><?= htmlspecialchars($booking['guests']) ?></td>
                        <td><?= htmlspecialchars($booking['phone']) ?></td>
                        <td><?= htmlspecialchars($statuses[$booking['status']] ?? $booking['status']) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <select name="status" class="form-select form-select-sm" required>
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $key === $booking['status'] ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Обновить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">Бронирований нет</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="admin_logout.php" class="btn btn-secondary">Выйти</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_time = $_POST['date_time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');

    if (!$date_time || strtotime($date_time) < time()) $errors[] = "Введите корректную будущую дату и время.";
    if ($guests < 1 || $guests > 10) $errors[] = "Количество гостей должно быть от 1 до 10.";
    if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = "Введите корректный номер телефона.";

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, date_time, guests, phone, status, created_at) VALUES (?, ?, ?, ?, 'Обрабатывается', NOW())");
        $stmt->bind_param("isis", $_SESSION['user_id'], $date_time, $guests, $phone);
        if ($stmt->execute()) $message = "Ваше бронирование отправлено на рассмотрение.";
        else $errors[] = "Ошибка при сохранении бронирования: " . $stmt->error;
        $stmt->close();
    }
}
if ($message) echo '<div class="alert alert-success">'.htmlspecialchars($message).'</div>';
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Дата и время</label>
        <input type="datetime-local" class="form-control" name="date_time" required value="<?=htmlspecialchars($_POST['date_time'] ?? '')?>">
    </div>
    <div class="mb-3"><label class="form-label">Количество гостей</label>
        <input type="number" class="form-control" name="guests" min="1" max="10" required value="<?=htmlspecialchars($_POST['guests'] ?? '1')?>">
    </div>
    <div class="mb-3"><label class="form-label">Телефон</label>
        <input type="tel" class="form-control" name="phone" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
    </div>
    <button type="submit" class="btn btn-primary w-100">Забронировать</button>
</form>
<?php include 'footer.php'; ?>

<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['review_text'])) {
    $booking_id = (int)$_POST['booking_id'];
    $review_text = trim($_POST['review_text']);

    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'Посещение состоялось'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking && $review_text !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row['cnt'] == 0) {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, review_text, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $booking_id, $user_id, $review_text);
            $message = $stmt->execute() ? "Спасибо за ваш отзыв!" : "Ошибка при сохранении отзыва.";
            $stmt->close();
        } else $error = "Вы уже оставили отзыв для этого бронирования.";
    } else $error = "Невозможно оставить отзыв для этого бронирования.";
}

$stmt = $conn->prepare("
    SELECT b.*, r.review_text
    FROM bookings b
    LEFT JOIN reviews r ON b.id = r.booking_id AND r.user_id = ?
    WHERE b.user_id = ?
    ORDER BY b.date_time DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($message) echo '<div class="alert alert-success">'.htmlspecialchars($message).'</div>';
if ($error) echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>';
?>
<h2>Мои бронирования</h2>
<table class="table table-bordered">
    <thead>
    <tr>
        <th>Дата и время</th>
        <th>Гостей</th>
        <th>Телефон</th>
        <th>Статус</th>
        <th>Отзыв</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($bookings as $booking): ?>
        <tr>
            <td><?=htmlspecialchars($booking['date_time'])?></td>
            <td><?=htmlspecialchars($booking['guests'])?></td>
            <td><?=htmlspecialchars($booking['phone'])?></td>
            <td><?=htmlspecialchars($booking['status'])?></td>
            <td>
                <?php if (!empty($booking['review_text'])): ?>
                    <blockquote><?= nl2br(htmlspecialchars($booking['review_text'])) ?></blockquote>
                <?php elseif ($booking['status'] === 'Посещение состоялось'): ?>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <textarea name="review_text" class="form-control" rows="3" required></textarea>
                        <button type="submit" class="btn btn-primary btn-sm mt-1">Отправить</button>
                    </form>
                <?php else: ?>
                    <em>Отзыв доступен после посещения</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include 'footer.php'; ?>

<?php
$conn = new mysqli("localhost", "root", "", "demka");
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ресторан</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<header class="bg-light border-bottom mb-4">
    <nav class="container navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="index.php">Ресторан</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Переключить навигацию">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_panel.php">Панель администратора</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_logout.php">Выйти (Админ)</a></li>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="booking_create.php">Забронировать столик</a></li>
                    <li class="nav-item"><a class="nav-link" href="bookings.php">Мои бронирования</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Выйти (<?=htmlspecialchars($_SESSION['login'] ?? '')?>)</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Вход</a></li>
                    <li class="nav-item"><a class="nav-link" href="reg.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
<div class="container mt-5">

<?php
function show_errors($errors) {
    if ($errors) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>';
        echo '</ul></div>';
    }
}
?>

<?php include 'header.php'; ?>
<div class="text-center mt-5">
    <h1>Добро пожаловать в ресторан!</h1>
    <p>Бронируйте столики онлайн и оставляйте отзывы о посещении.</p>
</div>
<?php include 'footer.php'; ?>

<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (mb_strlen($login) < 4) $errors[] = 'Введите логин';
    if (mb_strlen($password) < 6) $errors[] = 'Введите пароль';

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $login;
            header('Location: bookings.php');
            exit;
        } else {
            $errors[] = "Неправильный логин или пароль";
        }
        $stmt->close();
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Логин</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
</form>
<?php include 'footer.php'; ?>

<?php
session_start();
session_destroy();
header('Location: login.php');
exit;

<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = 'Неверно введен ФИО';
    if (!preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = 'Неверно введен телефон';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Неверно введен E-mail';
    if (mb_strlen($login) < 6) $errors[] = 'Логин должен быть не короче 6 символов';
    if (mb_strlen($password) < 6) $errors[] = 'Пароль должен быть не короче 6 символов';

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO users (fio, phone, email, login, password) VALUES (?, ?, ?, ?, ?)");
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $name, $phone, $email, $login, $hash);
        if ($stmt->execute()) {
            header('Location: bookings.php');
            exit;
        } else {
            $errors[] = "Ошибка регистрации: " . $conn->error;
        }
        $stmt->close();
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">ФИО</label><input type="text" class="form-control" name="fio" required></div>
    <div class="mb-3"><label class="form-label">Телефон</label><input type="text" class="form-control" name="phone" required></div>
    <div class="mb-3"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" required></div>
    <div class="mb-3"><label class="form-label">Логин</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
</form>
<?php include 'footer.php'; ?>

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 19 2025 г., 22:07
-- Версия сервера: 5.7.39
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `demka`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_time` datetime NOT NULL,
  `guests` tinyint(4) NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` set('Обрабатывается','Подтверждено','Посещение состоялось','Отменено') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Обрабатывается',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `date_time`, `guests`, `phone`, `status`, `created_at`) VALUES
(4, 8, '2222-11-11 22:22:00', 1, '+7(999)-999-00-01', 'Посещение состоялось', '2025-05-19 18:39:02'),
(5, 8, '2222-12-12 23:23:00', 1, '+7(999)-999-00-00', 'Посещение состоялось', '2025-05-19 18:56:27'),
(6, 8, '2222-12-12 22:22:00', 2, '+7(999)-999-00-01', 'Обрабатывается', '2025-05-19 18:57:26'),
(7, 8, '2222-02-22 22:02:00', 1, '+7(999)-999-00-01', 'Отменено', '2025-05-19 19:01:04');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `user_id`, `review_text`, `created_at`) VALUES
(2, 4, 8, 'фыв', '2025-05-19 18:43:23'),
(3, 5, 8, 'bb', '2025-05-19 18:57:12');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `fio`, `phone`, `email`, `login`, `password`) VALUES
(6, 'билли боб бос', '+7(999)-999-00-00', 'mail@mail.ru', 'test123', '$2y$10$trAl8cXMPmbiOQvjNO/AweryUmUywJfuCw3udoafmc.NM3tN8PewC'),
(7, 'билли боб бос', '+7(999)-999-00-00', 'mail@mail.ru', 'test123', '$2y$10$V8MdQolUzkeDJ4X.LJkWq.1icUH60nJLz89XxxlzrBJtJUtEBsQg2'),
(8, 'фывфывфыв', '+7(999)-999-00-01', 'bobs@mail.ru', 'test444', '$2y$10$tpOBQfhzTAeojv4Ory22n.OZlupSNO4ib.is8f0vtjrvNwDjnf8b6');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`booking_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
