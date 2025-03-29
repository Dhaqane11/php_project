<!-- Menu items -->
<nav class="mt-4">
    <a href="dashboard.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
        <i class="fas fa-home mr-3"></i>
        <span>Dashboard</span>
    </a>
    
    <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
        <i class="fas fa-chart-line mr-3"></i>
        <span>Statistics</span>
    </a>
    
    <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
        <i class="fas fa-money-bill-wave mr-3"></i>
        <span>Finances</span>
    </a>
    
    <a href="manage_players.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'manage_players.php' || basename($_SERVER['PHP_SELF']) === 'edit_player.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
        <i class="fas fa-users mr-3"></i>
        <span>Manage Players</span>
    </a>
    
    <a href="add_user.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'add_user.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
        <i class="fas fa-user-plus mr-3"></i>
        <span>Add New User</span>
    </a>
    
    <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
        <i class="fas fa-calendar-alt mr-3"></i>
        <span>Match Schedule</span>
    </a>
    
    <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
        <i class="fas fa-running mr-3"></i>
        <span>Training Sessions</span>
    </a>
    
    <a href="profile.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
        <i class="fas fa-cog mr-3"></i>
        <span>Profile Settings</span>
    </a>
    
    <a href="../logout.php" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 hover:text-red-700">
        <i class="fas fa-sign-out-alt mr-3"></i>
        <span>Logout</span>
    </a>
</nav> 