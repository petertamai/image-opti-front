<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Simple wrapper for managing PHP sessions.
 * Ensures session is started and provides basic access methods.
 */
class SessionManager
{
    /**
     * Starts the session if not already active.
     * Configures session cookie parameters for better security.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session cookie parameters before starting
            session_set_cookie_params([
                'lifetime' => 0, // Expire when browser closes
                'path' => '/', // Available on entire domain
                'domain' => '', // Current domain
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Send only over HTTPS
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Lax' // Mitigate CSRF attacks
            ]);
            session_start();
        }
    }

    /**
     * Sets a value in the session.
     *
     * @param string $key The session key.
     * @param mixed $value The value to store.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Gets a value from the session.
     *
     * @param string $key The session key.
     * @param mixed $default Default value to return if key not found.
     * @return mixed The session value or the default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a key exists in the session.
     *
     * @param string $key The session key.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Removes a key from the session.
     *
     * @param string $key The session key to remove.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Regenerates the session ID to prevent session fixation attacks.
     * Should be called after login or privilege level change (though we don't have login here).
     * It's good practice to call it periodically.
     *
     * @param bool $deleteOldSession Whether to delete the old session file.
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Destroys the entire session.
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Unset all session variables
            $_SESSION = [];

            // If session uses cookies, delete the session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000, // Set expiry in the past
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Finally, destroy the session
            session_destroy();
        }
    }

    /**
     * Gets the current session ID.
     *
     * @return string|false Session ID or false if session not active.
     */
    public function getId(): string|false
    {
        return session_id();
    }
}