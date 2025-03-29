<?php
// Start session
session_start();

// Check if user is logged in and is a player
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../config/database.php';

// Initialize variables
$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Get player data from database
try {
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT u.name, u.email, u.phone, p.position, p.age 
                           FROM users u 
                           JOIN players p ON u.id = p.user_id 
                           WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        $error = "Player information not found.";
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Update profile if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $error = "Name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $conn->beginTransaction();
            
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user_id]);
            
            // Update players table
            $stmt = $conn->prepare("UPDATE players SET position = ?, age = ? WHERE user_id = ?");
            $stmt->execute([$position, $age, $user_id]);
            
            $conn->commit();
            
            // Update session variable
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $success = "Profile updated successfully!";
            
            // Refresh player data
            $stmt = $conn->prepare("SELECT u.name, u.email, u.phone, p.position, p.age 
                                   FROM users u 
                                   JOIN players p ON u.id = p.user_id 
                                   WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $player = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// Update password if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                $error = "Current password is incorrect.";
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success = "Password updated successfully!";
            }
        } catch(PDOException $e) {
            $error = "Password update failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Soccer Team Management</title>
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
            <div class="flex items-center justify-between h-20 border-b px-4">
                <div class="flex items-center">
                    <i class="fas fa-futbol text-blue-500 text-2xl mr-2"></i>
                    <span class="text-xl font-bold text-gray-800">Soccer Team</span>
                </div>
                <button id="close-sidebar" class="md:hidden text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- User info -->
            <div class="p-4 border-b">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-500"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500">Player</p>
                    </div>
                </div>
            </div>
            
            <!-- Menu items -->
            <nav class="mt-4">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-home mr-3"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="profile.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-700 border-r-4 border-blue-500">
                    <i class="fas fa-user mr-3"></i>
                    <span>My Profile</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-users mr-3"></i>
                    <span>My Team</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    <span>Match Schedule</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span>My Statistics</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-700">
                    <i class="fas fa-running mr-3"></i>
                    <span>Training</span>
                </a>
                
                <a href="logout.php" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 hover:text-red-700">
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
                    <h1 class="text-xl font-semibold text-gray-800">Profile Settings</h1>
                </div>
                <div class="flex items-center">
                    <div class="text-sm text-gray-600 mr-4">
                        <i class="far fa-calendar mr-1"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Profile content -->
            <div class="p-6">
                <?php if($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Profile Information -->
                    <div class="md:col-span-2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-6">Profile Information</h2>
                            
                            <form method="POST" action="">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="name" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-user text-blue-500 mr-2"></i>Full Name
                                        </label>
                                        <input type="text" id="name" name="name" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            value="<?php echo htmlspecialchars($player['name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                                        </label>
                                        <input type="email" id="email" name="email" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            value="<?php echo htmlspecialchars($player['email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label for="phone" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-phone text-blue-500 mr-2"></i>Phone Number
                                        </label>
                                        <input type="tel" id="phone" name="phone" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            value="<?php echo htmlspecialchars($player['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="position" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-futbol text-blue-500 mr-2"></i>Position
                                        </label>
                                        <input type="text" id="position" name="position" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            value="<?php echo htmlspecialchars($player['position'] ?? ''); ?>">
                                    </div>
                                    
                                    <div>
                                        <label for="age" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-birthday-cake text-blue-500 mr-2"></i>Age
                                        </label>
                                        <input type="number" id="age" name="age" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            value="<?php echo htmlspecialchars($player['age'] ?? ''); ?>" min="0" max="100">
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" name="update_profile" 
                                            class="bg-blue-600 text-white font-medium py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center">
                                        <i class="fas fa-save mr-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Password Change -->
                    <div>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-6">Change Password</h2>
                            
                            <form method="POST" action="">
                                <div class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-lock text-blue-500 mr-2"></i>Current Password
                                        </label>
                                        <input type="password" id="current_password" name="current_password" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            required>
                                    </div>
                                    
                                    <div>
                                        <label for="new_password" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-key text-blue-500 mr-2"></i>New Password
                                        </label>
                                        <input type="password" id="new_password" name="new_password" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            required minlength="8">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">
                                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>Confirm New Password
                                        </label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            required minlength="8">
                                    </div>
                                    
                                    <button type="submit" name="update_password" 
                                            class="w-full bg-blue-600 text-white font-medium py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                                        <i class="fas fa-key mr-2"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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