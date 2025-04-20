<?php

declare(strict_types=1);

namespace App\Services;

use CURLFile; // Required for sending files with curl

/**
 * Interacts with a custom image optimization API.
 *
 * Assumes the API:
 * - Is reachable at IMAGE_OPTIMIZATION_SERVICE_BASE_URL.
 * - Requires an API key via IMAGE_OPTIMIZATION_API_KEY (e.g., in a header).
 * - Has endpoints like /optimize, /resize, /convert.
 * - Accepts POST requests with multipart/form-data including an 'image' file field.
 * - Returns JSON responses (e.g., {"status": "success", "outputUrl": "...", "outputPath": "..."} or {"status": "error", "message": "..."}).
 */
class OptimizationService
{
    private string $baseUrl;
    private string $apiKey;
    private const TIMEOUT = 30; // Seconds for API call timeout

    public function __construct()
    {
        $this->baseUrl = rtrim(getenv('IMAGE_OPTIMIZATION_SERVICE_BASE_URL') ?: '', '/');
        $this->apiKey = getenv('IMAGE_OPTIMIZATION_API_KEY') ?: '';

        if (empty($this->baseUrl)) {
            // In production, log this error critically
            throw new \RuntimeException("Optimization Service base URL is not configured in environment variables (IMAGE_OPTIMIZATION_SERVICE_BASE_URL).");
        }
        if (empty($this->apiKey)) {
            // In production, log this error critically
            throw new \RuntimeException("Optimization Service API key is not configured in environment variables (IMAGE_OPTIMIZATION_API_KEY).");
        }
    }

    /**
     * Makes a request to a specific endpoint of the optimization service.
     *
     * @param string $endpoint The API endpoint (e.g., '/optimize').
     * @param string $filePath The path to the local image file to process.
     * @param array $params Additional parameters for the API call.
     * @return array Decoded JSON response from the API.
     * @throws \RuntimeException If the API call fails or returns an error status.
     */
    private function makeApiRequest(string $endpoint, string $filePath, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("File does not exist or is not readable: " . $filePath);
        }

        $curlHandle = curl_init();

        // Prepare POST data (multipart/form-data)
        $postData = $params; // Start with text parameters
        // Create CURLFile object for upload
        $postData['image'] = new CURLFile(
            $filePath,
            mime_content_type($filePath) ?: 'application/octet-stream', // Guess mime type
            basename($filePath) // Send original filename
        );

        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, // Return response as string
            CURLOPT_ENCODING => '', // Handle all encodings
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData, // Send as multipart/form-data
            CURLOPT_HTTPHEADER => [
                // Add API Key header - Adjust header name ('X-API-Key') if your API uses a different one
                'X-API-Key: ' . $this->apiKey,
                // 'Accept: application/json', // Optional: Specify expected response type
            ],
            // If your API uses self-signed certs (dev environment), uncomment below. NOT recommended for production.
            // CURLOPT_SSL_VERIFYPEER => false,
            // CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $error = curl_error($curlHandle);
        curl_close($curlHandle);

        if ($error) {
            // Log curl error: $error
            throw new \RuntimeException("API request failed (curl): " . $error);
        }

        if ($httpCode >= 400) {
             // Log API error: $httpCode, $response
            throw new \RuntimeException("API request failed with status code {$httpCode}. Response: " . $response);
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             // Log JSON decode error: json_last_error_msg(), $response
            throw new \RuntimeException("Failed to decode API JSON response. Response: " . $response);
        }

        // Check for application-level errors in the response structure (adjust based on your API)
        if (isset($decodedResponse['status']) && $decodedResponse['status'] === 'error') {
            $errorMessage = $decodedResponse['message'] ?? 'Unknown API error';
            // Log application error: $errorMessage
            throw new \RuntimeException("API returned an error: " . $errorMessage);
        }
        if (!isset($decodedResponse['status']) || $decodedResponse['status'] !== 'success') {
             // Log unexpected structure: $response
             throw new \RuntimeException("API response format unexpected or indicates failure.");
        }


        return $decodedResponse;
    }

    /**
     * Optimizes multiple images using the custom API.
     *
     * @param array $filesInfo Array of file info maps, each containing at least 'path'.
     * @param array $params Optimization parameters (e.g., quality, format).
     * @return array Array of results, potentially containing 'outputUrl' or 'outputPath' for each file.
     */
    public function optimizeImages(array $filesInfo, array $params): array
    {
        $results = [];
        foreach ($filesInfo as $fileInfo) {
            if (!isset($fileInfo['path'])) continue;
            try {
                // Adjust endpoint and parameters as needed by your API
                $apiResult = $this->makeApiRequest('/optimize', $fileInfo['path'], $params);
                // Store result, associating it with the original file if possible
                $results[] = array_merge($fileInfo, ['optimizationResult' => $apiResult]);
            } catch (\Exception $e) {
                // Log error for this specific file: $e->getMessage()
                $results[] = array_merge($fileInfo, ['error' => $e->getMessage()]);
            }
        }
        return $results;
    }

    /**
     * Resizes multiple images using the custom API.
     *
     * @param array $filesInfo Array of file info maps, each containing at least 'path'.
     * @param array $params Resize parameters (e.g., width, height, mode).
     * @return array Array of results.
     */
    public function resizeImages(array $filesInfo, array $params): array
    {
        $results = [];
        foreach ($filesInfo as $fileInfo) {
             if (!isset($fileInfo['path'])) continue;
            try {
                // Adjust endpoint and parameters as needed by your API
                $apiResult = $this->makeApiRequest('/resize', $fileInfo['path'], $params);
                $results[] = array_merge($fileInfo, ['resizeResult' => $apiResult]);
            } catch (\Exception $e) {
                $results[] = array_merge($fileInfo, ['error' => $e->getMessage()]);
            }
        }
        return $results;
    }

    /**
     * Converts multiple images using the custom API.
     *
     * @param array $filesInfo Array of file info maps, each containing at least 'path'.
     * @param array $params Conversion parameters (e.g., format).
     * @return array Array of results.
     */
    public function convertImages(array $filesInfo, array $params): array
    {
         $results = [];
        foreach ($filesInfo as $fileInfo) {
             if (!isset($fileInfo['path'])) continue;
            try {
                // Adjust endpoint and parameters as needed by your API
                $apiResult = $this->makeApiRequest('/convert', $fileInfo['path'], $params);
                $results[] = array_merge($fileInfo, ['convertResult' => $apiResult]);
            } catch (\Exception $e) {
                $results[] = array_merge($fileInfo, ['error' => $e->getMessage()]);
            }
        }
        return $results;
    }
}