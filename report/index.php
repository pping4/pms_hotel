<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Reports';

// Date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Revenue stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_bookings,
    SUM(total_price) as total_revenue,
    AVG(total_price) as avg_revenue
    FROM bookings 
    WHERE status IN ('checked_in', 'checked_out') 
    AND check_in_date BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$bookingStats = $stmt->fetch();

// Contract revenue
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_contracts,
    SUM(total_amount) as total_revenue
    FROM contracts 
    WHERE status = 'active' 
    AND start_date <= ? AND end_date >= ?");
$stmt->execute([$endDate, $startDate]);
$contractStats = $stmt->fetch();

// Room occupancy
$stmt = $pdo->query("SELECT 
    status, COUNT(*) as count 
    FROM rooms 
    GROUP BY status");
$roomStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$totalRooms = array_sum($roomStats);
$occupiedRooms = ($roomStats['occupied'] ?? 0) + ($roomStats['cleaning'] ?? 0);
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

// Daily revenue for chart
$stmt = $pdo->prepare("SELECT DATE(check_in_date) as date, SUM(total_price) as revenue 
                       FROM bookings 
                       WHERE status IN ('checked_in', 'checked_out') 
                       AND check_in_date BETWEEN ? AND ? 
                       GROUP BY DATE(check_in_date) 
                       ORDER BY date");
$stmt->execute([$startDate, $endDate]);
$dailyRevenue = $stmt->fetchAll();

// Recent transactions
$stmt = $pdo->query("SELECT t.*, b.id as booking_id, g.first_name, g.last_name 
                     FROM transactions t 
                     LEFT JOIN bookings b ON t.booking_id = b.id 
                     LEFT JOIN guests g ON b.guest_id = g.id 
                     ORDER BY t.transaction_date DESC 
                     LIMIT 10");
$recentTransactions = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-chart-bar"></i> Reports</h1>
        <div class="topbar-right">
            <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>" style="width: auto;">
                <span>to</span>
                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>" style="width: auto;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($bookingStats['total_revenue'] ?? 0, 0); ?> ฿</h3>
                <p>Booking Revenue</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-file-contract"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($contractStats['total_revenue'] ?? 0, 0); ?> ฿</h3>
                <p>Contract Revenue</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $occupancyRate; ?>%</h3>
                <p>Occupancy Rate</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $bookingStats['total_bookings'] ?? 0; ?></h3>
                <p>Total Bookings</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Daily Revenue Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daily Revenue</h2>
            </div>
            <?php if (count($dailyRevenue) > 0): ?>
                <div style="height: 250px; display: flex; align-items: flex-end; gap: 5px; padding: 20px 0;">
                    <?php 
                    $maxRevenue = max(array_column($dailyRevenue, 'revenue')) ?: 1;
                    foreach ($dailyRevenue as $day): 
                        $height = ($day['revenue'] / $maxRevenue) * 200;
                    ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                            <div style="width: 100%; max-width: 40px; height: <?php echo $height; ?>px; background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%); border-radius: 4px 4px 0 0;" 
                                 title="<?php echo number_format($day['revenue'], 0); ?> ฿"></div>
                            <small style="color: var(--text-muted); font-size: 0.65rem; margin-top: 5px;">
                                <?php echo date('d', strtotime($day['date'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); padding: 40px;">
                    No revenue data for this period
                </p>
            <?php endif; ?>
        </div>

        <!-- Room Status -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Room Status</h2>
            </div>
            <div style="padding: 20px;">
                <?php 
                $statuses = [
                    'available' => ['label' => 'Available', 'color' => '#22c55e'],
                    'occupied' => ['label' => 'Occupied', 'color' => '#ef4444'],
                    'cleaning' => ['label' => 'Cleaning', 'color' => '#f59e0b'],
                    'maintenance' => ['label' => 'Maintenance', 'color' => '#64748b'],
                ];
                foreach ($statuses as $status => $info): 
                    $count = $roomStats[$status] ?? 0;
                    $percent = $totalRooms > 0 ? ($count / $totalRooms) * 100 : 0;
                ?>
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo $info['label']; ?></span>
                            <span><?php echo $count; ?> rooms</span>
                        </div>
                        <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $percent; ?>%; background: <?php echo $info['color']; ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Transactions</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Guest</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentTransactions) > 0): ?>
                        <?php foreach ($recentTransactions as $txn): ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($txn['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars(($txn['first_name'] ?? '') . ' ' . ($txn['last_name'] ?? '')); ?></td>
                                <td>
                                    <span class="badge <?php echo $txn['type'] === 'refund' ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo ucfirst($txn['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($txn['payment_method'] ?? '-'); ?></td>
                                <td><?php echo number_format($txn['amount'], 2); ?> ฿</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted);">
                                No transactions yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
