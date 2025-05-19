<?php
session_start();
require_once 'connect.php'; 
include __DIR__ . '/header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['review_text'])) {
    $booking_id = (int)$_POST['booking_id'];
    $review_text = trim($_POST['review_text']);

    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'Посещение состоялось'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res->fetch_assoc();
    $stmt->close();

    if ($booking && $review_text !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row['cnt'] == 0) {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, review_text, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $booking_id, $user_id, $review_text);
            if ($stmt->execute()) {
                $message = "Спасибо за ваш отзыв!";
            } else {
                $error = "Ошибка при сохранении отзыва.";
            }
            $stmt->close();
        } else {
            $error = "Вы уже оставили отзыв для этого бронирования.";
        }
    } else {
        $error = "Невозможно оставить отзыв для этого бронирования.";
    }
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
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Мои бронирования</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Мои бронирования</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

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
                            <textarea name="review_text" class="form-control" rows="3" placeholder="Оставьте отзыв" required></textarea>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
