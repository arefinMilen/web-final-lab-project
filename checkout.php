<?php
/**
 * CampusMart Checkout System
 * Complete order processing with delivery details
 */

// Start session and include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to checkout', 'warning');
}

$current_user = getCurrentUser();
$page_title = 'Checkout';

// Get active cart items for current user
$query = "SELECT c.*, p.title, p.description, p.price, p.condition_type, p.negotiable, 
          p.status, p.user_id as seller_id, u.name as seller_name, u.phone as seller_phone,
          (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY image_order LIMIT 1) as first_image
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          JOIN users u ON p.user_id = u.id 
          WHERE c.user_id = ? AND p.status = 'active'
          ORDER BY c.added_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Check if cart is empty
if (empty($cart_items)) {
    redirect('cart.php', 'Your cart is empty or contains no available items', 'warning');
}

// Calculate totals
$subtotal = 0;
$total_items = 0;
$sellers = [];

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
    
    // Group items by seller for order organization
    if (!isset($sellers[$item['seller_id']])) {
        $sellers[$item['seller_id']] = [
            'name' => $item['seller_name'],
            'phone' => $item['seller_phone'],
            'items' => [],
            'total' => 0
        ];
    }
    
    $sellers[$item['seller_id']]['items'][] = $item;
    $sellers[$item['seller_id']]['total'] += $item['price'] * $item['quantity'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    $errors = [];
    
    // Validate CSRF token
    if (!verifyCSRF($csrf_token)) {
        $errors[] = 'Invalid security token. Please try again.';
    }
    
    // Validate required fields
    if (empty($delivery_address)) {
        $errors[] = 'Delivery address is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create the order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, delivery_address, phone, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $subtotal, $delivery_address, $phone]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price_at_time, product_title) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['seller_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['title']
                ]);
            }
            
            // Store special instructions if provided
            if (!empty($special_instructions)) {
                $stmt = $pdo->prepare("UPDATE orders SET delivery_address = CONCAT(delivery_address, '\n\nSpecial Instructions: ', ?) WHERE id = ?");
                $stmt->execute([$special_instructions, $order_id]);
            }
            
            // Clear the cart (keep items until delivered, but mark as ordered)
            // We'll actually keep items in cart and clear them when delivered
            // For now, let's clear the cart as the order is placed
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Redirect to success page
            redirect('order_success.php?order=' . $order_id, 'Order placed successfully!', 'success');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error processing order: ' . $e->getMessage();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Checkout Header -->
