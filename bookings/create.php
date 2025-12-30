<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'New Booking';
$errors = [];

// Get guests
$stmt = $pdo->query("SELECT * FROM guests ORDER BY first_name, last_name");
$guests = $stmt->fetchAll();

// Get available rooms
$stmt = $pdo->query("SELECT r.*, rt.name as type_name, rt.base_price 
                     FROM rooms r 
                     LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                     WHERE r.status = 'available' 
                     ORDER BY r.room_number");
$rooms = $stmt->fetchAll();

// Pre-select guest if provided
$preselectedGuest = isset($_GET['guest_id']) ? (int)$_GET['guest_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)($_POST['guest_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in_date = $_POST['check_in_date'] ?? '';
    $check_out_date = $_POST['check_out_date'] ?? '';
    $total_price = floatval($_POST['total_price'] ?? 0);
    $status = $_POST['status'] ?? 'pending';

    // Validation
    if ($guest_id <= 0) {
        $errors[] = 'Please select a guest';
    }
    if ($room_id <= 0) {
        $errors[] = 'Please select a room';
    }
    if (empty($check_in_date)) {
        $errors[] = 'Check-in date is required';
    }
    if (empty($check_out_date)) {
        $errors[] = 'Check-out date is required';
    }
    if ($check_in_date >= $check_out_date) {
        $errors[] = 'Check-out date must be after check-in date';
    }
    if ($total_price <= 0) {
        $errors[] = 'Total price must be greater than 0';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO bookings (guest_id, room_id, check_in_date, check_out_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$guest_id, $room_id, $check_in_date, $check_out_date, $total_price, $status]);
        
        // If confirmed, mark room as occupied
        if ($status === 'confirmed') {
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $stmt->execute([$room_id]);
        }
        
        header('Location: /pms_hotel/bookings/?msg=created');
        exit;
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-plus"></i> New Booking</h1>
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

    <?php if (count($guests) == 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            No guests found. Please <a href="/pms_hotel/guests/create.php">add a guest</a> first.
        </div>
    <?php endif; ?>

    <?php if (count($rooms) == 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            No available rooms. Please <a href="/pms_hotel/rooms/create.php">add rooms</a> first.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Booking Details</h2>
        </div>
        <form method="POST" action="" id="bookingForm">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="guest_id">Guest *</label>
                    <select class="form-control" id="guest_id" name="guest_id" required>
                        <option value="">-- Select Guest --</option>
                        <?php foreach ($guests as $guest): ?>
                            <option value="<?php echo $guest['id']; ?>" 
                                <?php echo (($_POST['guest_id'] ?? $preselectedGuest) == $guest['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                                <?php if ($guest['phone']): ?> (<?php echo $guest['phone']; ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small><a href="/pms_hotel/guests/create.php" target="_blank">+ Add new guest</a></small>
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
                           value="<?php echo htmlspecialchars($_POST['check_in_date'] ?? date('Y-m-d')); ?>" 
                           required onchange="calculatePrice()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="check_out_date">Check-out Date *</label>
                    <input type="date" class="form-control" id="check_out_date" name="check_out_date" 
                           value="<?php echo htmlspecialchars($_POST['check_out_date'] ?? date('Y-m-d', strtotime('+1 day'))); ?>" 
                           required onchange="calculatePrice()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="total_price">Total Price (฿) *</label>
                    <input type="number" step="0.01" class="form-control" id="total_price" name="total_price" 
                           value="<?php echo htmlspecialchars($_POST['total_price'] ?? ''); ?>" 
                           required>
                    <small id="priceHint" style="color: var(--text-muted);"></small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending" <?php echo (($_POST['status'] ?? 'pending') == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo (($_POST['status'] ?? '') == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Booking
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
    const priceHint = document.getElementById('priceHint');
    
    if (room.value && checkIn && checkOut) {
        const price = parseFloat(room.options[room.selectedIndex].dataset.price) || 0;
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (nights > 0) {
            const total = price * nights;
            priceInput.value = total.toFixed(2);
            priceHint.textContent = nights + ' nights × ' + price.toLocaleString() + ' ฿ = ' + total.toLocaleString() + ' ฿';
        }
    }
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', calculatePrice);
</script>

<?php require '../includes/footer.php'; ?>
