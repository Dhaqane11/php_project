<?php
// Start session
session_start();

// Check if user is logged in and is a coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../config/database.php';

// Initialize variables
$success = '';
$error = '';
$user_name = $_SESSION['user_name'] ?? 'Coach';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All required fields must be filled out";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strcmp($password, $confirm_password) !== 0) {
        // Use strcmp for more reliable string comparison
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        try {
            $conn = connectDB();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email is already registered";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, created_at) 
                                        VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $hashed_password, $phone, $role]);
                
                $user_id = $conn->lastInsertId();
                
                // Handle role-specific data
                if ($role == 'player') {
                    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
                    $position = trim($_POST['position'] ?? '');
                    
                    $stmt = $conn->prepare("INSERT INTO players (user_id, position, age, created_at) 
                                            VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $position, $age]);
                    
                } elseif ($role == 'coach') {
                    $specialization = trim($_POST['specialization'] ?? '');
                    $experience = !empty($_POST['coach_experience']) ? intval($_POST['coach_experience']) : null;
                    
                    $stmt = $conn->prepare("INSERT INTO coaches (user_id, specialization, years_experience, created_at) 
                                            VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $specialization, $experience]);
                }
                
                // Commit transaction
                $conn->commit();
                
                $success = "New " . ucfirst($role) . " added successfully!";
                
                // Clear form data
                $name = $email = $phone = $position = $specialization = '';
                $age = $experience = null;
            }
        } catch(PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Soccer Team Management</title>
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
            
            <!-- User info with link to profile -->
            <a href="profile.php" class="block p-4 border-b hover:bg-blue-50 transition-colors">
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
                
                <a href="add_user.php" class="flex items-center px-4 py-3 bg-blue-50 text-blue-700 border-r-4 border-blue-500">
                    <i class="fas fa-user-plus mr-3"></i>
                    <span>Add New User</span>
                </a>
                
                <a href="manage_players.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'manage_players.php' || basename($_SERVER['PHP_SELF']) === 'edit_player.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
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
                    <h1 class="text-xl font-semibold text-gray-800">Add New User</h1>
                </div>
                <div class="flex items-center">
                    <a href="profile.php" class="flex items-center mr-4 text-gray-700 hover:text-blue-600">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                            <i class="fas fa-user text-blue-500"></i>
                        </div>
                        <span class="text-sm hidden md:inline"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Add User Form -->
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
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Team Member</h2>
                    <p class="text-gray-600 mb-6">Use this form to add a new player or coach to the system.</p>
                    
                    <form method="POST" action="" class="space-y-6">
                        <!-- Role Selection -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-user-tag text-blue-500 mr-2"></i>User Role
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="cursor-pointer" onclick="selectRole('player')">
                                    <div id="player-card" class="border-2 border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 hover:bg-blue-50 transition">
                                        <i class="fas fa-running text-3xl mb-2 text-gray-600"></i>
                                        <p class="font-medium">Player</p>
                                    </div>
                                </div>
                                <div class="cursor-pointer" onclick="selectRole('coach')">
                                    <div id="coach-card" class="border-2 border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 hover:bg-blue-50 transition">
                                        <i class="fas fa-clipboard text-3xl mb-2 text-gray-600"></i>
                                        <p class="font-medium">Coach</p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="role" name="role" required>
                        </div>
                        
                        <!-- Basic User Information -->
                        <div class="border-t pt-6">
                            <h3 class="font-medium text-lg text-gray-800 mb-4">Basic Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-user text-blue-500 mr-2"></i>Full Name
                                    </label>
                                    <input type="text" id="name" name="name" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           placeholder="John Doe" required>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                                    </label>
                                    <input type="email" id="email" name="email" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           placeholder="email@example.com" required>
                                </div>
                                
                                <div>
                                    <label for="password" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-lock text-blue-500 mr-2"></i>Password
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                               placeholder="••••••••" required>
                                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3 text-gray-500">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-check-circle text-blue-500 mr-2"></i>Confirm Password
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                               placeholder="••••••••" required>
                                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-3 text-gray-500">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-phone text-blue-500 mr-2"></i>Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           placeholder="+1234567890">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Player-specific fields -->
                        <div id="player-fields" class="hidden border-t pt-6">
                            <h3 class="font-medium text-lg text-gray-800 mb-4">Player Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="age" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-birthday-cake text-blue-500 mr-2"></i>Age
                                    </label>
                                    <input type="number" id="age" name="age" min="16" max="50" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="position" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-street-view text-blue-500 mr-2"></i>Position
                                    </label>
                                    <select id="position" name="position" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Position</option>
                                        <option value="goalkeeper">Goalkeeper</option>
                                        <option value="defender">Defender</option>
                                        <option value="midfielder">Midfielder</option>
                                        <option value="forward">Forward</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Coach-specific fields -->
                        <div id="coach-fields" class="hidden border-t pt-6">
                            <h3 class="font-medium text-lg text-gray-800 mb-4">Coach Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="specialization" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>Specialization
                                    </label>
                                    <input type="text" id="specialization" name="specialization" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="e.g. Defense, Goalkeeper Training">
                                </div>
                                
                                <div>
                                    <label for="coach_experience" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-history text-blue-500 mr-2"></i>Years Experience
                                    </label>
                                    <input type="number" id="coach_experience" name="coach_experience" min="0" max="50" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" 
                                    class="bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                                <i class="fas fa-user-plus mr-2"></i>Add New User
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
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const fieldType = field.getAttribute('type');
            const button = field.nextElementSibling;
            
            if (fieldType === 'password') {
                field.setAttribute('type', 'text');
                button.innerHTML = '<i class="far fa-eye-slash"></i>';
            } else {
                field.setAttribute('type', 'password');
                button.innerHTML = '<i class="far fa-eye"></i>';
            }
        }
        
        // Role selection functionality
        function selectRole(role) {
            // Update hidden input
            document.getElementById('role').value = role;
            
            // Update UI to show selected role
            document.getElementById('coach-card').classList.remove('border-blue-500', 'bg-blue-50');
            document.getElementById('player-card').classList.remove('border-blue-500', 'bg-blue-50');
            
            document.getElementById(`${role}-card`).classList.add('border-blue-500', 'bg-blue-50');
            
            // Show appropriate fields
            const playerFields = document.getElementById('player-fields');
            const coachFields = document.getElementById('coach-fields');
            
            playerFields.classList.add('hidden');
            coachFields.classList.add('hidden');
            
            if (role === 'player') {
                playerFields.classList.remove('hidden');
            } else if (role === 'coach') {
                coachFields.classList.remove('hidden');
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