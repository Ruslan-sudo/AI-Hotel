<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isBlocked($_SESSION['user_id'], $pdo)) {
    session_destroy();
    redirect('login.php');
}

// Функция для проверки доступности номера
function isRoomAvailable($pdo, $room_id, $check_in, $check_out) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE room_id = ? 
        AND status = 'confirmed'
        AND (
            (check_in <= ? AND check_out > ?) OR
            (check_in < ? AND check_out >= ?) OR
            (check_in >= ? AND check_out <= ?)
        )
    ");
    $stmt->execute([$room_id, $check_out, $check_in, $check_out, $check_in, $check_in, $check_out]);
    $result = $stmt->fetch();
    return $result['count'] == 0;
}

// Получаем все номера
$all_rooms = $pdo->query("SELECT * FROM rooms WHERE is_available = 1")->fetchAll();

// Если выбраны даты, фильтруем доступные номера
$available_rooms = $all_rooms;
$selected_check_in = $_GET['check_in'] ?? '';
$selected_check_out = $_GET['check_out'] ?? '';

if ($selected_check_in && $selected_check_out) {
    $available_rooms = array_filter($all_rooms, function($room) use ($pdo, $selected_check_in, $selected_check_out) {
        return isRoomAvailable($pdo, $room['id'], $selected_check_in, $selected_check_out);
    });
}

// Получаем бронирования пользователя
$stmt = $pdo->prepare("
    SELECT b.*, r.room_number, r.type 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Проверяем успешное бронирование
$show_success = isset($_SESSION['booking_success']);
$booking_data = $_SESSION['booking_data'] ?? null;
unset($_SESSION['booking_success']);
unset($_SESSION['booking_data']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель пользователя</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 100px rgba(0,0,0,0.5);
            z-index: 1000;
            max-width: 500px;
            text-align: center;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 999;
        }
        .close-btn {
            margin-top: 20px;
        }
        .date-filter {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .room-unavailable {
            opacity: 0.5;
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        .room-available {
            background: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <?php if($show_success && $booking_data): ?>
        <div class="overlay" onclick="this.style.display='none';document.querySelector('.success-modal').style.display='none';"></div>
        <div class="success-modal">
            <h2 style="color: #48bb78;">🎉 Поздравляем!</h2>
            <p>Ваше бронирование успешно подтверждено!</p>
            <div class="booking-summary" style="text-align: left;">
                <h3>Содержимое заказа:</h3>
                <p><strong>Номер:</strong> <?php echo $booking_data['room_number']; ?> (<?php echo $booking_data['type']; ?>)</p>
                <p><strong>Дата заезда:</strong> <?php echo $booking_data['check_in']; ?></p>
                <p><strong>Дата выезда:</strong> <?php echo $booking_data['check_out']; ?></p>
                <p><strong>Количество ночей:</strong> <?php echo $booking_data['days']; ?></p>
                <p><strong>Итоговая сумма:</strong> <?php echo $booking_data['total_price']; ?> руб</p>
                <p><strong>Подтверждение отправлено на email:</strong> <?php echo $booking_data['email']; ?></p>
            </div>
            <button class="close-btn" onclick="this.closest('.success-modal').style.display='none';document.querySelector('.overlay').style.display='none';">Закрыть</button>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="nav">
            <h2>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <div class="nav-links">
                <?php if(isAdmin()): ?>
                    <a href="admin.php">Админ-панель</a>
                <?php endif; ?>
                <a href="logout.php">Выйти</a>
            </div>
        </div>

        <h2>Поиск свободных номеров</h2>
        <div class="date-filter">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Дата заезда:</label>
                    <input type="date" name="check_in" value="<?php echo $selected_check_in; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Дата выезда:</label>
                    <input type="date" name="check_out" value="<?php echo $selected_check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                <button type="submit">Показать свободные номера</button>
                <?php if($selected_check_in && $selected_check_out): ?>
                    <a href="dashboard.php" class="btn">Сбросить фильтр</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>Доступные номера</h2>
        <?php if(empty($available_rooms)): ?>
            <div class="alert alert-error">На выбранные даты нет свободных номеров. Пожалуйста, выберите другие даты.</div>
        <?php else: ?>
            <div class="rooms-list">
                <?php foreach($available_rooms as $room): ?>
                    <div class="room-card">
                        <h3>Номер <?php echo $room['room_number']; ?> - <?php echo $room['type']; ?></h3>
                        <p>Цена: <?php echo $room['price_per_night']; ?> руб/ночь</p>
                        <p>Вместимость: <?php echo $room['capacity']; ?> чел.</p>
                        <p><?php echo $room['description']; ?></p>
                        <?php if($selected_check_in && $selected_check_out): ?>
                            <a href="book.php?room_id=<?php echo $room['id']; ?>&check_in=<?php echo $selected_check_in; ?>&check_out=<?php echo $selected_check_out; ?>" class="btn">Забронировать</a>
                        <?php else: ?>
                            <button class="btn" disabled style="background: #ccc;" title="Сначала выберите даты">Выберите даты</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2>Мои бронирования</h2>
        <?php if(count($bookings) > 0): ?>
            <table>
                <thead>
                    <tr><th>Номер</th><th>Тип</th><th>Заезд</th><th>Выезд</th><th>Сумма</th><th>Статус</th></tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr>
                            <td><?php echo $booking['room_number']; ?></td>
                            <td><?php echo $booking['type']; ?></td>
                            <td><?php echo $booking['check_in']; ?></td>
                            <td><?php echo $booking['check_out']; ?></td>
                            <td><?php echo $booking['total_price']; ?> руб</td>
                            <td><?php echo $booking['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>У вас пока нет бронирований.</p>
        <?php endif; ?>
    </div>
</body>
</html>