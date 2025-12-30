<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'New Contract';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)($_POST['guest_id'] ?? 0) ?: null;
    $room_id = (int)($_POST['room_id'] ?? 0) ?: null;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($start_date)) {
        $errors[] = 'Start date is required';
    }
    if (empty($end_date)) {
        $errors[] = 'End date is required';
    }
    if ($start_date >= $end_date) {
        $errors[] = 'End date must be after start date';
    }
    if ($total_amount <= 0) {
        $errors[] = 'Total amount must be greater than 0';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO contracts (guest_id, room_id, start_date, end_date, total_amount, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$guest_id, $room_id, $start_date, $end_date, $total_amount, $status, $description]);
        
        // If active and has room, mark room as occupied
        if ($status === 'active' && $room_id) {
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $stmt->execute([$room_id]);
        }
        
        header('Location: /pms_hotel/contracts/?msg=created');
        exit;
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-plus"></i> New Contract</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/contracts/" class="btn btn-secondary">
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
            <h2 class="card-title">Contract Details</h2>
        </div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="guest_id">Guest (Optional)</label>
                    <select class="form-control" id="guest_id" name="guest_id">
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
                    <label class="form-label" for="room_id">Room (Optional)</label>
                    <select class="form-control" id="room_id" name="room_id" onchange="calculateMonthlyRate()">
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
                    <label class="form-label" for="start_date">Start Date *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" 
                           required onchange="calculateMonthlyRate()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="end_date">End Date *</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'))); ?>" 
                           required onchange="calculateMonthlyRate()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="total_amount">Total Amount (฿) *</label>
                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" 
                           value="<?php echo htmlspecialchars($_POST['total_amount'] ?? ''); ?>" required>
                    <small id="rateHint" style="color: var(--text-muted);"></small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="active" <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo (($_POST['status'] ?? '') == 'expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="terminated" <?php echo (($_POST['status'] ?? '') == 'terminated') ? 'selected' : ''; ?>>Terminated</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="description">Description / Terms</label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Enter contract terms, special conditions, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Contract
                </button>
                <a href="/pms_hotel/contracts/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
function calculateMonthlyRate() {
    const room = document.getElementById('room_id');
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const amountInput = document.getElementById('total_amount');
    const rateHint = document.getElementById('rateHint');
    
    if (room.value && startDate && endDate) {
        const dailyRate = parseFloat(room.options[room.selectedIndex].dataset.price) || 0;
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (days > 0) {
            // Apply 20% discount for long-term contracts
            const discountedRate = dailyRate * 0.8;
            const total = discountedRate * days;
            amountInput.value = total.toFixed(2);
            rateHint.textContent = days + ' days × ' + discountedRate.toLocaleString() + ' ฿ (20% discount) = ' + total.toLocaleString() + ' ฿';
        }
    }
}

document.addEventListener('DOMContentLoaded', calculateMonthlyRate);
</script>

<?php require '../includes/footer.php'; ?>
