<?php
// Determine current page for menu active state
$currentPage = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-hotel"></i> Hotel PMS</h2>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="/pms_hotel/dashboard/" class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/pms_hotel/calendar/" class="<?php echo $currentPage == 'calendar' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
        </li>
        <li>
            <a href="/pms_hotel/rooms/" class="<?php echo $currentPage == 'rooms' ? 'active' : ''; ?>">
                <i class="fas fa-door-open"></i> Rooms
            </a>
        </li>
        <li>
            <a href="/pms_hotel/bookings/" class="<?php echo $currentPage == 'bookings' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Bookings
            </a>
        </li>
        <li>
            <a href="/pms_hotel/contracts/" class="<?php echo $currentPage == 'contracts' ? 'active' : ''; ?>">
                <i class="fas fa-file-contract"></i> Contracts
            </a>
        </li>
        <li>
            <a href="/pms_hotel/guests/" class="<?php echo $currentPage == 'guests' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Guests
            </a>
        </li>
        <li>
            <a href="/pms_hotel/housekeeping/" class="<?php echo $currentPage == 'housekeeping' ? 'active' : ''; ?>">
                <i class="fas fa-broom"></i> Housekeeping
            </a>
        </li>
        <li>
            <a href="/pms_hotel/report/" class="<?php echo $currentPage == 'report' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <li>
            <a href="/pms_hotel/auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</aside>