<div class="bg-gradient-to-r from-purple-600 to-blue-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4 animate-fadeIn">Checkout</h1>
            <p class="text-xl text-purple-100 animate-fadeIn">Complete your order</p>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Checkout Progress -->
    <div class="mb-8">
        <div class="flex items-center justify-center space-x-4">
            <div class="flex items-center">
                <div class="bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold">
                    ✓
                </div>
                <span class="ml-2 text-sm font-medium text-green-600">Cart</span>
            </div>
            <div class="w-16 h-1 bg-green-500"></div>
            <div class="flex items-center">
                <div class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold">
                    2
                </div>
                <span class="ml-2 text-sm font-medium text-blue-600">Checkout</span>
            </div>
            <div class="w-16 h-1 bg-gray-300"></div>
            <div class="flex items-center">
                <div class="bg-gray-300 text-gray-500 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold">
                    3
                </div>
                <span class="ml-2 text-sm font-medium text-gray-500">Complete</span>
            </div>
        </div>
    </div>

    <!-- Display Errors -->
    

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-12 lg:items-start">
        
        <!-- Checkout Form -->
        <div class="lg:col-span-7">
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                
                <!-- Delivery Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Delivery Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Full Name (Pre-filled) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" 
                                   value="<?php echo sanitize($current_user['name']); ?>" 
                                   disabled
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-500">
                        </div>
                        
                        <!-- University (Pre-filled) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">University</label>
                            <input type="text" 
                                   value="<?php echo sanitize($current_user['university']); ?>" 
                                   disabled
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-500">
                        </div>
                        
                        <!-- Phone Number -->
                        <div class="md:col-span-2">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo sanitize($current_user['phone']); ?>"
                                   placeholder="01700000000"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <!-- Delivery Address -->
                        <div class="md:col-span-2">
                            <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-2">
                                Delivery Address <span class="text-red-500">*</span>
                            </label>
                            <textarea id="delivery_address" 
                                      name="delivery_address" 
                                      rows="4"
                                      placeholder="Please provide your complete campus address including dormitory/building name, room number, and any landmarks..."
                                      required
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo sanitize($_POST['delivery_address'] ?? ''); ?></textarea>
                            <p class="text-sm text-gray-500 mt-1">Include building name, room number, and any helpful landmarks</p>
                        </div>
                        
                        <!-- Special Instructions -->
                        <div class="md:col-span-2">
                            <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                                Special Instructions (Optional)
                            </label>
                            <textarea id="special_instructions" 
                                      name="special_instructions" 
                                      rows="3"
                                      placeholder="Any special delivery instructions, preferred contact times, etc..."
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo sanitize($_POST['special_instructions'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Payment Method
                    </h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-green-800">Cash on Delivery</h3>
                                <p class="text-sm text-green-600">Pay when you receive your items</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <p>💡 <strong>How it works:</strong></p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Sellers will contact you to arrange delivery</li>
                            <li>Pay in cash when you receive the items</li>
                            <li>Inspect items before payment</li>
                            <li>Safe and secure campus transactions</li>
                        </ul>
                    </div>
                </div>

                <!-- Order Review Button -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="cart.php" class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition-colors duration-200 text-center font-semibold">
                        ← Back to Cart
                    </a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-200 font-semibold">
                        Place Order →
                    </button>
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-5 mt-8 lg:mt-0">
            <div class="bg-white rounded-lg shadow-md sticky top-24">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
                </div>
                
                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                    <!-- Items by Seller -->
                    <?php foreach ($sellers as $seller_id => $seller_data): ?>
                        <div class="mb-6 last:mb-0">
                            <div class="flex items-center text-sm text-gray-600 mb-3">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="font-medium">Sold by <?php echo sanitize($seller_data['name']); ?></span>
                            </div>
                            
                            <?php foreach ($seller_data['items'] as $item): ?>
                                <div class="flex items-center space-x-3 mb-3 pb-3 border-b border-gray-100 last:border-b-0">
                                    <!-- Product Image -->
                                    <div class="w-12 h-12 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                                        <?php if ($item['first_image']): ?>
                                            <img src="<?php echo UPLOAD_URL . sanitize($item['first_image']); ?>" 
                                                 alt="<?php echo sanitize($item['title']); ?>"
                                                 class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Product Details -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?php echo sanitize($item['title']); ?>
                                        </p>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <span><?php echo formatPrice($item['price']); ?></span>
                                            <span class="mx-1">×</span>
                                            <span><?php echo $item['quantity']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Item Total -->
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="flex justify-between text-sm font-medium text-gray-900 pt-2">
                                <span>Seller Total:</span>
                                <span><?php echo formatPrice($seller_data['total']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Totals -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items (<?php echo $total_items; ?>)</span>
                            <span class="text-gray-900"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Delivery</span>
                            <span class="text-green-600">Free</span>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-2">
                            <div class="flex justify-between text-lg font-semibold">
                                <span class="text-gray-900">Total</span>
                                <span class="text-blue-600"><?php echo formatPrice($subtotal); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Features -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg">
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Secure campus transactions</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <span>Direct seller contact</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Fast campus delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Enhanced UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Form validation
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing Order...';
        
        // Basic validation
        const deliveryAddress = document.getElementById('delivery_address').value.trim();
        const phone = document.getElementById('phone').value.trim();
        
        if (!deliveryAddress || !phone) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Place Order →';
            alert('Please fill in all required fields');
            return;
        }
        
        // Phone validation
        const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Place Order →';
            alert('Please enter a valid phone number');
            return;
        }
    });
    
    // Smooth animations
    const elements = document.querySelectorAll('.bg-white');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        setTimeout(() => {
            el.style.transition = 'all 0.4s ease-out';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Character counter for textareas
    const addressTextarea = document.getElementById('delivery_address');
    const instructionsTextarea = document.getElementById('special_instructions');
    
    function addCharacterCounter(textarea, maxLength = 500) {
        const counter = document.createElement('div');
        counter.className = 'text-xs text-gray-500 mt-1 text-right';
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length}/${maxLength}`;
            counter.className = remaining < 50 ? 'text-xs text-red-500 mt-1 text-right' : 'text-xs text-gray-500 mt-1 text-right';
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    addCharacterCounter(addressTextarea);
    addCharacterCounter(instructionsTextarea, 300);
});
</script>

<?php include 'includes/footer.php'; ?>
