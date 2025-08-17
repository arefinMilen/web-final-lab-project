<?php
/**
 * CampusMart Helper Functions
 * Common functions used throughout the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user info
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Redirect with message
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . htmlspecialchars($url) . "';</script>";
        exit;
    }
}

// Display flash messages
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $alertClass = match($type) {
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            default => 'bg-blue-100 border-blue-400 text-blue-700'
        };
        
        return "<div class='$alertClass border px-4 py-3 rounded mb-4' role='alert'>
                    <span class='block sm:inline'>$message</span>
                </div>";
    }
    return '';
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format price in BDT
function formatPrice($price) {
    return '৳' . number_format($price, 2);
}

// Get categories from database
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Upload product images
function uploadProductImages($files, $product_id) {
    global $pdo;
    $uploaded = 0;
    $max_images = 3;
    $allowed_types = ['image/jpeg', 'image/png', 'image/svg+xml'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Check current image count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $current_count = $stmt->fetchColumn();
    
    if ($current_count >= $max_images) {
        return ['error' => 'Maximum 3 images allowed per product'];
    }
    
    foreach ($files['tmp_name'] as $key => $tmp_name) {
        if (empty($tmp_name) || $uploaded >= ($max_images - $current_count)) {
            continue;
        }
        
        $file_type = $files['type'][$key];
        $file_size = $files['size'][$key];
        $file_error = $files['error'][$key];
        
        // Validate file
        if ($file_error !== UPLOAD_ERR_OK) {
            continue;
        }
        
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }
        
        if ($file_size > $max_size) {
            continue;
        }
        
        // Generate unique filename
        $extension = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
        $filename = 'product_' . $product_id . '_' . time() . '_' . $uploaded . '.' . $extension;
        $upload_path = UPLOAD_PATH . $filename;
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_order) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $filename, $uploaded + 1]);
            $uploaded++;
        }
    }
    
    return ['success' => $uploaded . ' images uploaded successfully'];
}

// Get product images
function getProductImages($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY image_order");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

// Get cart count for user
function getCartCount($user_id = null) {
    global $pdo;
    if (!$user_id && !isLoggedIn()) {
        return 0;
    }
    
    $user_id = $user_id ?? $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?? 0;
}

// Check if product is in cart
function isInCart($product_id, $user_id = null) {
    global $pdo;
    if (!$user_id && !isLoggedIn()) {
        return false;
    }
    
    $user_id = $user_id ?? $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetch() !== false;
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

// Generate CSRF token
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
