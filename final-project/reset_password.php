<?php
// We'll add logic later
$error = '';
$success = '';

// In a real implementation, you would validate the token from the URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

if (empty($token) || empty($email)) {
    $error = 'Invalid or expired password reset link.';
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
                <i class="fas fa-lock-open text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Reset Password</h1>
            <p class="text-gray-600">Create a new password for your account</p>
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
            
            <div class="text-center">
                <a href="login.php" 
                   class="inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div>
                    <label for="password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock text-blue-500 mr-2"></i>New Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3 text-gray-500">
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
            
            <div class="text-center mt-8 border-t pt-6">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 transition flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Back to Login</span>
                </a>
            </div>
        <?php endif; ?>
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