<?php
$page_title = "Dashboard";

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new product
    if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        
        // Validate CSRF token
        if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
            redirect(BASE_URL . 'dashboard.php', 'Invalid request!', 'error');
        }
        
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $condition_type = $_POST['condition_type'] ?? '';
        $negotiable = $_POST['negotiable'] ?? '';
        
        $errors = [];
        
        if (empty($title)) $errors[] = "Product title is required";
        if (empty($description)) $errors[] = "Product description is required";
        if ($price <= 0) $errors[] = "Valid price is required";
        if (empty($category)) $errors[] = "Category is required";
        if (!in_array($condition_type, ['new', 'used'])) $errors[] = "Valid condition is required";
        if (!in_array($negotiable, ['yes', 'no'])) $errors[] = "Negotiable option is required";
        
        if (empty($errors)) {
            try {
                // Insert product
                $stmt = $pdo->prepare("
                    INSERT INTO products (user_id, title, description, price, category, condition_type, negotiable) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $title, $description, $price, $category, $condition_type, $negotiable]);
                $product_id = $pdo->lastInsertId();
                
                // Handle image uploads
                if (!empty($_FILES['images']['tmp_name'][0])) {
                    $upload_result = uploadProductImages($_FILES['images'], $product_id);
                    if (isset($upload_result['error'])) {
                        redirect(BASE_URL . 'dashboard.php', 'Product added but ' . $upload_result['error'], 'warning');
                    }
                }
                
                redirect(BASE_URL . 'dashboard.php', 'Product added successfully!', 'success');
                
            } catch (Exception $e) {
                redirect(BASE_URL . 'dashboard.php', 'Error adding product: ' . $e->getMessage(), 'error');
            }
        } else {
            redirect(BASE_URL . 'dashboard.php', implode(', ', $errors), 'error');
        }
    }
    
    // Delete product
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        
        if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
            redirect(BASE_URL . 'dashboard.php', 'Invalid request!', 'error');
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        try {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$product_id, $user_id]);
            
            if ($stmt->fetch()) {
                // Get images to delete files
                $images = getProductImages($product_id);
                
                // Delete product (cascade will handle images and cart items)
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
                $stmt->execute([$product_id, $user_id]);
                
                // Delete image files
                foreach ($images as $image) {
                    $file_path = UPLOAD_PATH . $image['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                redirect(BASE_URL . 'dashboard.php', 'Product deleted successfully!', 'success');
            } else {
                redirect(BASE_URL . 'dashboard.php', 'Product not found or access denied!', 'error');
            }
            
        } catch (Exception $e) {
            redirect(BASE_URL . 'dashboard.php', 'Error deleting product: ' . $e->getMessage(), 'error');
        }
    }
    
    // Update product status
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        
        if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
            redirect(BASE_URL . 'dashboard.php', 'Invalid request!', 'error');
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if (in_array($status, ['active', 'sold', 'inactive'])) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$status, $product_id, $user_id]);
                
                redirect(BASE_URL . 'dashboard.php', 'Product status updated!', 'success');
                
            } catch (Exception $e) {
                redirect(BASE_URL . 'dashboard.php', 'Error updating status: ' . $e->getMessage(), 'error');
            }
        }
    }
}

// Get user's products


// Get categories for dropdown
$categories = getCategories();

require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Welcome back, <?php echo sanitize($current_user['name']); ?>!</h1>
                <p class="text-blue-100">Manage your products and track your sales</p>
            </div>
            <div class="hidden md:flex items-center space-x-8 text-center">
                <div>
                    <div class="text-2xl font-bold"><?php echo count($user_products); ?></div>
                    <div class="text-sm text-blue-200">Total Products</div>
                </div>
                <div>
                    <div class="text-2xl font-bold"><?php echo count(array_filter($user_products, fn($p) => $p['status'] === 'active')); ?></div>
                    <div class="text-sm text-blue-200">Active</div>
                </div>
                <div>
                    <div class="text-2xl font-bold"><?php echo count(array_filter($user_products, fn($p) => $p['status'] === 'sold')); ?></div>
                    <div class="text-sm text-blue-200">Sold</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Add Product Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-24">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Product
                </h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_product">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    
                    <!-- Product Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Product Title *</label>
                        <input type="text" id="title" name="title" required maxlength="200"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., MacBook Pro 13-inch">
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                        <textarea id="description" name="description" required rows="3" maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe your product..."></textarea>
                    </div>
                    
                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (৳) *</label>
                        <input type="number" id="price" name="price" required min="1" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="5000">
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select id="category" name="category" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo sanitize($category['name']); ?>"><?php echo sanitize($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Condition -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Condition *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="condition_type" value="new" required class="text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">New</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="condition_type" value="used" required class="text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">Used</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Negotiable -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Negotiable *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="negotiable" value="yes" required class="text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="negotiable" value="no" required class="text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Images -->
                    <div>
                        <label for="images" class="block text-sm font-medium text-gray-700 mb-1">Images (Max 3)</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" max="3"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, SVG. Max 5MB each.</p>
                        <div id="images_preview" class="flex flex-wrap gap-2 mt-2"></div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 hover-scale">
                        Add Product
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Products List -->
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">My Products</h2>
                <div class="text-sm text-gray-500">
                    <?php echo count($user_products); ?> products
                </div>
            </div>
            
            <?php if (empty($user_products)): ?>
                <!-- Empty State -->
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No products yet</h3>
                    <p class="text-gray-600 mb-6">Start by adding your first product to the marketplace</p>
                    <a href="#" onclick="document.getElementById('title').focus()" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Your First Product
                    </a>
                </div>
            <?php else: ?>
                <!-- Products Grid -->
                <div class="space-y-6">
                    <?php foreach ($user_products as $product): ?>
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                            <div class="md:flex">
                                <!-- Product Image -->
                                <div class="md:w-48 h-48 bg-gray-200 relative overflow-hidden">
                                    <?php if ($product['first_image']): ?>
                                        <img src="<?php echo UPLOAD_URL . $product['first_image']; ?>" 
                                             alt="<?php echo sanitize($product['title']); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Image Count Badge -->
                                    <?php if ($product['image_count'] > 0): ?>
                                        <div class="absolute top-2 left-2">
                                            <span class="bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                                                📷 <?php echo $product['image_count']; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Status Badge -->
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php 
                                            echo match($product['status']) {
                                                'active' => 'bg-green-100 text-green-800',
                                                'sold' => 'bg-blue-100 text-blue-800',
                                                'inactive' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            }; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="flex-1 p-6">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo sanitize($product['title']); ?></h3>
                                            <p class="text-sm text-gray-600 line-clamp-2"><?php echo sanitize($product['description']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-4 mb-4">
                                        <div class="text-2xl font-bold text-blue-600"><?php echo formatPrice($product['price']); ?></div>
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full"><?php echo sanitize($product['category']); ?></span>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $product['condition_type'] === 'new' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?> rounded-full">
                                            <?php echo ucfirst($product['condition_type']); ?>
                                        </span>
                                        <?php if ($product['negotiable'] === 'yes'): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">Negotiable</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V8a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                                            </svg>
                                            <?php echo $product['cart_count']; ?> in cart | <?php echo timeAgo($product['created_at']); ?>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex items-center space-x-2">
                                            <!-- Status Update -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" 
                                                        class="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500">
                                                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="sold" <?php echo $product['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                </select>
                                            </form>
                                            
                                            <!-- Delete Button -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
