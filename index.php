<?php
$page_title = "Student Marketplace";
require_once 'includes/header.php';

// Get recent products for featured section
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as seller_name, 
               (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY image_order LIMIT 1) as first_image
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (Exception $e) {
    $featured_products = [];
}

// Get total statistics
try {
    $stats_stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM products WHERE status = 'active') as total_products,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(DISTINCT category) FROM products) as total_categories,
            (SELECT COUNT(*) FROM orders WHERE status = 'delivered') as total_sales
    ");
    $stats = $stats_stmt->fetch();
} catch (Exception $e) {
    $stats = ['total_products' => 0, 'total_users' => 0, 'total_categories' => 0, 'total_sales' => 0];
}
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
            <defs>
                <pattern id="hero-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="1" fill="currentColor"/>
                </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#hero-pattern)"/>
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Left Content -->
            <div class="animate-fadeIn">
                <h1 class="text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    Your Campus
                    <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">
                        Marketplace
                    </span>
                </h1>
                <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                    Buy and sell products within your university community. Safe, convenient, and trusted by students and teachers.
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>marketplace.php" 
                           class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-200 hover-scale text-center">
                            Browse Marketplace
                        </a>
                        <a href="<?php echo BASE_URL; ?>dashboard.php" 
                           class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-all duration-200 hover-scale text-center">
                            Sell Your Items
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>register.php" 
                           class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-200 hover-scale text-center">
                            Get Started Free
                        </a>
                        <a href="<?php echo BASE_URL; ?>marketplace.php" 
                           class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-all duration-200 hover-scale text-center">
                            Browse Products
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400"><?php echo number_format($stats['total_products']); ?>+</div>
                        <div class="text-sm text-blue-200">Products</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400"><?php echo number_format($stats['total_users']); ?>+</div>
                        <div class="text-sm text-blue-200">Users</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400"><?php echo $stats['total_categories']; ?>+</div>
                        <div class="text-sm text-blue-200">Categories</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400"><?php echo number_format($stats['total_sales']); ?>+</div>
                        <div class="text-sm text-blue-200">Sales</div>
                    </div>
                </div>
            </div>

            <!-- Right Content - Hero Image/Animation -->
            <div class="relative animate-fadeIn">
                <div class="relative z-10 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                    <!-- Mock Product Cards -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 bg-white/20 rounded-lg p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-white">MacBook Pro</div>
                                <div class="text-blue-200 text-sm">৳85,000</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 bg-white/20 rounded-lg p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-400 to-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-white">Programming Books</div>
                                <div class="text-blue-200 text-sm">৳2,500</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 bg-white/20 rounded-lg p-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-400 to-red-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-white">Gaming Chair</div>
                                <div class="text-blue-200 text-sm">৳12,000</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Floating Elements -->
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-yellow-400 rounded-full opacity-80 animate-bounce"></div>
                <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-purple-400 rounded-full opacity-60 animate-pulse"></div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Why Choose CampusMart?</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Built specifically for university communities with features that matter to students and educators.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Safe & Trusted</h3>
                <p class="text-gray-600">
                    University-verified users only. Buy and sell with confidence within your trusted academic community.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-blue-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Fast & Easy</h3>
                <p class="text-gray-600">
                    List your items in minutes. Advanced search and filtering make finding what you need super quick.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Budget-Friendly</h3>
                <p class="text-gray-600">
                    No commission fees! Keep 100% of your earnings. Perfect for student budgets and academic needs.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Community Focus</h3>
                <p class="text-gray-600">
                    Connect with classmates, seniors, and teachers. Build relationships while making great deals.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Rich Media</h3>
                <p class="text-gray-600">
                    Upload multiple high-quality images. Detailed descriptions help buyers make informed decisions.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale animate-fadeIn">
                <div class="w-16 h-16 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Smart Filters</h3>
                <p class="text-gray-600">
                    Advanced filtering by price, condition, category, and negotiability. Find exactly what you need.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Recent Products</h2>
            <p class="text-xl text-gray-600">
                Check out the latest items added to our marketplace
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach (array_slice($featured_products, 0, 8) as $product): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover-scale border border-gray-100">
                    <!-- Product Image -->
                    <div class="h-48 bg-gray-200 relative overflow-hidden">
                        <?php if ($product['first_image']): ?>
                            <img src="<?php echo UPLOAD_URL . $product['first_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Condition Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['condition_type'] === 'new' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo ucfirst($product['condition_type']); ?>
                            </span>
                        </div>

                        <!-- Negotiable Badge -->
                        <?php if ($product['negotiable'] === 'yes'): ?>
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    Negotiable
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($product['title']); ?></h3>
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                        
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-2xl font-bold text-blue-600"><?php echo formatPrice($product['price']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo $product['category']; ?></div>
                        </div>

                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <?php echo htmlspecialchars($product['seller_name']); ?>
                        </div>

                        <div class="text-xs text-gray-400">
                            <?php echo timeAgo($product['created_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center">
            <a href="<?php echo BASE_URL; ?>marketplace.php" 
               class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 transition-all duration-200 hover-scale">
                View All Products
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
            <p class="text-xl text-gray-600">
                Find exactly what you're looking for in our organized categories
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php
            $categories = [
                ['name' => 'Electronics', 'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z', 'color' => 'from-blue-500 to-cyan-500'],
                ['name' => 'Books & Stationery', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'from-green-500 to-emerald-500'],
                ['name' => 'Clothing & Accessories', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', 'color' => 'from-purple-500 to-pink-500'],
                ['name' => 'Sports & Fitness', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'from-orange-500 to-red-500'],
                ['name' => 'Gadgets', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'from-indigo-500 to-purple-500'],
                ['name' => 'Furniture', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'color' => 'from-yellow-500 to-orange-500'],
                ['name' => 'Home & Living', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => 'from-teal-500 to-green-500'],
                ['name' => 'Others', 'icon' => 'M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'color' => 'from-pink-500 to-rose-500']
            ];
            
            foreach ($categories as $category): ?>
                <a href="<?php echo BASE_URL; ?>marketplace.php?category=<?php echo urlencode($category['name']); ?>" 
                   class="group bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover-scale text-center">
                    
                    <!-- Category Icon -->
                    <div class="w-16 h-16 bg-gradient-to-r <?php echo $category['color']; ?> rounded-lg flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $category['icon']; ?>"></path>
                        </svg>
                    </div>
                    
                    <!-- Category Name -->
                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">
                        <?php echo $category['name']; ?>
                    </h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-20 bg-gradient-to-r from-blue-600 to-purple-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-4xl font-bold text-white mb-6">
                Ready to Start Trading?
            </h2>
            <p class="text-xl text-blue-100 mb-8">
                Join thousands of students and teachers who trust CampusMart for their buying and selling needs.
            </p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo BASE_URL; ?>register.php" 
                       class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-200 hover-scale">
                        Create Free Account
                    </a>
                    <a href="<?php echo BASE_URL; ?>marketplace.php" 
                       class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-all duration-200 hover-scale">
                        Browse Products
                    </a>
                </div>
            <?php else: ?>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo BASE_URL; ?>dashboard.php" 
                       class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-200 hover-scale">
                        Start Selling
                    </a>
                    <a href="<?php echo BASE_URL; ?>marketplace.php" 
                       class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-all duration-200 hover-scale">
                        Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Additional CSS for line-clamp -->
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php require_once 'includes/footer.php'; ?>