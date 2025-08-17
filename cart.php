<?php
/**
 * CampusMart Shopping Cart
 * Complete cart management with update/remove functionality
 */

// Start session and include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view your cart', 'warning');
}

$current_user = getCurrentUser();
$page_title = 'Shopping Cart';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'update_quantity':
                $cart_id = intval($_POST['cart_id']);
                $quantity = max(1, intval($_POST['quantity'])); // Minimum quantity is 1
                
                // Verify cart item belongs to current user
                $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->execute([$quantity, $cart_id]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Quantity updated successfully';
                    $response['cart_count'] = getCartCount();
                } else {
                    $response['message'] = 'Cart item not found';
                }
                break;
                
            case 'remove_item':
                $cart_id = intval($_POST['cart_id']);
                
                // Verify cart item belongs to current user
                $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                    $stmt->execute([$cart_id]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Item removed from cart';
                    $response['cart_count'] = getCartCount();
                } else {
                    $response['message'] = 'Cart item not found';
                }
                break;
                
            case 'clear_cart':
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                $response['success'] = true;
                $response['message'] = 'Cart cleared successfully';
                $response['cart_count'] = 0;
                break;
                
            default:
                $response['message'] = 'Invalid action';
        }
    } catch (Exception $e) {
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Get cart items for current user
$query = "SELECT c.*, p.title, p.description, p.price, p.condition_type, p.negotiable, 
          p.status, u.name as seller_name, u.phone as seller_phone,
          (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY image_order LIMIT 1) as first_image
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          JOIN users u ON p.user_id = u.id 
          WHERE c.user_id = ? 
          ORDER BY c.added_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate cart totals
$subtotal = 0;
$total_items = 0;
$unavailable_items = [];

foreach ($cart_items as $item) {
    if ($item['status'] === 'active') {
        $subtotal += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    } else {
        $unavailable_items[] = $item;
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Cart Header -->
<div class="bg-gradient-to-r from-green-600 to-blue-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4 animate-fadeIn">Shopping Cart</h1>
            <p class="text-xl text-green-100 animate-fadeIn">Review your selected items</p>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart State -->
        <div class="text-center py-16">
            <div class="bg-white rounded-lg shadow-md p-12 max-w-md mx-auto">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V8a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900 mb-4">Your cart is empty</h3>
                <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
                <a href="marketplace.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-200 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Cart Content -->
        <div class="lg:grid lg:grid-cols-12 lg:gap-x-12 lg:items-start">
            
            <!-- Cart Items -->
            <div class="lg:col-span-8">
                
                <!-- Cart Header -->
                <div class="bg-white rounded-lg shadow-md mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Cart Items (<?php echo count($cart_items); ?>)
                            </h2>
                            <?php if (count($cart_items) > 0): ?>
                                <button onclick="clearCart()" class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors duration-200">
                                    Clear Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Unavailable Items Warning -->
                <?php if (!empty($unavailable_items)): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Some items in your cart are no longer available
                                </h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    <?php echo count($unavailable_items); ?> item(s) have been sold or removed by the seller.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Cart Items List -->
                <div class="space-y-4" id="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="bg-white rounded-lg shadow-md p-6 cart-item animate-fadeIn" data-cart-id="<?php echo $item['id']; ?>">
                            
                            <?php if ($item['status'] !== 'active'): ?>
                                <!-- Unavailable Item Overlay -->
                                <div class="absolute inset-0 bg-gray-500 bg-opacity-50 rounded-lg flex items-center justify-center z-10">
                                    <div class="bg-white px-4 py-2 rounded-lg shadow-md">
                                        <span class="text-red-600 font-semibold">No Longer Available</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex flex-col md:flex-row gap-6 <?php echo $item['status'] !== 'active' ? 'opacity-50' : ''; ?>">
                                
                                <!-- Product Image -->
                                <div class="md:w-32 md:h-32 w-full h-48 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                    <?php if ($item['first_image']): ?>
                                        <img src="<?php echo UPLOAD_URL . sanitize($item['first_image']); ?>" 
                                             alt="<?php echo sanitize($item['title']); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1">
                                    <div class="flex flex-col md:flex-row md:justify-between">
                                        
                                        <!-- Product Info -->
                                        <div class="flex-1 mb-4 md:mb-0 md:pr-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <?php echo sanitize($item['title']); ?>
                                            </h3>
                                            
                                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                                <?php echo sanitize(substr($item['description'], 0, 150)) . (strlen($item['description']) > 150 ? '...' : ''); ?>
                                            </p>
                                            
                                            <!-- Product Badges -->
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                <span class="bg-<?php echo $item['condition_type'] === 'new' ? 'green' : 'blue'; ?>-100 text-<?php echo $item['condition_type'] === 'new' ? 'green' : 'blue'; ?>-800 text-xs px-2 py-1 rounded-full">
                                                    <?php echo ucfirst($item['condition_type']); ?>
                                                </span>
                                                <?php if ($item['negotiable'] === 'yes'): ?>
                                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                                        Negotiable
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Seller Info -->
                                            <div class="flex items-center text-sm text-gray-500">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span>Sold by <?php echo sanitize($item['seller_name']); ?></span>
                                            </div>
                                        </div>

                                        <!-- Price and Actions -->
                                        <div class="md:w-48 flex flex-col items-end">
                                            
                                            <!-- Price -->
                                            <div class="text-right mb-4">
                                                <div class="text-2xl font-bold text-blue-600">
                                                    <?php echo formatPrice($item['price']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">per item</div>
                                            </div>

                                            <!-- Quantity Controls -->
                                            <?php if ($item['status'] === 'active'): ?>
                                                <div class="flex items-center mb-4">
                                                    <label class="text-sm text-gray-700 mr-3">Qty:</label>
                                                    <div class="flex items-center border border-gray-300 rounded-lg">
                                                        <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)" 
                                                                class="px-3 py-1 text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors duration-200 <?php echo $item['quantity'] <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                                                <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                            </svg>
                                                        </button>
                                                        <input type="number" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               class="w-16 px-2 py-1 text-center border-0 focus:ring-0"
                                                               onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                                        <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)" 
                                                                class="px-3 py-1 text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors duration-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Item Total -->
                                                <div class="text-lg font-semibold text-gray-900 mb-4">
                                                    Total: <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Remove Button -->
                                            <button onclick="removeItem(<?php echo $item['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors duration-200 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="lg:col-span-4 mt-8 lg:mt-0">
                <div class="bg-white rounded-lg shadow-md sticky top-24">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
                    </div>
                    
                    <div class="px-6 py-4 space-y-4">
                        
                        <!-- Summary Details -->
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items (<?php echo $total_items; ?>)</span>
                            <span class="text-gray-900"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Delivery</span>
                            <span class="text-gray-900">Free</span>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between text-lg font-semibold">
                                <span class="text-gray-900">Total</span>
                                <span class="text-blue-600"><?php echo formatPrice($subtotal); ?></span>
                            </div>
                        </div>
                        
                        <!-- Checkout Button -->
                        <?php if ($subtotal > 0): ?>
                            <div class="pt-4">
                                <a href="checkout.php" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center block font-semibold">
                                    Proceed to Checkout
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="pt-4">
                                <button class="w-full bg-gray-400 text-white py-3 px-4 rounded-lg cursor-not-allowed" disabled>
                                    No Available Items
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="marketplace.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-200">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                    
                    <!-- Security Note -->
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Secure campus-to-campus delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Cart Functionality -->
<script>
// Update item quantity
function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) return;
    
    const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
    const quantityInput = cartItem.querySelector('input[type="number"]');
    
    // Show loading state
    quantityInput.disabled = true;
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_quantity&cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the input value
            quantityInput.value = newQuantity;
            
            // Update cart count in navbar
            updateNavbarCartCount(data.cart_count);
            
            // Reload page to update totals
            setTimeout(() => {
                location.reload();
            }, 500);
            
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error updating quantity', 'error');
    })
    .finally(() => {
        quantityInput.disabled = false;
    });
}

