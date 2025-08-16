<?php
/**
 * CampusMart Marketplace
 * Browse and filter products with advanced search functionality
 */

// Start session and include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$is_logged_in = isLoggedIn();
$current_user = getCurrentUser();

// Set page title
$page_title = 'Marketplace';

// Pagination settings
$products_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_filter = isset($_GET['price']) ? trim($_GET['price']) : '';
$condition_filter = isset($_GET['condition']) ? trim($_GET['condition']) : '';
$negotiable_filter = isset($_GET['negotiable']) ? trim($_GET['negotiable']) : '';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Build WHERE clause for filters
$where_conditions = ["p.status = 'active'"];
$params = [];

// Search filter
if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter
if (!empty($category_filter) && $category_filter !== 'all') {
    $where_conditions[] = "p.category = ?";
    $params[] = $category_filter;
}

// Price range filter
if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'under_500':
            $where_conditions[] = "p.price < 500";
            break;
        case '500_1000':
            $where_conditions[] = "p.price BETWEEN 500 AND 1000";
            break;
        case '1000_3000':
            $where_conditions[] = "p.price BETWEEN 1000 AND 3000";
            break;
        case '3000_5000':
            $where_conditions[] = "p.price BETWEEN 3000 AND 5000";
            break;
        case 'above_5000':
            $where_conditions[] = "p.price > 5000";
            break;
    }
}

// Condition filter
if (!empty($condition_filter) && $condition_filter !== 'all') {
    $where_conditions[] = "p.condition_type = ?";
    $params[] = $condition_filter;
}

// Negotiable filter
if (!empty($negotiable_filter) && $negotiable_filter !== 'all') {
    $where_conditions[] = "p.negotiable = ?";
    $params[] = $negotiable_filter;
}

// Build ORDER BY clause
$order_by = match($sort_by) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Build final query
$where_clause = implode(' AND ', $where_conditions);

// Get total products count for pagination
$count_query = "SELECT COUNT(*) FROM products p 
                JOIN users u ON p.user_id = u.id 
                WHERE $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $products_per_page);

// Get products with pagination
$query = "SELECT p.*, u.name as seller_name, u.university,
          (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY image_order LIMIT 1) as first_image
          FROM products p 
          JOIN users u ON p.user_id = u.id 
          WHERE $where_clause 
          ORDER BY $order_by 
          LIMIT $products_per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter dropdown
$categories = getCategories();

