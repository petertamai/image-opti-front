<?php

declare(strict_types=1);

// Define Base Path (Project Root) - Assuming index.php is in /public
define('BASE_PATH', dirname(__DIR__));
define('APP_BASE_PATH', '/opti'); // <--- ADD THIS
// --- 1. Simple Autoloader (PSR-4ish) ---
// For production, using Composer (vendor/autoload.php) is highly recommended.
spl_autoload_register(function ($class) {
    $prefix = 'App\\'; // Your application's namespace prefix
    $base_dir = BASE_PATH . '/src/'; // Base directory for your source files

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Not a class from our application namespace
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators and add .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    } else {
        // Optional: Log or throw an error if class file not found
        error_log("Autoload Error: Class file not found for {$class} at {$file}");
    }
});

// --- 2. Basic .env File Loader ---
// Loads variables into getenv(), $_ENV, $_SERVER
function loadEnv(string $path): void {
    if (!file_exists($path) || !is_readable($path)) {
        error_log(".env file not found or not readable at {$path}");
        // Depending on requirements, you might want to throw an exception here
        // if the .env file is absolutely critical.
        // throw new RuntimeException(".env file not found or not readable.");
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and invalid lines
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes (single or double)
        if (preg_match('/^(\'(.*)\'|"(.*)")$/', $value, $matches)) {
            $value = $matches[2] ?? $matches[3] ?? ''; // Use captured group inside quotes
        }

        if (!empty($name)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value; // Make available in $_SERVER as well
        }
    }
}

// Load the .env file from the project root
loadEnv(BASE_PATH . '/.env');

// --- 3. Error and Exception Handling ---
// Adjust error reporting based on environment (e.g., from .env)
$appEnv = getenv('APP_ENV') ?: 'production'; // Default to production

if ($appEnv === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0); // Turn off reporting for production (log instead)
    // TODO: Set up proper logging for production errors
    // set_error_handler(...)
    // set_exception_handler(...)
}

// --- 4. Session Start (via SessionManager) ---
// The SessionManager constructor will handle starting the session if needed.
// We instantiate it here or rely on controllers instantiating it.
// For simplicity, let controllers handle it via dependency injection (manual for now).
// use App\Utils\SessionManager;
// $session = new SessionManager(); // Optional: Start session globally if needed early

// --- 5. Routing ---
use App\Controllers\IndexController;
use App\Controllers\ProcessController;

// Determine the route based on query parameters
$page = $_GET['page'] ?? null;     // For displaying different UI modes
$action = $_GET['action'] ?? null; // For handling processing requests (usually POST)

$controller = null;
$method = null;

if ($action) {
    // Actions are handled by ProcessController
    $controller = new ProcessController();
    switch ($action) {
        case 'optimize':
            $method = 'optimize';
            break;
        case 'remove_bg':
            $method = 'removeBackground';
            break;
        case 'run_pipeline':
            $method = 'runPipeline';
            break;
        default:
            // Unknown action - Treat as Not Found or specific error
            $controller = new IndexController(); // Use IndexController for error display
            $method = 'notFound'; // Assumes IndexController has a notFound method
            break;
    }
} else {
    // Pages are handled by IndexController
    $controller = new IndexController();
    switch ($page) {
        case 'basic':
            $method = 'basicMode';
            break;
        case 'simple':
            $method = 'simpleOptMode';
            break;
        case 'advanced':
            $method = 'advancedMode';
            break;
        case null: // No page specified, default to home/basic mode
        default: // Unknown page, default to home or show 404
            $method = 'home'; // Assumes home() defaults to basic or handles routing
            // If you want a strict 404 for unknown pages:
            // if ($page !== null) { $method = 'notFound'; } else { $method = 'home'; }
            break;
    }
}

// --- 6. Dispatch Request ---
if ($controller && $method && method_exists($controller, $method)) {
    try {
        // Call the determined controller method
        $controller->$method();
    } catch (\Throwable $e) {
        // Catch any uncaught exceptions from controllers/services
        error_log("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());

        // Display a generic error page in production, detailed in development
        if ($appEnv === 'development') {
            // Re-throw for detailed error page (if PHP display_errors is on)
             echo "<h1>Error</h1><p>Uncaught Exception:</p><pre>";
             echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
             echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
             echo "</pre>";
        } else {
            // Attempt to show a user-friendly error page using the controller
            try {
                // Check if IndexController exists and has an error rendering method
                 if (class_exists(IndexController::class)) {
                    $errorController = new IndexController();
                    if (method_exists($errorController, 'renderError')) { // Assuming renderError exists
                         // Call renderError directly if possible, otherwise call a generic error method
                         // Need to adjust based on IndexController's error method signature
                         // $errorController->renderError('An internal server error occurred.', 500);
                         // Fallback to a simpler method if renderError isn't suitable here
                         if (method_exists($errorController, 'error')) {
                            $errorController->error('An internal server error occurred.', 500);
                         } else {
                             http_response_code(500);
                             echo "An internal server error occurred."; // Absolute fallback
                         }
                    } else {
                         http_response_code(500);
                         echo "An internal server error occurred."; // Absolute fallback
                    }
                 } else {
                     http_response_code(500);
                     echo "An internal server error occurred."; // Absolute fallback
                 }

            } catch (\Throwable $errorDisplayException) {
                // If even the error display fails...
                http_response_code(500);
                echo "A critical internal server error occurred."; // Final fallback message
                error_log("Critical Error: Failed to display error page. Initial Exception: " . $e->getMessage());
                error_log("Error Display Exception: " . $errorDisplayException->getMessage());
            }
        }
        exit; // Stop script execution after handling the error
    }
} else {
    // If routing failed to find a valid controller/method (should ideally be caught by routing logic)
    error_log("Routing Error: No valid route found for page='{$page}', action='{$action}'");
    // Display a 404 Not Found error
    if (class_exists(IndexController::class) && method_exists(IndexController::class, 'notFound')) {
         $errorController = new IndexController();
         $errorController->notFound();
    } else {
        http_response_code(404);
        echo "404 Not Found"; // Fallback 404
    }
    exit;
}