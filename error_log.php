<?php
echo "<h1>PHP Error Log</h1>";
echo "<pre>";
$error_log_path = ini_get('error_log');
echo "Error log path: " . $error_log_path . "\n\n";

if (file_exists($error_log_path)) {
    $log_content = file_get_contents($error_log_path);
    echo htmlspecialchars($log_content);
} else {
    echo "Error log file not found or not accessible.";
    
    // Try to get the last errors from the system
    $logs = [];
    
    // Try common log locations
    $possible_logs = [
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        '/var/log/php/error.log',
        '/var/log/php-fpm/error.log',
        dirname(__FILE__) . '/logs/error.log'
    ];
    
    foreach ($possible_logs as $log) {
        if (file_exists($log) && is_readable($log)) {
            echo "\nFound log file: $log\n";
            $content = shell_exec("tail -n 50 $log");
            echo htmlspecialchars($content) . "\n\n";
        }
    }
}
echo "</pre>";