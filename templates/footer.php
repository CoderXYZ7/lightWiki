</main>
    <div class="footer-section">
        <p class="footer-text">&copy; <?php echo date(
            "Y",
        ); ?> <?php echo htmlspecialchars(
     $config["site_title"],
 ); ?>. Powered by LightWiki.</p>
    </div>
    <script src="<?php echo $config["js_path"]; ?>"></script>
    <script>
        // Initialize Mermaid
        mermaid.initialize(<?php echo json_encode(
            $markdown->getMermaidConfig(),
        ); ?>);

        // Initialize syntax highlighting
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure all content is loaded
            setTimeout(function() {
                Prism.highlightAll();
            }, 100);
        });

        // Also try to highlight on window load as fallback
        window.addEventListener('load', function() {
            setTimeout(function() {
                Prism.highlightAll();
            }, 200);
        });
    </script>
</body>
</html>
