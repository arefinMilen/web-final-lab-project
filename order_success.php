<?php
/**
 * CampusMart Order Success Page
 * Display order confirmation and details
 */

// Start session and include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view order details', 'warning');
}

$current_user = getCurrentUser();
$page_title = 'Order Confirmation';

// Get order ID from URL
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

if (!$order_id) {
    redirect('marketplace.php', 'Invalid order ID', 'error');
}

// Get order details
$query = "SELECT o.*, u.name as buyer_name, u.email as buyer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ? AND o.user_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('marketplace.php', 'Order not found', 'error');
}

// Get order items
$query = "SELECT oi.*, p.title, p.description, p.condition_type, p.negotiable,
          u.name as seller_name, u.phone as seller_phone, u.email as seller_email,
          (SELECT image_path FROM product_images WHERE product_id = oi.product_id ORDER BY image_order LIMIT 1) as first_image
          FROM order_items oi 
          LEFT JOIN products p ON oi.product_id = p.id 
          JOIN users u ON oi.seller_id = u.id 
          WHERE oi.order_id = ?
          ORDER BY u.name, oi.product_title";

$stmt = $pdo->prepare($query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Group items by seller
$sellers = [];
foreach ($order_items as $item) {
    $seller_id = $item['seller_id'];
    if (!isset($sellers[$seller_id])) {
        $sellers[$seller_id] = [
            'name' => $item['seller_name'],
            'phone' => $item['seller_phone'],
            'email' => $item['seller_email'],
            'items' => [],
            'total' => 0
        ];
    }
    $sellers[$seller_id]['items'][] = $item;
    $sellers[$seller_id]['total'] += $item['price_at_time'] * $item['quantity'];
}

// Include header
include 'includes/header.php';
?>

<!-- Success Header -->
<div class="bg-gradient-to-r from-green-500 to-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="animate-fadeIn">
            <!-- Success Icon -->
            <div class="mx-auto w-20 h-20 bg-white rounded-full flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h1 class="text-4xl font-bold mb-4">Order Confirmed!</h1>
            <p class="text-xl text-green-100 mb-2">Thank you for your order</p>
            <p class="text-lg text-green-200">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- What's Next Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-blue-900 mb-4">What happens next?</h2>
        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div class="flex items-start">
                <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                <div>
                    <h3 class="font-medium text-blue-900">Sellers will contact you</h3>
                    <p class="text-blue-700">Each seller will reach out to arrange delivery</p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                <div>
                    <h3 class="font-medium text-blue-900">Meet & inspect items</h3>
                    <p class="text-blue-700">Check items before payment</p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                <div>
                    <h3 class="font-medium text-blue-900">Pay cash on delivery</h3>
                    <p class="text-blue-700">Safe payment upon receipt</p>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-12 lg:gap-x-12">
        
        <!-- Order Details -->
        <div class="lg:col-span-8">
            
            <!-- Order Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-900">Order Number</dt>
                        <dd class="text-gray-600">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-900">Order Date</dt>
                        <dd class="text-gray-600"><?php echo date('M j, Y \a\t g:i A', strtotime($order['order_date'])); ?></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-900">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-900">Total Amount</dt>
                        <dd class="text-gray-600 font-semibold"><?php echo formatPrice($order['total_price']); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Delivery Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Delivery Information</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="font-medium text-gray-900">Delivery Address:</span>
                        <p class="text-gray-600 mt-1 whitespace-pre-line"><?php echo sanitize($order['delivery_address']); ?></p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-900">Contact Phone:</span>
                        <p class="text-gray-600"><?php echo sanitize($order['phone']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Items by Seller -->
            <div class="space-y-6">
                <?php foreach ($sellers as $seller_id => $seller_data): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Seller Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?php echo sanitize($seller_data['name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">Seller Contact Information</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-blue-600">
                                        <?php echo formatPrice($seller_data['total']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">Order total</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="px-6 py-4 bg-blue-50 border-b border-gray-200">
                            <div class="grid md:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="font-medium text-blue-900">Phone:</span>
                                    <a href="tel:<?php echo sanitize($seller_data['phone']); ?>" class="ml-2 text-blue-600 hover:text-blue-800">
                                        <?php echo sanitize($seller_data['phone']); ?>
                                    </a>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-medium text-blue-900">Email:</span>
                                    <a href="mailto:<?php echo sanitize($seller_data['email']); ?>" class="ml-2 text-blue-600 hover:text-blue-800">
                                        <?php echo sanitize($seller_data['email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Seller's Items -->
                        <div class="px-6 py-4">
                            <div class="space-y-4">
                                <?php foreach ($seller_data['items'] as $item): ?>
                                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                        <!-- Product Image -->
                                        <div class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                            <?php if ($item['first_image']): ?>
                                                <img src="<?php echo UPLOAD_URL . sanitize($item['first_image']); ?>" 
                                                     alt="<?php echo sanitize($item['product_title']); ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Product Details -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-gray-900 truncate">
                                                <?php echo sanitize($item['product_title']); ?>
                                            </h4>
                                            
                                            <!-- Product Attributes -->
                                            <div class="flex items-center space-x-2 mt-1">
                                                <?php if ($item['condition_type']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-<?php echo $item['condition_type'] === 'new' ? 'green' : 'blue'; ?>-100 text-<?php echo $item['condition_type'] === 'new' ? 'green' : 'blue'; ?>-800">
                                                        <?php echo ucfirst($item['condition_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['negotiable'] === 'yes'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Negotiable
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex items-center text-xs text-gray-500 mt-1">
                                                <span><?php echo formatPrice($item['price_at_time']); ?></span>
                                                <span class="mx-2">×</span>
                                                <span><?php echo $item['quantity']; ?></span>
                                            </div>
                                        </div>

                                        <!-- Item Total -->
                                        <div class="text-right">
                                            <p class="text-lg font-semibold text-gray-900">
                                                <?php echo formatPrice($item['price_at_time'] * $item['quantity']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="lg:col-span-4 mt-8 lg:mt-0">
            <div class="bg-white rounded-lg shadow-md sticky top-24">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Order Totals -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Items (<?php echo array_sum(array_column($order_items, 'quantity')); ?>)</span>
                            <span class="text-gray-900"><?php echo formatPrice($order['total_price']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery</span>
                            <span class="text-green-600">Free</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2">
                            <div class="flex justify-between text-lg font-semibold">
                                <span class="text-gray-900">Total Paid</span>
                                <span class="text-blue-600"><?php echo formatPrice($order['total_price']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="pt-4 space-y-3">
                        <a href="marketplace.php" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center block font-semibold">
                            Continue Shopping
                        </a>
                        <a href="dashboard.php" class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200 text-center block font-medium">
                            View My Products
                        </a>
                    </div>
                </div>
                
                <!-- Support Information -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg">
                    <h4 class="font-medium text-gray-900 mb-2">Need Help?</h4>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p>• Contact sellers directly for delivery updates</p>
                        <p>• Inspect items before payment</p>
                        <p>• Report any issues to CampusMart support</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Enhanced UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate elements on page load
    const elements = document.querySelectorAll('.bg-white, .bg-blue-50');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        setTimeout(() => {
            el.style.transition = 'all 0.4s ease-out';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add click-to-call functionality
    const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
    phoneLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Track click for analytics if needed
            console.log('Phone number clicked:', this.href);
        });
    });
    
    // Add click-to-email functionality
    const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
    emailLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Track click for analytics if needed
            console.log('Email clicked:', this.href);
        });
    });
    
    // Auto-copy order number on click
    const orderNumber = document.querySelector('h1 + p + p');
    if (orderNumber) {
        orderNumber.style.cursor = 'pointer';
        orderNumber.title = 'Click to copy order number';
        
        orderNumber.addEventListener('click', function() {
            const orderNum = this.textContent.replace('Order ', '');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(orderNum).then(() => {
                    showMessage('Order number copied to clipboard!', 'success');
                });
            }
        });
    }
});

// Show notification messages
function showMessage(message, type) {
    const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `${alertClass} border px-4 py-3 rounded mb-4 fixed top-20 right-4 z-50 min-w-64 shadow-lg`;
    messageDiv.innerHTML = `<span class="block sm:inline">${message}</span>`;
    
    document.body.appendChild(messageDiv);
    
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
</script>

<?php include 'includes/footer.php'; ?>