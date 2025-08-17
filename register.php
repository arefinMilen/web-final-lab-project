<?php
$page_title = "Register";
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // Sanitize and validate input
        $form_data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'university' => sanitize($_POST['university'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        // Validation
        if (empty($form_data['name'])) {
            $errors[] = "Name is required.";
        } elseif (strlen($form_data['name']) < 2) {
            $errors[] = "Name must be at least 2 characters long.";
        }

        if (empty($form_data['email'])) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if (empty($form_data['phone'])) {
            $errors[] = "Phone number is required.";
        } elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $form_data['phone'])) {
            $errors[] = "Please enter a valid phone number.";
        }

        if (empty($form_data['university'])) {
            $errors[] = "University is required.";
        }

        if (empty($form_data['password'])) {
            $errors[] = "Password is required.";
        } elseif (strlen($form_data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        if ($form_data['password'] !== $form_data['confirm_password']) {
            $errors[] = "Passwords do not match.";
        }

        // Check if email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$form_data['email']]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email already exists.";
            }
        }

        // Create user if no errors
        if (empty($errors)) {
            $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, university, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $form_data['name'],
                    $form_data['email'],
                    $form_data['phone'],
                    $form_data['university'],
                    $hashed_password
                ]);

                redirect(BASE_URL . 'login.php', 'Registration successful! Please login to continue.', 'success');
            } catch (Exception $e) {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 animate-fadeIn">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
            <p class="mt-2 text-sm text-gray-600">Join CampusMart and start buying & selling</p>
        </div>

        <!-- Registration Form -->
        <form class="mt-8 space-y-6 bg-white p-8 rounded-xl shadow-lg" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input id="name" name="name" type="text" required 
                           value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                           placeholder="Enter your full name">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input id="email" name="email" type="email" required 
                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                           placeholder="your.email@university.edu">
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input id="phone" name="phone" type="tel" required 
                           value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                           placeholder="01700000000">
                </div>

                <!-- University -->
                <div>
                    <label for="university" class="block text-sm font-medium text-gray-700 mb-2">University</label>
                    <select id="university" name="university" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        <option value="">Select your university</option>
                        <option value="University of Dhaka" <?php echo ($form_data['university'] ?? '') === 'University of Dhaka' ? 'selected' : ''; ?>>University of Dhaka</option>
                        <option value="Bangladesh University of Engineering and Technology" <?php echo ($form_data['university'] ?? '') === 'Bangladesh University of Engineering and Technology' ? 'selected' : ''; ?>>BUET</option>
                        <option value="Dhaka University of Engineering and Technology" <?php echo ($form_data['university'] ?? '') === 'Dhaka University of Engineering and Technology' ? 'selected' : ''; ?>>DUET</option>
                        <option value="North South University" <?php echo ($form_data['university'] ?? '') === 'North South University' ? 'selected' : ''; ?>>North South University</option>
                        <option value="BRAC University" <?php echo ($form_data['university'] ?? '') === 'BRAC University' ? 'selected' : ''; ?>>BRAC University</option>
                        <option value="Independent University Bangladesh" <?php echo ($form_data['university'] ?? '') === 'Independent University Bangladesh' ? 'selected' : ''; ?>>IUB</option>
                        <option value="American International University Bangladesh" <?php echo ($form_data['university'] ?? '') === 'American International University Bangladesh' ? 'selected' : ''; ?>>AIUB</option>
                        <option value="East West University" <?php echo ($form_data['university'] ?? '') === 'East West University' ? 'selected' : ''; ?>>East West University</option>
                        <option value="United International University" <?php echo ($form_data['university'] ?? '') === 'United International University' ? 'selected' : ''; ?>>UIU</option>
                        <option value="Other" <?php echo ($form_data['university'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                               placeholder="Choose a strong password">
                        <button type="button" onclick="togglePassword('password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="password-eye" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Must be at least 6 characters long</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <div class="relative">
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
                               placeholder="Confirm your password">
                        <button type="button" onclick="togglePassword('confirm_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="confirm_password-eye" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover-scale">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-white group-hover:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </span>
                    Create Account
                </button>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="<?php echo BASE_URL; ?>login.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        field.type = 'password';
        eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Real-time password confirmation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('border-red-500');
        this.classList.remove('border-gray-300');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
        this.classList.add('border-gray-300');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
