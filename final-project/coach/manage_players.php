<?php
// Start session
session_start();

// Check if user is logged in and is a coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: ../login.php");
    exit;
}

// Database connection
require_once '../db_connect.php';

// Get user name for display
$user_name = $_SESSION['user_name'] ?? 'Coach';

// Check if the players table exists and create it if it doesn't
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'players'");
    if ($checkTable->rowCount() == 0) {
        // Create players table
        $conn->exec("
            CREATE TABLE players (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                position VARCHAR(50) NULL,
                jersey_number INT NULL,
                height DECIMAL(5,2) NULL,
                weight DECIMAL(5,2) NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
} catch (PDOException $e) {
    // Table already exists or other error
}

// Handle player deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // First delete from players table due to foreign key constraint
        $stmt = $conn->prepare("DELETE FROM players WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Then delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'player'");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $success_message = "Player deleted successfully.";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = "Failed to delete player: " . $e->getMessage();
    }
}

// Fetch all players from the database, including position if available
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.created_at, 
               p.position
        FROM users u
        LEFT JOIN players p ON u.id = p.user_id
        WHERE u.role = 'player'
        ORDER BY u.name ASC
    ");
    
    $stmt->execute();
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Players - Soccer Team Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-transition {
            transition: width 0.3s ease, transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar-open {
                transform: translateX(0);
            }
            
            .sidebar-closed {
                transform: translateX(-100%);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen relative">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-white shadow-md sidebar-transition w-64 md:w-64 fixed md:static inset-y-0 left-0 z-30 md:translate-x-0 sidebar-closed md:sidebar-open">
            <!-- Logo -->
            <div class="flex items-center justify-between h-20 border-b border-gray-200 px-4">
                <div class="flex items-center">
                    <i class="fas fa-futbol text-blue-500 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-gray-800">Soccer Team</span>
                </div>
                <button id="close-sidebar" class="md:hidden text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- User info -->
            <a href="profile.php" class="block p-4 border-b border-gray-200 hover:bg-blue-50 transition-colors">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-500"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-xs text-gray-500">Coach</p>
                    </div>
                </div>
            </a>
            
            <!-- Menu items -->
            <nav class="mt-4">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-home mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <a href="add_user.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-user-plus mr-3"></i>
                    <span>Add New User</span>
                </a>
                
              

                <a href="manage_players.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-700 border-r-4 border-blue-500">
                    <i class="fas fa-users mr-3"></i>
                    <span>Manage Players</span>
                </a>
                
                  
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    <span>Match Schedule</span>
                </a>
               
                
                <a href="profile.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Profile Settings</span>
                </a>
                
                <a href="../logout.php" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 hover:text-red-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Backdrop for mobile sidebar -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-gray-800 bg-opacity-50 z-20 hidden" onclick="toggleSidebar()"></div>
        
        <!-- Main content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Top navbar -->
            <div class="bg-white shadow-sm p-4 flex justify-between items-center">
                <div class="flex items-center">
                    <button id="open-sidebar" class="mr-2 md:hidden text-gray-500 focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">Manage Players</h1>
                </div>
                <div class="flex items-center">
                    <a href="profile.php" class="flex items-center mr-4 text-gray-700 hover:text-blue-600">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                            <i class="fas fa-user text-blue-500"></i>
                        </div>
                        <span class="text-sm hidden md:inline"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Page content -->
            <div class="p-6">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Manage Players</h2>
                        <a href="add_user.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md text-sm flex items-center">
                            <i class="fas fa-user-plus mr-2"></i> Add New Player
                        </a>
                    </div>
                    
                    <!-- Display success message -->
                    <?php if (isset($success_message)): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p><?php echo $success_message; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display error message -->
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?php echo $error_message; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Search -->
                    <div class="mb-6">
                        <div class="relative">
                            <input type="text" id="search" placeholder="Search players..." class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 pr-10">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Players table -->
                    <?php if (isset($players) && count($players) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="players-table-body">
                                    <?php foreach ($players as $player): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                                        <i class="fas fa-user text-blue-500"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($player['name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($player['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($player['phone'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if (!empty($player['position'])): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        <?php echo htmlspecialchars($player['position']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                                <?php 
                                                    if (!empty($player['created_at'])) {
                                                        $date = new DateTime($player['created_at']);
                                                        echo $date->format('M d, Y');
                                                    } else {
                                                        echo 'Unknown';
                                                    }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="edit_player.php?id=<?php echo $player['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage_players.php?delete=<?php echo $player['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this player?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-5xl text-gray-300 mb-4">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">No players found</h3>
                            <p class="text-gray-500 mb-4">There are no registered players in the system yet.</p>
                            <a href="add_user.php" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md">
                                <i class="fas fa-user-plus mr-2"></i> Add New Player
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            sidebar.classList.toggle('sidebar-open');
            sidebar.classList.toggle('sidebar-closed');
            
            if (sidebar.classList.contains('sidebar-open')) {
                backdrop.classList.remove('hidden');
            } else {
                backdrop.classList.add('hidden');
            }
        }
        
        // Search functionality
        document.getElementById('search').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#players-table-body tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Add event listeners
        document.getElementById('open-sidebar').addEventListener('click', toggleSidebar);
        document.getElementById('close-sidebar').addEventListener('click', toggleSidebar);
        
        // Close sidebar when window resizes to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                document.getElementById('sidebar-backdrop').classList.add('hidden');
            }
        });
    </script>
</body>
</html> 