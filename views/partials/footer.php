    </main>
    <?php require __DIR__ . '/cookie-banner.php'; ?>
    <footer>
        <div class="container" style="text-align: center; padding: 2rem 1rem;">
            <div class="nav-links" style="justify-content: center; margin-bottom: 1rem;">
                <a href="/terms">Terms & Conditions</a>
                <a href="/privacy">Privacy Policy</a>
                <a href="#" id="manage-cookies-link" style="cursor: pointer;">Manage Cookies</a>
            </div>
            <p style="margin-bottom: 0.5rem; text-align: center;">
                © <?= date('Y') ?> <strong><?= html($appConfig['name']) ?></strong>
            </p>
            <p style="font-size: 0.85rem; color: var(--color-text-muted); text-align: center;">
            © VVAIStudio.com, 2025-2026 | Built with ❤️
            </p>
        </div>
    </footer>
    <script src="/assets/js/app.js"></script>
</body>
</html>

