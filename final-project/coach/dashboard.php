<?php
// Start session
session_start();

// Check if user is logged in and is a coach
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: ../login.php");
    exit;
}

// Get user name for display
$user_name = $_SESSION['user_name'] ?? 'Coach';

// Database connection
$conn = new PDO("mysql:host=localhost;dbname=soccer_team_management", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Dashboard - Soccer Team Management</title>
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
            
            <!-- User info with link to profile -->
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

                <a href="add_user.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) === 'add_user.php' ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-500' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700'; ?>">
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
                    <h1 class="text-xl font-semibold text-gray-800">Coach Dashboard</h1>
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
            
            <!-- Dashboard content -->
            <div class="p-6">
                <!-- Welcome message -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p class="text-gray-600">Here's what's happening with your team today.</p>
                </div>
                
                <!-- Stats cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 mr-4">
                                <i class="fas fa-users text-blue-500"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Players</p>
                                <?php
                                // Count players in database
                                try {
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'player'");
                                    $stmt->execute();
                                    $result = $stmt->fetch();
                                    $playerCount = $result['count'];
                                } catch(PDOException $e) {
                                    $playerCount = 0;
                                }
                                ?>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $playerCount; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 mr-4">
                                <i class="fas fa-calendar-check text-green-500"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Matches Won</p>
                                <p class="text-2xl font-bold text-gray-800">12</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 mr-4">
                                <i class="fas fa-trophy text-yellow-500"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Goals Scored</p>
                                <p class="text-2xl font-bold text-gray-800">32</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 mr-4">
                                <i class="fas fa-user-shield text-red-500"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Goals Conceded</p>
                                <p class="text-2xl font-bold text-gray-800">8</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Players -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recently Added Players</h3>
                        <a href="manage_players.php" class="text-blue-500 text-sm hover:underline">View all</a>
                    </div>
                    
                    <?php
                    // Fetch recent players
                    try {
                        $stmt = $conn->prepare("
                            SELECT u.id, u.name, u.email, u.created_at, p.position
                            FROM users u
                            LEFT JOIN players p ON u.id = p.user_id
                            WHERE u.role = 'player'
                            ORDER BY u.created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute();
                        $recentPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {
                        $recentPlayers = [];
                    }
                    ?>
                    
                    <?php if (!empty($recentPlayers)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recentPlayers as $player): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($player['name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($player['email']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($player['position']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($player['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upcoming matches & Team management -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Upcoming matches -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Upcoming Matches</h3>
                            <a href="#" class="text-blue-500 text-sm hover:underline">View all</a>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="border-l-4 border-blue-500 p-3 bg-blue-50">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Our Team vs. Eagles FC</p>
                                        <p class="text-sm text-gray-500">Local Stadium</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-blue-600">Tomorrow</p>
                                        <p class="text-sm text-gray-500">15:00</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-l-4 border-green-500 p-3 bg-green-50">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Falcons SC vs. Our Team</p>
                                        <p class="text-sm text-gray-500">City Stadium</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-green-600">Sat, May 12</p>
                                        <p class="text-sm text-gray-500">16:30</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-l-4 border-purple-500 p-3 bg-purple-50">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-800">Our Team vs. United SC</p>
                                        <p class="text-sm text-gray-500">Local Stadium</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-purple-600">Wed, May 16</p>
                                        <p class="text-sm text-gray-500">19:00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Team management quick access -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Team Management</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <a href="add_user.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-blue-50 transition-colors">
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-user-plus text-blue-500"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Add Player</span>
                            </a>
                            
                            <a href="manage_players.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-blue-50 transition-colors">
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-users text-blue-500"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Manage Players</span>
                            </a>
                            
                            <a href="#" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-blue-50 transition-colors">
                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                    <i class="fas fa-clipboard-list text-blue-500"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Training Plan</span>
                            </a>
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