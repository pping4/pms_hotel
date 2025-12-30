<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Guest Management';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check for existing bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: /pms_hotel/guests/?error=has_bookings');
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM guests WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /pms_hotel/guests/?msg=deleted');
    exit;
}

// Get all guests
$sql = "SELECT g.*, 
        (SELECT COUNT(*) FROM bookings WHERE guest_id = g.id) as booking_count,
        (SELECT COUNT(*) FROM contracts WHERE guest_id = g.id) as contract_count
        FROM guests g ORDER BY g.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$guests = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-users"></i> Guest Management</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/guests/create.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Guest
            </a>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'has_bookings'): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            Cannot delete guest with existing bookings or contracts.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'created' => 'Guest created successfully!',
                'updated' => 'Guest updated successfully!',
                'deleted' => 'Guest deleted successfully!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Guests (<?php echo count($guests); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="no-filter">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>ID/Passport</th>
                        <th>Bookings</th>
                        <th class="no-filter">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($guests) > 0): ?>
                        <?php foreach ($guests as $guest): ?>
                            <tr>
                                <td>#<?php echo $guest['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($guest['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($guest['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($guest['id_card_passport'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $guest['booking_count']; ?> bookings</span>
                                    <?php if ($guest['contract_count'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $guest['contract_count']; ?> contracts</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/pms_hotel/guests/view.php?id=<?php echo $guest['id']; ?>" 
                                       class="btn btn-secondary btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/pms_hotel/guests/edit.php?id=<?php echo $guest['id']; ?>" 
                                       class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/pms_hotel/guests/?delete=<?php echo $guest['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       data-confirm="Are you sure you want to delete this guest?"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                <?php if ($search): ?>
                                    No guests found matching "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    No guests yet. <a href="/pms_hotel/guests/create.php">Add your first guest</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
