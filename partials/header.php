<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <button class="btn btn-link d-md-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-link text-dark dropdown-toggle text-decoration-none" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg me-2"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <!-- <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li> -->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.querySelector('.sidebar')?.classList.toggle('show');
});
</script>