// Remove item from cart
function removeItem(cartId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
    
    // Show loading state
    cartItem.style.opacity = '0.5';
    cartItem.style.pointerEvents = 'none';
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_item&cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item with animation
            cartItem.style.transition = 'all 0.3s ease-out';
            cartItem.style.transform = 'translateX(-100%)';
            cartItem.style.opacity = '0';
            
            setTimeout(() => {
                cartItem.remove();
                
                // Check if cart is empty
                const remainingItems = document.querySelectorAll('.cart-item').length;
                if (remainingItems === 0) {
                    location.reload();
                }
            }, 300);
            
            // Update cart count in navbar
            updateNavbarCartCount(data.cart_count);
            
            // Update totals after animation
            setTimeout(() => {
                location.reload();
            }, 1000);
            
            showMessage(data.message, 'success');
        } else {
            // Restore item state
            cartItem.style.opacity = '1';
            cartItem.style.pointerEvents = 'auto';
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        cartItem.style.opacity = '1';
        cartItem.style.pointerEvents = 'auto';
        showMessage('Error removing item', 'error');
    });
}

// Clear entire cart
function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart? This action cannot be undone.')) {
        return;
    }
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear_cart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in navbar
            updateNavbarCartCount(0);
            
            // Reload page
            location.reload();
            
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error clearing cart', 'error');
    });
}

// Update navbar cart count
function updateNavbarCartCount(count) {
    const cartCountElement = document.querySelector('.absolute.-top-2.-right-2');
    if (count > 0) {
        if (cartCountElement) {
            cartCountElement.textContent = count;
        } else {
            // Add cart count badge if it doesn't exist
            const cartIcon = document.querySelector('a[href*="cart.php"] svg').parentNode;
            const badge = document.createElement('span');
            badge.className = 'absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center';
            badge.textContent = count;
            cartIcon.appendChild(badge);
        }
    } else {
        if (cartCountElement) {
            cartCountElement.remove();
        }
    }
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
            if (messageDiv.parentNode) {
                document.body.removeChild(messageDiv);
            }
        }, 500);
    }, 3000);
}

// Enhanced animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate cart items on load
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        setTimeout(() => {
            item.style.transition = 'all 0.4s ease-out';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