// Handle AJAX add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    header('Content-Type: application/json');
    
    if (!$is_logged_in) {
        echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
        exit;
    }
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id > 0) {
        try {
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            
            if ($stmt->fetch()) {
                // Check if already in cart
                if (isInCart($product_id)) {
                    echo json_encode(['success' => false, 'message' => 'Item already in cart']);
                } else {
                    // Add to cart
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                    $stmt->execute([$_SESSION['user_id'], $product_id]);
                    
                    $cart_count = getCartCount();
                    echo json_encode(['success' => true, 'message' => 'Added to cart successfully', 'cart_count' => $cart_count]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
    }
    exit;
}

// Include header
include 'includes/header.php';
?>

<!-- Marketplace Header -->
<div class="bg-gradient-to-r from-blue-600 to-purple-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4 animate-fadeIn">Marketplace</h1>
            <p class="text-xl text-blue-100 animate-fadeIn">Discover amazing products from fellow students</p>
        </div>
    </div>
</div>

<!-- Search & Filters Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="" class="space-y-4">
            
            <!-- Search Bar -->
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="<?php echo sanitize($search); ?>"
                               placeholder="Search products..." 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-200 hover-scale">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
            </div>

            <!-- Filter Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo sanitize($category['name']); ?>" 
                                    <?php echo $category_filter === $category['name'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                    <select name="price" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Prices</option>
                        <option value="under_500" <?php echo $price_filter === 'under_500' ? 'selected' : ''; ?>>Under ৳500</option>
                        <option value="500_1000" <?php echo $price_filter === '500_1000' ? 'selected' : ''; ?>>৳500 - ৳1,000</option>
                        <option value="1000_3000" <?php echo $price_filter === '1000_3000' ? 'selected' : ''; ?>>৳1,000 - ৳3,000</option>
                        <option value="3000_5000" <?php echo $price_filter === '3000_5000' ? 'selected' : ''; ?>>৳3,000 - ৳5,000</option>
                        <option value="above_5000" <?php echo $price_filter === 'above_5000' ? 'selected' : ''; ?>>Above ৳5,000</option>
                    </select>
                </div>

                <!-- Condition Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Condition</label>
                    <select name="condition" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Conditions</option>
                        <option value="new" <?php echo $condition_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="used" <?php echo $condition_filter === 'used' ? 'selected' : ''; ?>>Used</option>
                    </select>
                </div>

                <!-- Negotiable Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Negotiable</label>
                    <select name="negotiable" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All</option>
                        <option value="yes" <?php echo $negotiable_filter === 'yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="no" <?php echo $negotiable_filter === 'no' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-center pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Showing <?php echo $total_products; ?> products
                    <?php if ($search || $category_filter || $price_filter || $condition_filter || $negotiable_filter): ?>
                        with current filters
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Apply Filters
                    </button>
                    <a href="marketplace.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200">
                        Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m4-8v16m4-16v16"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No products found</h3>
            <p class="mt-2 text-gray-500">Try adjusting your filters or search terms.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden animate-fadeIn hover-scale">
                    
                    <!-- Product Image -->
                    <div class="relative h-48 bg-gray-200">
                        <?php if ($product['first_image']): ?>
                            <img src="<?php echo UPLOAD_URL . sanitize($product['first_image']); ?>" 
                                 alt="<?php echo sanitize($product['title']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Condition Badge -->
                        <div class="absolute top-2 left-2">
                            <span class="bg-<?php echo $product['condition_type'] === 'new' ? 'green' : 'blue'; ?>-100 text-<?php echo $product['condition_type'] === 'new' ? 'green' : 'blue'; ?>-800 text-xs px-2 py-1 rounded-full">
                                <?php echo ucfirst($product['condition_type']); ?>
                            </span>
                        </div>
                        
                        <!-- Negotiable Badge -->
                        <?php if ($product['negotiable'] === 'yes'): ?>
                            <div class="absolute top-2 right-2">
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                    Negotiable
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <div class="mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 truncate" title="<?php echo sanitize($product['title']); ?>">
                                <?php echo sanitize($product['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo sanitize($product['category']); ?></p>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            <?php echo sanitize(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?>
                        </p>
                        
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl font-bold text-blue-600">
                                <?php echo formatPrice($product['price']); ?>
                            </span>
                        </div>
                        
                        <!-- Seller Info -->
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="truncate"><?php echo sanitize($product['seller_name']); ?></span>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><?php echo timeAgo($product['created_at']); ?></span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <?php if ($is_logged_in): ?>
                                <?php if ($product['user_id'] != $_SESSION['user_id']): ?>
                                    <?php if (isInCart($product['id'])): ?>
                                        <button class="flex-1 bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed" disabled>
                                            In Cart
                                        </button>
                                    <?php else: ?>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 add-to-cart-btn">
                                            Add to Cart
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="flex-1 bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed" disabled>
                                        Your Product
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg text-center hover:bg-blue-700 transition-colors duration-200">
                                    Login to Buy
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="viewProduct(<?php echo $product['id']; ?>)" 
                                    class="bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="flex space-x-2">
                    <?php
                    // Build current URL parameters for pagination
                    $url_params = [];
                    if ($search) $url_params['search'] = $search;
                    if ($category_filter) $url_params['category'] = $category_filter;
                    if ($price_filter) $url_params['price'] = $price_filter;
                    if ($condition_filter) $url_params['condition'] = $condition_filter;
                    if ($negotiable_filter) $url_params['negotiable'] = $negotiable_filter;
                    if ($sort_by) $url_params['sort'] = $sort_by;
                    ?>
                    
                    <!-- Previous Page -->
                    <?php if ($current_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($url_params, ['page' => $current_page - 1])); ?>" 
                           class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($url_params, ['page' => $i])); ?>" 
                           class="px-3 py-2 <?php echo $i === $current_page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Next Page -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($url_params, ['page' => $current_page + 1])); ?>" 
                           class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- JavaScript for AJAX and interactions -->
<script>
// Add to cart functionality
function addToCart(productId) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Adding...';
    
    fetch('marketplace.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in navbar
            const cartCount = document.querySelector('.absolute.-top-2.-right-2');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            } else if (data.cart_count > 0) {
                // Add cart count badge if it doesn't exist
                const cartIcon = document.querySelector('a[href*="cart.php"] svg').parentNode;
                const badge = document.createElement('span');
                badge.className = 'absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center';
                badge.textContent = data.cart_count;
                cartIcon.appendChild(badge);
            }
            
            // Update button state
            btn.innerHTML = 'In Cart';
            btn.className = 'flex-1 bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed';
            btn.disabled = true;
            
            // Show success message
            showMessage(data.message, 'success');
        } else {
            // Restore button
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            // Show error message
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
        showMessage('Error adding to cart', 'error');
    });
}

// View product details (placeholder for future implementation)
function viewProduct(productId) {
    // For now, we'll just show an alert
    // In the future, this could open a modal or redirect to a product detail page
    alert('Product details view - Coming soon!');
}

// Show notification messages
function showMessage(message, type) {
    const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `${alertClass} border px-4 py-3 rounded mb-4 fixed top-20 right-4 z-50 min-w-64 shadow-lg`;
    messageDiv.innerHTML = `<span class="block sm:inline">${message}</span>`;
    
    document.body.appendChild(messageDiv);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        messageDiv.style.transition = 'opacity 0.5s ease-out';
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 500);
    }, 3000);
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('select[name="category"], select[name="price"], select[name="condition"], select[name="negotiable"], select[name="sort"]');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Optional: Auto-submit form when filters change
            // Uncomment the line below if you want instant filtering
            // this.form.submit();
        });
    });
    
    // Search input auto-submit on Enter
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});

