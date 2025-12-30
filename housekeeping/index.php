<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Housekeeping';

// Handle status update
if (isset($_GET['action']) && isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];
    $action = $_GET['action'];
    
    if ($action === 'complete') {
        // Mark room as available
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt->execute([$room_id]);
        
        // Log completion
        $stmt = $pdo->prepare("INSERT INTO housekeeping_logs (room_id, user_id, status, cleaned_at) VALUES (?, ?, 'completed', NOW())");
        $stmt->execute([$room_id, $currentUser['id']]);
        
        header('Location: /pms_hotel/housekeeping/?msg=completed');
        exit;
    } elseif ($action === 'cleaning') {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'cleaning' WHERE id = ?");
        $stmt->execute([$room_id]);
        
        // Create pending log
        $stmt = $pdo->prepare("INSERT INTO housekeeping_logs (room_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$room_id, $currentUser['id']]);
        
        header('Location: /pms_hotel/housekeeping/?msg=started');
        exit;
    } elseif ($action === 'maintenance') {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
        $stmt->execute([$room_id]);
        header('Location: /pms_hotel/housekeeping/?msg=maintenance');
        exit;
    }
}

// Get rooms by status
$stmt = $pdo->query("SELECT r.*, rt.name as type_name, rt.base_price,
                     (SELECT MAX(cleaned_at) FROM housekeeping_logs WHERE room_id = r.id AND status = 'completed') as last_cleaned
                     FROM rooms r 
                     LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                     ORDER BY 
                        CASE r.status 
                            WHEN 'cleaning' THEN 1 
                            WHEN 'occupied' THEN 2 
                            WHEN 'maintenance' THEN 3 
                            WHEN 'available' THEN 4 
                        END, 
                        r.room_number");
$rooms = $stmt->fetchAll();

// Count by status
$counts = ['cleaning' => 0, 'occupied' => 0, 'available' => 0, 'maintenance' => 0];
foreach ($rooms as $room) {
    $counts[$room['status']]++;
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-broom"></i> Housekeeping</h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'completed' => 'Room marked as clean and available!',
                'started' => 'Room marked for cleaning!',
                'maintenance' => 'Room marked for maintenance!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-broom"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $counts['cleaning']; ?></h3>
                <p>Needs Cleaning</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $counts['occupied']; ?></h3>
                <p>Occupied</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $counts['available']; ?></h3>
                <p>Available</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $counts['maintenance']; ?></h3>
                <p>Maintenance</p>
            </div>
        </div>
    </div>

    <!-- Room Grid -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Rooms</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($rooms as $room): 
                $statusColor = match($room['status']) {
                    'available' => '#22c55e',
                    'occupied' => '#ef4444',
                    'cleaning' => '#f59e0b',
                    'maintenance' => '#64748b',
                    default => '#94a3b8'
                };
                $statusIcon = match($room['status']) {
                    'available' => 'check-circle',
                    'occupied' => 'bed',
                    'cleaning' => 'broom',
                    'maintenance' => 'tools',
                    default => 'door-open'
                };
            ?>
                <div style="background: white; border-radius: var(--radius); border: 2px solid <?php echo $statusColor; ?>; padding: 15px; position: relative;">
                    <div style="position: absolute; top: -10px; right: 10px; background: <?php echo $statusColor; ?>; color: white; padding: 2px 10px; border-radius: 10px; font-size: 0.7rem; text-transform: uppercase;">
                        <?php echo $room['status']; ?>
                    </div>
                    <h3 style="margin-bottom: 5px;">
                        <i class="fas fa-<?php echo $statusIcon; ?>" style="color: <?php echo $statusColor; ?>;"></i>
                        <?php echo htmlspecialchars($room['room_number']); ?>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($room['type_name'] ?? 'N/A'); ?> • Floor <?php echo $room['floor'] ?? '-'; ?>
                    </p>
                    <?php if ($room['last_cleaned']): ?>
                        <p style="font-size: 0.75rem; color: var(--text-muted);">
                            Last cleaned: <?php echo date('d M, H:i', strtotime($room['last_cleaned'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                        <?php if ($room['status'] === 'cleaning'): ?>
                            <a href="/pms_hotel/housekeeping/?action=complete&room_id=<?php echo $room['id']; ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Done
                            </a>
                        <?php elseif ($room['status'] === 'available'): ?>
                            <a href="/pms_hotel/housekeeping/?action=cleaning&room_id=<?php echo $room['id']; ?>" 
                               class="btn btn-warning btn-sm" style="color: white;">
                                <i class="fas fa-broom"></i> Clean
                            </a>
                            <a href="/pms_hotel/housekeeping/?action=maintenance&room_id=<?php echo $room['id']; ?>" 
                               class="btn btn-secondary btn-sm">
                                <i class="fas fa-tools"></i>
                            </a>
                        <?php elseif ($room['status'] === 'maintenance'): ?>
                            <a href="/pms_hotel/housekeeping/?action=complete&room_id=<?php echo $room['id']; ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Fixed
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($rooms) == 0): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">
                No rooms found. <a href="/pms_hotel/rooms/create.php">Add rooms</a> first.
            </p>
        <?php endif; ?>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
