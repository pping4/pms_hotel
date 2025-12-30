<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Edit Room';
$errors = [];

// Get room ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/rooms/');
    exit;
}

// Fetch room
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: /pms_hotel/rooms/');
    exit;
}

// Get room types
$stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
$roomTypes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type_id = $_POST['room_type_id'] ?? '';
    $floor = trim($_POST['floor'] ?? '');
    $status = $_POST['status'] ?? 'available';

    // Validation
    if (empty($room_number)) {
        $errors[] = 'Room number is required';
    } else {
        // Check duplicate room number (exclude current)
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ?");
        $stmt->execute([$room_number, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Room number already exists';
        }
    }

    if (empty($room_type_id)) {
        $errors[] = 'Please select a room type';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type_id = ?, floor = ?, status = ? WHERE id = ?");
        $stmt->execute([$room_number, $room_type_id, $floor, $status, $id]);
        header('Location: /pms_hotel/rooms/?msg=updated');
        exit;
    }
} else {
    // Pre-fill form
    $_POST = $room;
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-edit"></i> Edit Room: <?php echo htmlspecialchars($room['room_number']); ?></h1>
        <div class="topbar-right">
            <a href="/pms_hotel/rooms/" class="btn btn-secondary">
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
            <h2 class="card-title">Room Information</h2>
        </div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="room_number">Room Number *</label>
                    <input type="text" class="form-control" id="room_number" name="room_number" 
                           value="<?php echo htmlspecialchars($_POST['room_number'] ?? ''); ?>" 
                           placeholder="e.g., 101, A101" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="room_type_id">Room Type *</label>
                    <select class="form-control" id="room_type_id" name="room_type_id" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                <?php echo (($_POST['room_type_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?> 
                                (<?php echo number_format($type['base_price'], 2); ?> ฿)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="floor">Floor</label>
                    <input type="text" class="form-control" id="floor" name="floor" 
                           value="<?php echo htmlspecialchars($_POST['floor'] ?? ''); ?>" 
                           placeholder="e.g., 1, 2, Ground">
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="available" <?php echo (($_POST['status'] ?? '') == 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo (($_POST['status'] ?? '') == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                        <option value="cleaning" <?php echo (($_POST['status'] ?? '') == 'cleaning') ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="maintenance" <?php echo (($_POST['status'] ?? '') == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Room
                </button>
                <a href="/pms_hotel/rooms/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
