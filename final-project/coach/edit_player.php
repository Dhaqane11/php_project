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

// Check if player ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_players.php");
    exit;
}

$player_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    // Validate the input
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Update users table
            $user_sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'player'";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->execute([$name, $email, $phone, $player_id]);
            
            // Check if the player has an entry in the players table
            $check_stmt = $conn->prepare("SELECT user_id FROM players WHERE user_id = ?");
            $check_stmt->execute([$player_id]);
            
            if ($check_stmt->rowCount() > 0) {
                // Update players table
                $player_sql = "UPDATE players SET position = ? WHERE user_id = ?";
                $player_stmt = $conn->prepare($player_sql);
                $player_stmt->execute([$position, $player_id]);
            } else {
                // Create new entry in players table
                $player_sql = "INSERT INTO players (user_id, position) VALUES (?, ?)";
                $player_stmt = $conn->prepare($player_sql);
                $player_stmt->execute([$player_id, $position]);
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Player updated successfully!";
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error_message = "Failed to update player: " . $e->getMessage();
        }
    }
}

// Fetch player data
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, p.position
        FROM users u
        LEFT JOIN players p ON u.id = p.user_id
        WHERE u.id = ? AND u.role = 'player'
    ");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        // Player not found
        header("Location: manage_players.php");
        exit;
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $player = []; // Empty array to avoid undefined variable errors
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player - Soccer Team Management</title>
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
                <a href="dashboard.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="manage_players.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'manage_players.php' || basename($_SERVER['PHP_SELF']) === 'edit_player.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
                    <i class="fas fa-users mr-3"></i>
                    <span>Manage Players</span>
                </a>
                
                <a href="add_user.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'add_user.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
                    <i class="fas fa-user-plus mr-3"></i>
                    <span>Add New User</span>
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
                    <h1 class="text-xl font-semibold text-gray-800">Edit Player</h1>
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
                        <h2 class="text-xl font-semibold text-gray-800">Edit Player</h2>
                        <a href="manage_players.php" class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Players
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
                    
                    <!-- Edit player form -->
                    <form method="POST" action="edit_player.php?id=<?php echo $player_id; ?>" class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($player['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($player['email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($player['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <select id="position" name="position" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select position</option>
                                <option value="Forward" <?php echo (isset($player['position']) && $player['position'] === 'Forward') ? 'selected' : ''; ?>>Forward</option>
                                <option value="Midfielder" <?php echo (isset($player['position']) && $player['position'] === 'Midfielder') ? 'selected' : ''; ?>>Midfielder</option>
                                <option value="Defender" <?php echo (isset($player['position']) && $player['position'] === 'Defender') ? 'selected' : ''; ?>>Defender</option>
                                <option value="Goalkeeper" <?php echo (isset($player['position']) && $player['position'] === 'Goalkeeper') ? 'selected' : ''; ?>>Goalkeeper</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center justify-end pt-4">
                            <a href="manage_players.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Save Changes
                            </button>
                        </div>
                    </form>
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