// Smooth scroll to top when pagination is clicked
document.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', function() {
        setTimeout(() => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }, 100);
    });
});

// Image loading optimization
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('load', function() {
        this.style.opacity = '1';
    });
    
    img.addEventListener('error', function() {
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik02Ny41IDc1SDEzMi41VjEyNUg2Ny41Vjc1WiIgZmlsbD0iIzlDQTNBRiIvPgo8L3N2Zz4K';
        this.alt = 'Image not available';
    });
});

// Add loading animation to product grid
function showLoading() {
    const productGrid = document.querySelector('.grid');
    if (productGrid) {
        productGrid.innerHTML = `
            <div class="col-span-full flex justify-center items-center py-12">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-600">Loading products...</p>
                </div>
            </div>
        `;
    }
}

// Enhanced hover effects for product cards
document.querySelectorAll('.hover-scale').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.02)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
});

// Price formatting for better readability
function formatPriceDisplay() {
    document.querySelectorAll('.text-2xl.font-bold.text-blue-600').forEach(priceElement => {
        const price = priceElement.textContent;
        // Add any additional price formatting if needed
    });
}

// Call price formatting on page load
formatPriceDisplay();

// Intersection Observer for lazy loading animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-fadeIn');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all product cards for animation
document.querySelectorAll('.bg-white.rounded-lg.shadow-md').forEach(card => {
    observer.observe(card);
});
</script>

<?php include 'includes/footer.php'; ?>