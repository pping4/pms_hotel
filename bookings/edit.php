<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Edit Booking';
$errors = [];

// Get booking ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/bookings/');
    exit;
}

// Fetch booking
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: /pms_hotel/bookings/');
    exit;
}

// Only allow editing pending/confirmed bookings
if (!in_array($booking['status'], ['pending', 'confirmed'])) {
    header('Location: /pms_hotel/bookings/view.php?id=' . $id);
    exit;
}

// Get guests
$stmt = $pdo->query("SELECT * FROM guests ORDER BY first_name, last_name");
$guests = $stmt->fetchAll();

// Get rooms (include current room + available)
$stmt = $pdo->prepare("SELECT r.*, rt.name as type_name, rt.base_price 
                       FROM rooms r 
                       LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                       WHERE r.status = 'available' OR r.id = ?
                       ORDER BY r.room_number");
$stmt->execute([$booking['room_id']]);
$rooms = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)($_POST['guest_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in_date = $_POST['check_in_date'] ?? '';
    $check_out_date = $_POST['check_out_date'] ?? '';
    $total_price = floatval($_POST['total_price'] ?? 0);
    $status = $_POST['status'] ?? 'pending';

    // Validation
    if ($guest_id <= 0) $errors[] = 'Please select a guest';
    if ($room_id <= 0) $errors[] = 'Please select a room';
    if (empty($check_in_date)) $errors[] = 'Check-in date is required';
    if (empty($check_out_date)) $errors[] = 'Check-out date is required';
    if ($check_in_date >= $check_out_date) $errors[] = 'Check-out date must be after check-in date';
    if ($total_price <= 0) $errors[] = 'Total price must be greater than 0';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE bookings SET guest_id = ?, room_id = ?, check_in_date = ?, check_out_date = ?, total_price = ?, status = ? WHERE id = ?");
        $stmt->execute([$guest_id, $room_id, $check_in_date, $check_out_date, $total_price, $status, $id]);
        header('Location: /pms_hotel/bookings/?msg=updated');
        exit;
    }
} else {
    $_POST = $booking;
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-edit"></i> Edit Booking #<?php echo $id; ?></h1>
        <div class="topbar-right">
            <a href="/pms_hotel/bookings/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo implode('<br>', $errors); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Booking Details</h2>
        </div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="guest_id">Guest *</label>
                    <select class="form-control" id="guest_id" name="guest_id" required>
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($guests as $guest): ?>
                            <option value="<?php echo $guest['id']; ?>" 
                                <?php echo (($_POST['guest_id'] ?? '') == $guest['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="room_id">Room *</label>
                    <select class="form-control" id="room_id" name="room_id" required onchange="calculatePrice()">
                        <option value="" data-price="0">-- Select Room --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" 
                                    data-price="<?php echo $room['base_price']; ?>"
                                <?php echo (($_POST['room_id'] ?? '') == $room['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['room_number']); ?> - 
                                <?php echo htmlspecialchars($room['type_name'] ?? 'N/A'); ?>
                                (<?php echo number_format($room['base_price'], 2); ?> ฿/night)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="check_in_date">Check-in Date *</label>
                    <input type="date" class="form-control" id="check_in_date" name="check_in_date" 
                           value="<?php echo htmlspecialchars($_POST['check_in_date'] ?? ''); ?>" 
                           required onchange="calculatePrice()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="check_out_date">Check-out Date *</label>
                    <input type="date" class="form-control" id="check_out_date" name="check_out_date" 
                           value="<?php echo htmlspecialchars($_POST['check_out_date'] ?? ''); ?>" 
                           required onchange="calculatePrice()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="total_price">Total Price (฿) *</label>
                    <input type="number" step="0.01" class="form-control" id="total_price" name="total_price" 
                           value="<?php echo htmlspecialchars($_POST['total_price'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending" <?php echo (($_POST['status'] ?? '') == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo (($_POST['status'] ?? '') == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Booking
                </button>
                <a href="/pms_hotel/bookings/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
function calculatePrice() {
    const room = document.getElementById('room_id');
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    const priceInput = document.getElementById('total_price');
    
    if (room.value && checkIn && checkOut) {
        const price = parseFloat(room.options[room.selectedIndex].dataset.price) || 0;
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (nights > 0) {
            priceInput.value = (price * nights).toFixed(2);
        }
    }
}
</script>

<?php require '../includes/footer.php'; ?>
