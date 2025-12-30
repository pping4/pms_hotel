<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Room Management';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /pms_hotel/rooms/?msg=deleted');
    exit;
}

// Get all rooms with room type info
$stmt = $pdo->query("SELECT r.*, rt.name as type_name, rt.base_price 
                     FROM rooms r 
                     LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                     ORDER BY r.room_number");
$rooms = $stmt->fetchAll();

// Get room types for filter/reference
$stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
$roomTypes = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-door-open"></i> Room Management</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/rooms/types.php" class="btn btn-secondary">
                <i class="fas fa-tags"></i> Room Types
            </a>
            <a href="/pms_hotel/rooms/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Room
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'created' => 'Room created successfully!',
                'updated' => 'Room updated successfully!',
                'deleted' => 'Room deleted successfully!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Rooms (<?php echo count($rooms); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Room No.</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Price/Night</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rooms) > 0): ?>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['type_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($room['floor'] ?? '-'); ?></td>
                                <td><?php echo number_format($room['base_price'] ?? 0, 2); ?> ฿</td>
                                <td>
                                    <?php
                                    $badgeClass = match($room['status']) {
                                        'available' => 'badge-success',
                                        'occupied' => 'badge-danger',
                                        'cleaning' => 'badge-warning',
                                        'maintenance' => 'badge-secondary',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/pms_hotel/rooms/edit.php?id=<?php echo $room['id']; ?>" 
                                       class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/pms_hotel/rooms/?delete=<?php echo $room['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       data-confirm="Are you sure you want to delete this room?"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-door-open" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                No rooms yet. <a href="/pms_hotel/rooms/create.php">Add your first room</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
