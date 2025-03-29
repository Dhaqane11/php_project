<?php
// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Initialize variables
$error = '';
$success = '';

// Check if there's a registration success message
if (isset($_SESSION['registration_success'])) {
    $success = "Registration successful! You can now log in.";
    unset($_SESSION['registration_success']);
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on role
    if ($_SESSION['role'] == 'player') {
        header("Location: player/dashboard.php");
    } else {
        header("Location: coach/dashboard.php");
    }
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        try {
            $conn = connectDB();
            
            // Get user by email
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Set remember me cookie if checked
                    if ($remember) {
                        $token = bin2hex(random_bytes(32)); // Generate a random token
                        $expires = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // Store token in database
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
                        $stmt->execute([$token, date('Y-m-d H:i:s', $expires), $user['id']]);
                        
                        // Set cookie
                        setcookie('remember_token', $token, $expires, '/');
                        setcookie('user_email', $email, $expires, '/');
                    }
                    
                    // Update last login time
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Redirect to appropriate dashboard based on role
                    if ($user['role'] == 'player') {
                        header("Location: player/dashboard.php");
                    } else {
                        header("Location: coach/dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "No account found with that email";
            }
        } catch(PDOException $e) {
            $error = "Login failed: " . $e->getMessage();
        }
    }
}

// Check for "remember me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_email'])) {
    try {
        $conn = connectDB();
        
        $token = $_COOKIE['remember_token'];
        $email = $_COOKIE['user_email'];
        
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users 
                               WHERE email = ? AND remember_token = ? AND token_expires > NOW()");
        $stmt->execute([$email, $token]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login time
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Redirect to appropriate dashboard
            if ($user['role'] == 'player') {
                header("Location: player/dashboard.php");
            } else {
                header("Location: coach/dashboard.php");
            }
            exit;
        }
    } catch(PDOException $e) {
        // Just ignore errors with remember me functionality
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soccer Team Management - Login</title>
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
<body class="bg-soccer min-h-screen flex items-center justify-center">
    <div class="w-[500px] bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-blue-500 rounded-full mb-4">
                <i class="fas fa-futbol text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
            <p class="text-gray-600">Sign in to your account</p>
        </div>
        
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
        
        <form method="POST" action="" class="space-y-5">
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">
                    <i class="fas fa-envelope text-blue-500 mr-2"></i>Email Address
                </label>
                <input type="email" id="email" name="email" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="your@email.com" required>
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
            
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-key mr-1"></i>Forgot password?
                </a>
            </div>
            
            <div class="pt-4">
                <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </div>
        </form>
        
        <div class="text-center mt-8 border-t pt-6">
            <p class="text-sm text-gray-600 mb-3">Or sign in with</p>
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
    </script>
</body>
</html> 