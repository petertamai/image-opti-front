<?php
// Use global constant if defined, otherwise default
$basePath = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';

// Set default error message and status code if not provided
$errorMessage = $errorMessage ?? 'An unexpected error occurred.';
$errorStatusCode = $errorStatusCode ?? 500;

$pageTitle = "Error " . htmlspecialchars((string)$errorStatusCode, ENT_QUOTES, 'UTF-8');
$isStandalone = !isset($contentView);
?>

<?php if ($isStandalone): ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="<?php echo $basePath; ?>/css/style.css" rel="stylesheet"> <?php // Assume CSS is available ?>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center p-4">
<?php endif; ?>

    <div class="max-w-lg w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 text-center">
        <h1 class="text-6xl font-bold text-red-500 dark:text-red-400 mb-4">
            <?php echo htmlspecialchars((string)$errorStatusCode, ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-3">
            Oops! Something went wrong.
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <a href="<?php echo $basePath; ?>/" class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
            Go Back to Homepage
        </a>
    </div>

<?php if ($isStandalone): ?>
</body>
</html>
<?php endif; ?>