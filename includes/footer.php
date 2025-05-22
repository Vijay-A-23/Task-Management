    </main>
    
    <footer class="bg-white dark:bg-gray-800 py-6">
        <div class="container mx-auto px-4">
            <div class="text-center text-gray-600 dark:text-gray-400">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Dark mode toggle script -->
    <script>
        document.getElementById('darkModeToggle').addEventListener('click', function() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', !isDarkMode);
        });
    </script>
    
    <!-- Custom JavaScript -->
    <script src="<?= APP_URL ?>/js/main.js"></script>
    
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
