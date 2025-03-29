<?php
// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    // For debugging
    // echo "Password: " . $password . "<br>";
    // echo "Confirm Password: " . $confirm_password . "<br>";
    
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
                
                // Set success message and redirect
                $_SESSION['registration_success'] = true;
                header("Location: login.php");
                exit;
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
    <title>Soccer Team Management - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-soccer {
            background-color: #f0f7ff;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48ZyBmaWxsPSIjMjY2OGZmIiBmaWxsLW9wYWNpdHk9IjAuMDUiPjxwYXRoIGQ9Ik0yNSA1MGMxMy44MDcgMCAyNS0xMS4xOTMgMjUtMjVTMzguODA3IDAgMjUgMCAwIDExLjE5MyAwIDI1czExLjE5MyAyNSAyNSAyNXoiLz48L2c+PC9zdmc+');
        }
    </style>
</head>
<body class="bg-soccer min-h-screen flex items-center justify-center py-8">
    <div class="w-[500px] bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-blue-500 rounded-full mb-4">
                <i class="fas fa-futbol text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Join Our Team</h1>
            <p class="text-gray-600">Create your account to get started</p>
        </div>
        
        <?php if($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="space-y-5">
            <!-- Name and Email in flex layout -->
            <div class="flex gap-4">
                <div class="w-1/2">
                    <label for="name" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-user text-blue-500 mr-2"></i>Full Name
                    </label>
                    <input type="text" id="name" name="name" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="John Doe" required>
                </div>
                
                <div class="w-1/2">
                    <label for="email" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                    </label>
                    <input type="email" id="email" name="email" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="your@email.com" required>
                </div>
            </div>
            
            <!-- Password fields in flex layout -->
            <div class="flex gap-4">
                <div class="w-1/2">
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
                
                <div class="w-1/2">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-check-circle text-blue-500 mr-2"></i>Confirm
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
            </div>
            
            <div>
                <label for="phone" class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-phone text-blue-500 mr-2"></i>Phone Number
                </label>
                <input type="tel" id="phone" name="phone" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="+1234567890">
            </div>
            
            <div>
                <label for="role" class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-user-tag text-blue-500 mr-2"></i>Your Role
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="cursor-pointer" onclick="selectRole('coach')">
                        <div id="coach-card" class="border-2 border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 hover:bg-blue-50 transition">
                            <i class="fas fa-clipboard text-3xl mb-2 text-gray-600"></i>
                            <p class="font-medium">Coach</p>
                        </div>
                    </div>
                    <div class="cursor-pointer" onclick="selectRole('player')">
                        <div id="player-card" class="border-2 border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 hover:bg-blue-50 transition">
                            <i class="fas fa-running text-3xl mb-2 text-gray-600"></i>
                            <p class="font-medium">Player</p>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="role" name="role" required>
            </div>
            
            <!-- Player-specific fields -->
            <div id="player-fields" class="hidden space-y-5">
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold mb-3 text-blue-800 flex items-center">
                        <i class="fas fa-user-shield mr-2"></i>Player Information
                    </h3>
                    
                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label for="age" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-birthday-cake text-blue-500 mr-2"></i>Age
                            </label>
                            <input type="number" id="age" name="age" min="16" max="50" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="w-1/2">
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
            </div>
            
            <!-- Coach-specific fields -->
            <div id="coach-fields" class="hidden space-y-5">
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold mb-3 text-blue-800 flex items-center">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>Coach Information
                    </h3>
                    
                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label for="specialization" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>Specialization
                            </label>
                            <input type="text" id="specialization" name="specialization" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. Defense, Goalkeeper Training">
                        </div>
                        
                        <div class="w-1/2">
                            <label for="coach_experience" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-history text-blue-500 mr-2"></i>Years Experience
                            </label>
                            <input type="number" id="coach_experience" name="coach_experience" min="0" max="50" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="pt-4">
                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </div>
        </form>
        
        <div class="text-center mt-8 border-t pt-6">
            <p class="text-sm text-gray-600 mb-3">Or sign up with</p>
            <div class="flex justify-center space-x-4">
                <a href="#" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition">
                    <i class="fab fa-google text-xl text-red-500"></i>
                </a>
                <a href="#" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition">
                    <i class="fab fa-facebook-f text-xl text-blue-600"></i>
                </a>
                <a href="#" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition">
                    <i class="fab fa-twitter text-xl text-blue-400"></i>
                </a>
            </div>
        </div>
        
        <div class="text-center mt-6">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-sign-in-alt mr-1"></i>Sign in
                </a>
            </p>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html> 