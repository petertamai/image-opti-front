<?php

declare(strict_types=1); // Enforce strict type checking

namespace App\Controllers;

/**
 * Handles requests for displaying the main application pages/modes.
 */
class IndexController
{
    /**
     * Base path to the templates directory.
     * Calculated relative to this file's location.
     * @var string
     */
    private string $templateBasePath;

    public function __construct()
    {
        // Assumes controllers are in src/Controllers and templates are in /templates
        $this->templateBasePath = dirname(__DIR__, 2) . '/templates/';
    }

    /**
     * Renders a specific view template.
     * Includes the main layout which in turn includes header, footer, and the specific page content.
     *
     * @param string $viewPath Path to the specific page view relative to the templates directory (e.g., 'pages/basic_mode.php').
     * @param array $data Data to be extracted and made available to the view.
     */
    private function renderView(string $viewPath, array $data = []): void
    {
        // Make data available as variables in the view's scope
        extract($data, EXTR_SKIP);

        // Define the path to the actual content file needed by the main layout
        $contentView = $this->templateBasePath . $viewPath;

        // Include the main layout file
        // The main layout file is expected to include header, footer, and $contentView
        $layoutFile = $this->templateBasePath . 'layout/main.php';

        if (file_exists($layoutFile) && file_exists($contentView)) {
            require_once $layoutFile;
        } else {
            // Fallback error handling if template files are missing
            $this->renderError('Template file not found: ' . (!file_exists($layoutFile) ? $layoutFile : $contentView), 500);
        }
    }

    /**
     * Renders an error page.
     *
     * @param string $message Error message to display.
     * @param int $statusCode HTTP status code.
     */
    private function renderError(string $message = 'An unexpected error occurred.', int $statusCode = 500): void
    {
        http_response_code($statusCode);
        // Make data available to the error template
        $errorMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $errorStatusCode = $statusCode; // Pass status code as well

        $errorTemplate = $this->templateBasePath . 'error.php';
        if (file_exists($errorTemplate)) {
            // Include directly as it might be a full page or use its own minimal layout
            require_once $errorTemplate;
        } else {
            // Absolute fallback if error template is missing
            header('Content-Type: text/plain');
            echo "Critical Error: Error template missing. Original error ($statusCode): $errorMessage";
        }
        exit; // Stop execution after rendering an error
    }

    /**
     * Displays the default page (Basic Mode).
     */
    public function home(): void
    {
        $this->basicMode();
    }

    /**
     * Displays the Basic Mode page.
     */
    public function basicMode(): void
    {
        $this->renderView('pages/basic_mode.php', ['pageTitle' => 'Basic Mode']);
    }

    /**
     * Displays the Simple Optimization Mode page.
     */
    public function simpleOptMode(): void
    {
        $this->renderView('pages/simple_opt_mode.php', ['pageTitle' => 'Simple Optimization']);
    }

    /**
     * Displays the Advanced Pipeline Mode page.
     */
    public function advancedMode(): void
    {
        $this->renderView('pages/advanced_mode.php', ['pageTitle' => 'Advanced Pipeline']);
    }

    /**
     * Handles requests that don't match any known route.
     */
    public function notFound(): void
    {
        $this->renderError('Page Not Found', 404);
    }
}