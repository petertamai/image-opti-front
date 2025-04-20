<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Interacts with the Replicate API (api.replicate.com)
 * Handles starting predictions and polling for results.
 */
class ReplicateService
{
    private const API_BASE_URL = 'https://api.replicate.com/v1';
    // IMPORTANT: Choose the correct model and version for background removal
    // Example using 'cjwbw/rembg'. Find the latest version on Replicate.
    private const BACKGROUND_REMOVAL_MODEL = 'cjwbw/rembg';
    private const MODEL_VERSION = 'fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003'; // Example version for cjwbw/rembg

    private string $apiToken;
    private const PREDICTION_TIMEOUT = 120; // Max seconds to wait for prediction completion
    private const POLL_INTERVAL = 3; // Seconds between polling requests

    public function __construct()
    {
        $this->apiToken = getenv('REPLICATE_API_TOKEN') ?: '';

        if (empty($this->apiToken)) {
            // In production, log this error critically
            throw new \RuntimeException("Replicate API token is not configured in environment variables (REPLICATE_API_TOKEN).");
        }
    }

    /**
     * Makes a request to the Replicate API using curl.
     *
     * @param string $method HTTP method ('GET', 'POST').
     * @param string $url Full API URL.
     * @param array|null $data Data payload for POST requests (will be JSON encoded).
     * @return array Decoded JSON response.
     * @throws \RuntimeException If the API call fails.
     */
    private function makeApiRequest(string $method, string $url, ?array $data = null): array
    {
        $curlHandle = curl_init();
        $headers = [
            'Authorization: Token ' . $this->apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Timeout for individual HTTP request
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (strtoupper($method) === 'POST') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'POST';
            if ($data !== null) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } else {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'GET';
        }

        // If using self-signed certs (dev), uncomment below. NOT recommended for production.
        // $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        // $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;

        curl_setopt_array($curlHandle, $curlOptions);

        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $error = curl_error($curlHandle);
        curl_close($curlHandle);

        if ($error) {
            // Log curl error: $error
            throw new \RuntimeException("Replicate API request failed (curl): " . $error);
        }

        if ($httpCode >= 400) {
            // Log API error: $httpCode, $response
            throw new \RuntimeException("Replicate API request failed with status code {$httpCode}. Response: " . $response);
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log JSON decode error: json_last_error_msg(), $response
            throw new \RuntimeException("Failed to decode Replicate API JSON response. Response: " . $response);
        }

        return $decodedResponse;
    }

    /**
     * Removes the background from an image using a Replicate model.
     *
     * @param string $imagePath Path to the local image file.
     * @return array Result structure containing the output URL on success.
     * @throws \RuntimeException If the process fails.
     * @throws \InvalidArgumentException If the file is invalid.
     */
    public function removeBackground(string $imagePath): array
    {
        if (!file_exists($imagePath) || !is_readable($imagePath)) {
            throw new \InvalidArgumentException("File does not exist or is not readable: " . $imagePath);
        }

        // Convert image to data URI for Replicate input
        // Note: Max payload size limits apply. For very large images, uploading first might be needed.
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
             throw new \RuntimeException("Failed to read image file: " . $imagePath);
        }
        $mimeType = mime_content_type($imagePath) ?: 'image/png'; // Default or detect
        $base64Image = base64_encode($imageData);
        $dataUri = "data:{$mimeType};base64,{$base64Image}";

        // 1. Start Prediction
        $predictionUrl = self::API_BASE_URL . '/predictions';
        $inputData = [
            'version' => self::MODEL_VERSION,
            'input' => [
                // Input parameters depend on the specific model (cjwbw/rembg)
                'image' => $dataUri
                // Add other model-specific parameters here if needed
                // 'alpha_matting' => true,
                // 'alpha_matting_foreground_threshold' => 240,
                // ... etc. Check model documentation on Replicate.
            ],
            // Optional: Add webhook URL here for asynchronous handling
            // 'webhook': 'YOUR_WEBHOOK_URL',
            // 'webhook_events_filter': ['completed']
        ];

        try {
            $startResponse = $this->makeApiRequest('POST', $predictionUrl, $inputData);
        } catch (\Exception $e) {
             // Log error
             throw new \RuntimeException("Failed to start Replicate prediction: " . $e->getMessage());
        }


        if (empty($startResponse['id']) || empty($startResponse['urls']['get'])) {
             // Log unexpected response: json_encode($startResponse)
            throw new \RuntimeException("Replicate API did not return a valid prediction ID or status URL.");
        }

        $statusUrl = $startResponse['urls']['get']; // Use the specific GET URL provided

        // 2. Poll for Result
        $startTime = time();
        while (time() - $startTime < self::PREDICTION_TIMEOUT) {
            sleep(self::POLL_INTERVAL); // Wait before checking status

            try {
                 $statusResponse = $this->makeApiRequest('GET', $statusUrl);
            } catch (\Exception $e) {
                // Log polling error, maybe retry a few times?
                // For now, we fail the process.
                 throw new \RuntimeException("Failed to poll Replicate prediction status: " . $e->getMessage());
            }


            $status = $statusResponse['status'] ?? 'unknown';

            if ($status === 'succeeded') {
                if (empty($statusResponse['output'])) {
                     // Log missing output: json_encode($statusResponse)
                     throw new \RuntimeException("Replicate prediction succeeded but output is missing.");
                }
                // Success! Return the result structure
                // The structure of 'output' depends on the model.
                // For rembg, it's typically a URL to the output image.
                return [
                    'status' => 'success',
                    'output_url' => $statusResponse['output'] // Assuming output is the direct URL string
                ];
            } elseif ($status === 'failed' || $status === 'canceled') {
                $errorDetails = $statusResponse['error'] ?? 'No error details provided.';
                 // Log failure: $status, $errorDetails
                throw new \RuntimeException("Replicate prediction {$status}. Error: " . $errorDetails);
            }

            // Continue polling if status is 'starting' or 'processing'
        }

        // If loop finishes without success/failure, it timed out
        throw new \RuntimeException("Replicate prediction timed out after " . self::PREDICTION_TIMEOUT . " seconds.");
    }
}