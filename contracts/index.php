<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Contract Management';

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'terminate') {
        $stmt = $pdo->prepare("UPDATE contracts SET status = 'terminated' WHERE id = ?");
        $stmt->execute([$id]);
        // Free up the room
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = (SELECT room_id FROM contracts WHERE id = ?)");
        $stmt->execute([$id]);
    }
    header('Location: /pms_hotel/contracts/?msg=' . $action);
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /pms_hotel/contracts/?msg=deleted');
    exit;
}

// Filter by status
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if (!empty($statusFilter)) {
    $where = "WHERE c.status = ?";
    $params = [$statusFilter];
}

// Get all contracts
$sql = "SELECT c.*, g.first_name, g.last_name, r.room_number 
        FROM contracts c 
        LEFT JOIN guests g ON c.guest_id = g.id 
        LEFT JOIN rooms r ON c.room_id = r.id 
        $where
        ORDER BY c.start_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contracts = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-file-contract"></i> Contract Management</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/contracts/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Contract
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'created' => 'Contract created successfully!',
                'updated' => 'Contract updated successfully!',
                'deleted' => 'Contract deleted successfully!',
                'terminate' => 'Contract terminated!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="/pms_hotel/contracts/" 
               class="btn <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                All
            </a>
            <a href="/pms_hotel/contracts/?status=active" 
               class="btn <?php echo $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Active
            </a>
            <a href="/pms_hotel/contracts/?status=expired" 
               class="btn <?php echo $statusFilter === 'expired' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Expired
            </a>
            <a href="/pms_hotel/contracts/?status=terminated" 
               class="btn <?php echo $statusFilter === 'terminated' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Terminated
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Contracts (<?php echo count($contracts); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contracts) > 0): ?>
                        <?php foreach ($contracts as $contract): ?>
                            <tr>
                                <td>#<?php echo $contract['id']; ?></td>
                                <td>
                                    <?php if ($contract['guest_id']): ?>
                                        <a href="/pms_hotel/guests/view.php?id=<?php echo $contract['guest_id']; ?>">
                                            <?php echo htmlspecialchars($contract['first_name'] . ' ' . $contract['last_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($contract['room_number'] ?? 'N/A'); ?></td>
                                <td><?php echo $contract['start_date']; ?></td>
                                <td><?php echo $contract['end_date']; ?></td>
                                <td><?php echo number_format($contract['total_amount'], 2); ?> ฿</td>
                                <td>
                                    <?php
                                    $badgeClass = match($contract['status']) {
                                        'active' => 'badge-success',
                                        'expired' => 'badge-secondary',
                                        'terminated' => 'badge-danger',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($contract['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/pms_hotel/contracts/view.php?id=<?php echo $contract['id']; ?>" 
                                       class="btn btn-secondary btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($contract['status'] === 'active'): ?>
                                        <a href="/pms_hotel/contracts/edit.php?id=<?php echo $contract['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/pms_hotel/contracts/?action=terminate&id=<?php echo $contract['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           data-confirm="Are you sure you want to terminate this contract?"
                                           title="Terminate">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-file-contract" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                No contracts found. <a href="/pms_hotel/contracts/create.php">Create your first contract</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
