<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Edit Contract';
$errors = [];

// Get contract ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/contracts/');
    exit;
}

// Fetch contract
$stmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
$stmt->execute([$id]);
$contract = $stmt->fetch();

if (!$contract) {
    header('Location: /pms_hotel/contracts/');
    exit;
}

// Only allow editing active contracts
if ($contract['status'] !== 'active') {
    header('Location: /pms_hotel/contracts/view.php?id=' . $id);
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
$stmt->execute([$contract['room_id']]);
$rooms = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)($_POST['guest_id'] ?? 0) ?: null;
    $room_id = (int)($_POST['room_id'] ?? 0) ?: null;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($start_date)) $errors[] = 'Start date is required';
    if (empty($end_date)) $errors[] = 'End date is required';
    if ($start_date >= $end_date) $errors[] = 'End date must be after start date';
    if ($total_amount <= 0) $errors[] = 'Total amount must be greater than 0';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE contracts SET guest_id = ?, room_id = ?, start_date = ?, end_date = ?, total_amount = ?, description = ? WHERE id = ?");
        $stmt->execute([$guest_id, $room_id, $start_date, $end_date, $total_amount, $description, $id]);
        header('Location: /pms_hotel/contracts/?msg=updated');
        exit;
    }
} else {
    $_POST = $contract;
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-edit"></i> Edit Contract #<?php echo $id; ?></h1>
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
                    <label class="form-label" for="guest_id">Guest</label>
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
                    <label class="form-label" for="room_id">Room</label>
                    <select class="form-control" id="room_id" name="room_id">
                        <option value="">-- Select Room --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" 
                                <?php echo (($_POST['room_id'] ?? '') == $room['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['room_number']); ?> - 
                                <?php echo htmlspecialchars($room['type_name'] ?? 'N/A'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date *</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="end_date">End Date *</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="total_amount">Total Amount (฿) *</label>
                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" 
                           value="<?php echo htmlspecialchars($_POST['total_amount'] ?? ''); ?>" required>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="description">Description / Terms</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Contract
                </button>
                <a href="/pms_hotel/contracts/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
