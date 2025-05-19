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
