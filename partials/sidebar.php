<div class="sidebar position-fixed" style="width: 250px;">
    <div class="p-4">
        <h4 class="text-white mb-4">
            <i class="fas fa-dumbbell"></i> EnduraCore
        </h4>
        
        <nav class="nav flex-column">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $role = $_SESSION['role'];
            
            if ($role == 'ADMIN') {
            ?>
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link <?php echo ($current_page == 'members.php') ? 'active' : ''; ?>" href="members.php">
                    <i class="fas fa-users"></i> Members
                </a>
                <a class="nav-link <?php echo ($current_page == 'trainers.php') ? 'active' : ''; ?>" href="trainers.php">
                    <i class="fas fa-user-tie"></i> Trainers
                </a>
                <a class="nav-link <?php echo ($current_page == 'memberships.php') ? 'active' : ''; ?>" href="memberships.php">
                    <i class="fas fa-id-card"></i> Memberships
                </a>
                <a class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a class="nav-link <?php echo ($current_page == 'equipment.php') ? 'active' : ''; ?>" href="equipment.php">
                    <i class="fas fa-tools"></i> Equipment
                </a>
                <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            <?php
            } elseif ($role == 'TRAINER') {
            ?>
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link <?php echo ($current_page == 'my_members.php') ? 'active' : ''; ?>" href="my_members.php">
                    <i class="fas fa-users"></i> My Members
                </a>
                <a class="nav-link <?php echo ($current_page == 'workout_plans.php') ? 'active' : ''; ?>" href="workout_plans.php">
                    <i class="fas fa-dumbbell"></i> Workout Plans
                </a>
                <a class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a class="nav-link <?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>" href="schedule.php">
                    <i class="fas fa-calendar-alt"></i> Schedule
                </a>
            <?php
            } else {
            ?>
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link <?php echo ($current_page == 'my_workouts.php') ? 'active' : ''; ?>" href="my_workouts.php">
                    <i class="fas fa-dumbbell"></i> My Workouts
                </a>
                <a class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-calendar-check"></i> My Attendance
                </a>
                <a class="nav-link <?php echo ($current_page == 'membership.php') ? 'active' : ''; ?>" href="membership.php">
                    <i class="fas fa-id-card"></i> Membership
                </a>
                <a class="nav-link <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php
            }
            ?>
        </nav>
    </div>
</div>