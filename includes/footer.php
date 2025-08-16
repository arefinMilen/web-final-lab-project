<!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <!-- Brand -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-2 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-800">CampusMart</span>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Your trusted campus marketplace for buying and selling products within the university community. 
                        Connect with fellow students and teachers for safe and convenient transactions.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-gray-800 font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-gray-600 hover:text-blue-600 transition-colors">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>marketplace.php" class="text-gray-600 hover:text-blue-600 transition-colors">Marketplace</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo BASE_URL; ?>dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>cart.php" class="text-gray-600 hover:text-blue-600 transition-colors">My Cart</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>login.php" class="text-gray-600 hover:text-blue-600 transition-colors">Login</a></li>
                            <li><a href="<?php echo BASE_URL; ?>register.php" class="text-gray-600 hover:text-blue-600 transition-colors">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-gray-800 font-semibold mb-4">Categories</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo BASE_URL; ?>marketplace.php?category=Electronics" class="text-gray-600 hover:text-blue-600 transition-colors">Electronics</a></li>
                        <li><a href="<?php echo BASE_URL; ?>marketplace.php?category=Books%20%26%20Stationery" class="text-gray-600 hover:text-blue-600 transition-colors">Books & Stationery</a></li>
                        <li><a href="<?php echo BASE_URL; ?>marketplace.php?category=Gadgets" class="text-gray-600 hover:text-blue-600 transition-colors">Gadgets</a></li>
                        <li><a href="<?php echo BASE_URL; ?>marketplace.php?category=Furniture" class="text-gray-600 hover:text-blue-600 transition-colors">Furniture</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-200 mt-8 pt-6 flex flex-col sm:flex-row justify-between items-center">
                <p class="text-gray-500 text-sm">
                    © <?php echo date('Y'); ?> CampusMart. Made with ❤️ for students by students.
                </p>
                <div class="flex space-x-4 mt-4 sm:mt-0">
                    <span class="text-gray-500 text-sm">Currency: BDT (৳)</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Common JavaScript -->
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Auto-hide flash messages after 5 seconds
        const flashMessage = document.querySelector('[role="alert"]');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.transition = 'opacity 0.5s ease-out';
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.remove();
                }, 500);
            }, 5000);
        }

        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
                }
            });
        });

        // Image preview for file inputs
        document.querySelectorAll('input[type="file"]').forEach(input => {
            if (input.accept && input.accept.includes('image')) {
                input.addEventListener('change', function() {
                    const preview = document.getElementById(this.id + '_preview');
                    if (preview && this.files.length > 0) {
                        preview.innerHTML = '';
                        Array.from(this.files).slice(0, 3).forEach((file, index) => {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'w-20 h-20 object-cover rounded-lg border-2 border-gray-200';
                                preview.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        });
                    }
                });
            }
        });
    </script>

</body>
</html>