<?php
// Include configuration and functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
$cart_count = isLoggedIn() ? getCartCount() : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - CampusMart' : 'CampusMart - Student Marketplace'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS for animations and additional styles -->
    <style>
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }
        
        .animate-slideIn {
            animation: slideIn 0.4s ease-out;
        }
        
        /* Hover effects */
        .hover-scale {
            transition: transform 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        /* Glass effect */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        /* Sticky navbar shadow */
        .navbar-shadow {
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Sticky Navigation -->
    <nav class="bg-white sticky top-0 z-50 navbar-shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo BASE_URL; ?>" class="flex items-center space-x-2 hover-scale">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-800">CampusMart</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="<?php echo BASE_URL; ?>" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Home</a>
                    <a href="<?php echo BASE_URL; ?>marketplace.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Marketplace</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>dashboard.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Dashboard</a>
                        
                        <!-- Cart Icon -->
                        <a href="<?php echo BASE_URL; ?>cart.php" class="relative text-gray-700 hover:text-blue-600 transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V8a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                            </svg>
                            <?php if ($cart_count > 0): ?>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- User Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors duration-200">
                                <div class="bg-blue-100 p-2 rounded-full">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <span class="hidden lg:block"><?php echo sanitize($current_user['name']); ?></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="py-1">
                                    <a href="<?php echo BASE_URL; ?>dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Products</a>
                                    <a href="<?php echo BASE_URL; ?>cart.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Cart</a>
                                    <div class="border-t border-gray-100"></div>
                                    <a href="<?php echo BASE_URL; ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200">Login</a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 hover-scale">Register</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="text-gray-700 hover:text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="<?php echo BASE_URL; ?>" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Home</a>
                <a href="<?php echo BASE_URL; ?>marketplace.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Marketplace</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>dashboard.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>cart.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Cart (<?php echo $cart_count; ?>)</a>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="block px-3 py-2 text-red-600 hover:bg-red-50 rounded-md">Logout</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded-md">Login</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="block px-3 py-2 bg-blue-600 text-white rounded-md text-center">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php echo getFlashMessage(); ?>
    </div>

    <!-- Mobile menu toggle script -->
    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>