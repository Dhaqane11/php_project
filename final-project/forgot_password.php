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
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($new_password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        try {
            $conn = connectDB();
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                $error = "No account found with that email";
            } else {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
                
                // Set success message
                $success = "Your password has been reset successfully";
            }
        } catch(PDOException $e) {
            $error = "Password reset failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soccer Team Management - Reset Password</title>
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
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Reset Password</h1>
            <p class="text-gray-600">Enter your email and new password</p>
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
            
            <div class="text-center mt-4">
                <a href="login.php" 
                   class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            </div>
        <?php else: ?>
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
                    <label for="new_password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock text-blue-500 mr-2"></i>New Password
                    </label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword('new_password')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long.</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-check-circle text-blue-500 mr-2"></i>Confirm New Password
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
                
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>Reset Password
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-8 border-t pt-6">
            <a href="login.php" class="text-blue-600 hover:text-blue-800 transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i>
                <span>Back to Login</span>
            </a>
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
        
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });
    </script>
</body>
</html> 