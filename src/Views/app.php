<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starlight Dominion</title>
    
    <?php if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development'): ?>
       <!--  <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/src/Resources/js/app.js"></script> -->

        <script type="module" src="http://starlightdominion.com:5173/@vite/client"></script>
        <script type="module" src="http://starlightdominion.com:5173/src/Resources/js/app.js"></script>
        
    <?php else: ?>
        <?php 
            $manifestPath = dirname(__DIR__, 2) . '/public/dist/.vite/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $jsFile = $manifest['src/Resources/js/app.js']['file'];
                $cssFiles = $manifest['src/Resources/js/app.js']['css'] ?? [];
                
                foreach ($cssFiles as $css) {
                    echo '<link rel="stylesheet" href="/dist/' . $css . '">';
                }
                echo '<script type="module" src="/dist/' . $jsFile . '"></script>';
            }
        ?>
    <?php endif; ?>
</head>
<body class="bg-[#0a0a0a] text-gray-300 antialiased overflow-x-hidden">
    <div id="app"></div>
    
    <script>
        // Use json_encode to prevent "Array" string conversion and handle XSS safety
        window.__INITIAL_STATE__ = <?= json_encode($state ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__CSRF_TOKEN__ = "<?= \sdo\Infrastructure\Csrf::getToken() ?>";
    </script>
</body>
</html>