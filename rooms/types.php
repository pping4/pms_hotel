<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Room Types';
$errors = [];
$editMode = false;
$editType = null;

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if any rooms use this type
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Cannot delete: This room type is used by existing rooms.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: /pms_hotel/rooms/types.php?msg=deleted');
        exit;
    }
}

// Handle edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
    $stmt->execute([$id]);
    $editType = $stmt->fetch();
    if ($editType) {
        $editMode = true;
        $_POST = $editType;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $base_price = floatval($_POST['base_price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 2);
    $type_id = intval($_POST['type_id'] ?? 0);

    if (empty($name)) {
        $errors[] = 'Room type name is required';
    }

    if ($base_price <= 0) {
        $errors[] = 'Base price must be greater than 0';
    }

    if (empty($errors)) {
        if ($type_id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE room_types SET name = ?, base_price = ?, description = ?, capacity = ? WHERE id = ?");
            $stmt->execute([$name, $base_price, $description, $capacity, $type_id]);
            header('Location: /pms_hotel/rooms/types.php?msg=updated');
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO room_types (name, base_price, description, capacity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $base_price, $description, $capacity]);
            header('Location: /pms_hotel/rooms/types.php?msg=created');
        }
        exit;
    }
}

// Get all room types
$stmt = $pdo->query("SELECT rt.*, (SELECT COUNT(*) FROM rooms WHERE room_type_id = rt.id) as room_count 
                     FROM room_types rt ORDER BY rt.name");
$roomTypes = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-tags"></i> Room Types</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/rooms/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Rooms
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo implode('<br>', $errors); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'created' => 'Room type created successfully!',
                'updated' => 'Room type updated successfully!',
                'deleted' => 'Room type deleted successfully!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?php echo $editMode ? 'Edit Room Type' : 'Add Room Type'; ?>
                </h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="type_id" value="<?php echo $editType['id'] ?? 0; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Type Name *</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           placeholder="e.g., Standard, Deluxe, Suite" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="base_price">Base Price (฿/night) *</label>
                    <input type="number" step="0.01" class="form-control" id="base_price" name="base_price" 
                           value="<?php echo htmlspecialchars($_POST['base_price'] ?? ''); ?>" 
                           placeholder="e.g., 1500.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="capacity">Capacity (guests)</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" 
                           value="<?php echo htmlspecialchars($_POST['capacity'] ?? 2); ?>" 
                           min="1" max="10">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Room amenities..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> <?php echo $editMode ? 'Update' : 'Add'; ?> Room Type
                </button>
                
                <?php if ($editMode): ?>
                    <a href="/pms_hotel/rooms/types.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Room Types List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Room Types (<?php echo count($roomTypes); ?>)</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price/Night</th>
                            <th>Capacity</th>
                            <th>Rooms</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($roomTypes) > 0): ?>
                            <?php foreach ($roomTypes as $type): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                                    <td><?php echo number_format($type['base_price'], 2); ?> ฿</td>
                                    <td><?php echo $type['capacity']; ?> guests</td>
                                    <td><?php echo $type['room_count']; ?></td>
                                    <td>
                                        <a href="/pms_hotel/rooms/types.php?edit=<?php echo $type['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/pms_hotel/rooms/types.php?delete=<?php echo $type['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           data-confirm="Are you sure you want to delete this room type?">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">
                                    No room types yet. Add one using the form.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
