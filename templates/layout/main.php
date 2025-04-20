<?php
// Use the globally defined constant
// $appBasePath = '/opti'; // Remove this line if you added it before

// Set default page title if not provided by the controller
$pageTitle = $pageTitle ?? 'ImgTasks - Simplified Image Processing';

// Ensure contentView path is set by the controller (critical for rendering)
if (!isset($contentView) || !file_exists($contentView)) {
    http_response_code(500);
    error_log("Critical Error: ContentView not set or file missing in main.php. Path: " . ($contentView ?? 'Not Set'));
    echo "Error: Cannot render page content. Please contact support.";
    exit;
}

// Define paths to header and footer templates
$headerTemplate = dirname(__DIR__) . '/layout/header.php';
$footerTemplate = dirname(__DIR__) . '/layout/footer.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Favicon Links -->
    <link rel="icon" href="<?php echo APP_BASE_PATH; ?>/assets/images/favicon.ico" sizes="any">
    <link rel="icon" href="<?php echo APP_BASE_PATH; ?>/assets/images/favicon.svg" type="image/svg+xml">

    <!-- Vendor CSS -->
    <link href="<?php echo APP_BASE_PATH; ?>/css/dropzone.min.css" rel="stylesheet" type="text/css" />

    <!-- Application CSS (Tailwind Output) -->
    <link href="<?php echo APP_BASE_PATH; ?>/css/style.css" rel="stylesheet">

</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans antialiased flex flex-col">

    <?php
    // Include the site header
    if (file_exists($headerTemplate)) {
        require $headerTemplate;
    } else {
        error_log("Error: Header template not found at " . $headerTemplate);
        echo "<!-- Header missing -->";
    }
    ?>

    <!-- Main Content Area -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php require $contentView; ?>
    </main>

    <?php
    // Include the site footer
     if (file_exists($footerTemplate)) {
        require $footerTemplate;
    } else {
        error_log("Error: Footer template not found at " . $footerTemplate);
        echo "<!-- Footer missing -->";
    }
    ?>
<script>
    // Make PHP's APP_BASE_PATH available to JavaScript
    var APP_BASE_PATH = '<?php echo APP_BASE_PATH; ?>';
</script>

    <!-- Vendor JavaScript -->
    <script src="<?php echo APP_BASE_PATH; ?>/js/vendor/jquery.min.js"></script>
    <script src="<?php echo APP_BASE_PATH; ?>/js/vendor/dropzone.min.js"></script>
    <script src="<?php echo APP_BASE_PATH; ?>/js/vendor/Sortable.min.js"></script>
    <script src="<?php echo APP_BASE_PATH; ?>/js/vendor/sweetalert2.all.min.js"></script>
    <script src="<?php echo APP_BASE_PATH; ?>/js/vendor/progressbar.min.js"></script>

    <!-- Application JavaScript -->
    <script src="<?php echo APP_BASE_PATH; ?>/js/app.js"></script>

</body>
</html>