<?php

declare(strict_types=1);

namespace App\Services;

use CURLFile; // Required for sending files with curl

/**
 * Interacts with the image optimization API at imgopt.petertam.pro.
 */
class OptimizationService
{
    private string $baseUrl;
    private string $apiKey;
    private const TIMEOUT = 60; // Seconds for API call timeout

    public function __construct()
    {
        $this->baseUrl = rtrim(getenv('IMAGE_OPTIMIZATION_SERVICE_BASE_URL') ?: 'https://imgopt.petertam.pro', '/');
        $this->apiKey = getenv('IMAGE_OPTIMIZATION_API_KEY') ?: 'key';

        
        if (empty($this->baseUrl)) {
            throw new \RuntimeException("Optimization Service base URL is not configured in environment variables (IMAGE_OPTIMIZATION_SERVICE_BASE_URL).");
        }
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Optimization Service API key is not configured in environment variables (IMAGE_OPTIMIZATION_API_KEY).");
        }
    }

    /**
     * Makes a request to the optimization service.
     *
     * @param string $endpoint The API endpoint (e.g., '/optimize' or '/pipeline').
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
                'X-API-Key: ' . $this->apiKey,
                'Accept: application/json',
            ],
            // For development/debugging:
            CURLOPT_VERBOSE => true,
            // If your API uses self-signed certs (dev environment), uncomment below:
            // CURLOPT_SSL_VERIFYPEER => false,
            // CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $error = curl_error($curlHandle);
        
        if ($error) {
        }
        
        curl_close($curlHandle);

        if ($error) {
            throw new \RuntimeException("API request failed (curl): " . $error);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("API request failed with status code {$httpCode}. Response: " . $response);
        }

        // Try to decode as JSON
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to decode API JSON response. Response: " . $response);
        }

        // Check for application-level errors in the response structure
        if (isset($decodedResponse['status']) && isset($decodedResponse['status']['code']) && $decodedResponse['status']['code'] < 0) {
            $errorMessage = $decodedResponse['status']['message'] ?? 'Unknown API error';
            throw new \RuntimeException("API returned an error: " . $errorMessage);
        }

        return $decodedResponse;
    }

    /**
     * Optimizes multiple images using the optimization API.
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
                // Convert parameters to the format expected by the API
                $apiParams = [
                    'quality' => $params['quality'] ?? 80,
                    'convertto' => $params['format'] ?? 'webp',
                ];
                
                // Handle resize parameters if provided
                if (isset($params['width']) && !empty($params['width']) || 
                    isset($params['height']) && !empty($params['height'])) {
                    $apiParams['resize'] = 1; // Use 'contain' method by default
                    if (isset($params['width']) && !empty($params['width'])) {
                        $apiParams['resize_width'] = $params['width'];
                    }
                    if (isset($params['height']) && !empty($params['height'])) {
                        $apiParams['resize_height'] = $params['height'];
                    }
                }
                
                // Determine which API endpoint to use
                $endpoint = '/optimize';
                
                $apiResult = $this->makeApiRequest($endpoint, $fileInfo['path'], $apiParams);
                
                // Process the API response
                if (isset($apiResult['downloadUrl'])) {
                    // If there's a download URL, fetch and save the optimized image
                    $savedPath = $this->downloadOptimizedImage($apiResult['downloadUrl'], basename($fileInfo['path']));
                    if ($savedPath) {
                        $result = [
                            'path' => $savedPath,
                            'url' => str_replace(dirname(dirname(dirname(__DIR__))), '', $savedPath), // Convert to relative URL
                            'originalName' => $fileInfo['originalName'],
                            'originalSize' => $fileInfo['size'],
                            'processedSize' => $apiResult['processedSize'] ?? null,
                            'compressionRatio' => $apiResult['compressionRatio'] ?? null,
                            'format' => $apiResult['format'] ?? null,
                            'width' => $apiResult['width'] ?? null,
                            'height' => $apiResult['height'] ?? null,
                        ];
                    } else {
                        $result = array_merge($fileInfo, ['error' => 'Failed to download optimized image']);
                    }
                } else {
                    // If no download URL, return the API result directly
                    $result = array_merge($fileInfo, ['optimizationResult' => $apiResult]);
                }
                
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = array_merge($fileInfo, ['error' => $e->getMessage()]);
            }
        }
        return $results;
    }

    /**
     * Downloads an optimized image from the provided URL and saves it.
     *
     * @param string $url The URL to download the optimized image from.
     * @param string $originalFilename The original filename for reference.
     * @return string|null The path to the saved file, or null on failure.
     */
    private function downloadOptimizedImage(string $url, string $originalFilename): ?string
    {
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($data)) {
            return null;
        }
        
        // Generate a filename for the optimized image
        $extension = pathinfo($url, PATHINFO_EXTENSION) ?: pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'optimized_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Determine the save path (uploads directory)
        $savePath = dirname(dirname(__DIR__)) . '/uploads/' . $filename;
        
        // Ensure uploads directory exists
        $uploadsDir = dirname($savePath);
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }
        
        // Save the file
        if (file_put_contents($savePath, $data) === false) {
            return null;
        }
        
        return $savePath;
    }

    /**
     * Resizes multiple images using the API.
     *
     * @param array $filesInfo Array of file info maps, each containing at least 'path'.
     * @param array $params Resize parameters (e.g., width, height, mode).
     * @return array Array of results.
     */
    public function resizeImages(array $filesInfo, array $params): array
    {
        // Map resize modes to API values
        $modes = [
            'fit' => 1,    // contain
            'cover' => 2,  // cover
            'fill' => 3,   // fill
        ];
        
        $apiParams = [
            'resize' => $modes[$params['mode'] ?? 'fit'] ?? 1,
            'resize_width' => $params['width'] ?? null,
            'resize_height' => $params['height'] ?? null,
        ];
        
        // Remove null values
        $apiParams = array_filter($apiParams, function($value) {
            return $value !== null;
        });
        
        return $this->optimizeImages($filesInfo, $apiParams);
    }

    /**
     * Converts multiple images using the API.
     *
     * @param array $filesInfo Array of file info maps, each containing at least 'path'.
     * @param array $params Conversion parameters (e.g., format).
     * @return array Array of results.
     */
    public function convertImages(array $filesInfo, array $params): array
    {
        $apiParams = [
            'convertto' => $params['format'] ?? 'webp',
            'quality' => $params['quality'] ?? 80,
        ];
        
        return $this->optimizeImages($filesInfo, $apiParams);
    }
}