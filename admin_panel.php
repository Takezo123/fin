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
