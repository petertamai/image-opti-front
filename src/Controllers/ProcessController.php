<?php

declare(strict_types=1); // Enforce strict type checking

namespace App\Controllers;

// These classes need to be created and implemented in src/Services and src/Utils
use App\Services\OptimizationService;
use App\Services\ReplicateService;
use App\Utils\FileHandler;
use App\Utils\Validator;
use App\Utils\SessionManager; // Assuming session might be needed for state

/**
 * Handles incoming requests for image processing tasks.
 * Interacts with Services and Utils to perform operations.
 */
class ProcessController
{
    private OptimizationService $optimizationService;
    private ReplicateService $replicateService;
    private FileHandler $fileHandler;
    private Validator $validator;
    private SessionManager $sessionManager; // Example if session needed

    public function __construct()
    {
        // Basic instantiation - In a larger app, use a Dependency Injection container
        // These lines assume the corresponding classes exist and have constructors
        // that don't require arguments or can resolve their own dependencies (like API keys from .env).
        $this->optimizationService = new OptimizationService();
        $this->replicateService = new ReplicateService();
        $this->fileHandler = new FileHandler(); // Needs upload path configuration
        $this->validator = new Validator();
        $this->sessionManager = new SessionManager(); // Starts session if not already started
    }

    /**
     * Sends a JSON response and terminates the script.
     *
     * @param array $data Data to encode as JSON.
     * @param int $statusCode HTTP status code to set.
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit; // Terminate script execution
    }

    /**
     * Sends a standardized error JSON response.
     *
     * @param string $message Error message.
     * @param int $statusCode HTTP status code.
     * @param array|null $details Optional additional error details.
     */
    private function errorResponse(string $message, int $statusCode = 400, ?array $details = null): void
    {
        $response = ['status' => 'error', 'message' => $message];
        if ($details !== null) {
            $response['details'] = $details;
        }
        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Sends a standardized success JSON response.
     *
     * @param array $data Data payload for the success response.
     * @param string|null $message Optional success message.
     */
    private function successResponse(array $data, ?string $message = null): void
    {
        $response = ['status' => 'success'];
        if ($message !== null) {
            $response['message'] = $message;
        }
        $response['data'] = $data; // Embed the actual result data
        $this->jsonResponse($response, 200);
    }

    /**
     * Handles image optimization requests.
     */
    public function optimize(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->errorResponse('Invalid request method. Only POST is allowed.', 405);
        }
    
        // Check for files under either 'images' or 'file' field names
        $uploadedFiles = null;
        if (!empty($_FILES['images'])) {
            $uploadedFiles = $_FILES['images'];
        } else if (!empty($_FILES['file'])) {
            $uploadedFiles = $_FILES['file'];
        } else {
            echo "<pre>ERROR: No image files provided in \$_FILES. Contents: " . print_r($_FILES, true) . "</pre>";
            $this->errorResponse('No image files provided.', 400);
        }
    
        $params = $_POST; // Get optimization parameters (quality, format, etc.)
    
        // 1. Validate uploaded files (using Validator)
        $validationErrors = $this->validator->validateImageUploads($uploadedFiles);
        if (!empty($validationErrors)) {
            $this->errorResponse('Invalid file uploads.', 400, ['errors' => $validationErrors]);
        }
    
        // 2. Validate parameters (using Validator)
        $paramErrors = $this->validator->validateOptimizationParams($params);
        if (!empty($paramErrors)) {
            $this->errorResponse('Invalid optimization parameters.', 400, ['errors' => $paramErrors]);
        }
    
        try {
            // 3. Process uploads (using FileHandler to move to a temporary processing location)
            // FileHandler should return structured data about saved files (e.g., path, original name)
            $processedFilesInfo = $this->fileHandler->processUploads($uploadedFiles);
            if (empty($processedFilesInfo)) {
                $this->errorResponse('Failed to process uploaded files.', 500);
            }
    
            // 4. Call the Optimization Service
            // Pass file paths and validated parameters
            $results = $this->optimizationService->optimizeImages($processedFilesInfo, $params);
    
            // 5. Handle results (potentially clean up original uploads if needed)
            // FileHandler might have a cleanup method
    
            // 6. Return success response with paths/data of optimized images
            $this->successResponse(['results' => $results], 'Images optimized successfully.');
    
        } catch (\Exception $e) {
            // Log the exception for debugging
            error_log('Optimization Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            echo "<pre>ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString() . "</pre>";
            $this->errorResponse('An internal error occurred during optimization: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Handles background removal requests.
     */
    public function removeBackground(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->errorResponse('Invalid request method. Only POST is allowed.', 405);
        }

        if (empty($_FILES['image'])) { // Assuming single file upload with field name 'image'
             $this->errorResponse('No image file provided.', 400);
        }

        $uploadedFile = $_FILES['image'];

        // 1. Validate uploaded file
        $validationErrors = $this->validator->validateImageUpload($uploadedFile); // Method might need adjustment for single file
        if (!empty($validationErrors)) {
            $this->errorResponse('Invalid file upload.', 400, ['errors' => $validationErrors]);
        }

        try {
            // 2. Process upload
            $processedFileInfo = $this->fileHandler->processUpload($uploadedFile); // Method for single file
             if (empty($processedFileInfo)) {
                 $this->errorResponse('Failed to process uploaded file.', 500);
            }

            // 3. Call the Replicate Service
            $result = $this->replicateService->removeBackground($processedFileInfo['path']); // Pass the path

            // 4. Handle result (e.g., save the result image via FileHandler)
            $savedResultPath = $this->fileHandler->saveProcessedImage($result['output_url'] ?? null, 'bg_removed'); // Example

            if (!$savedResultPath) {
                 $this->errorResponse('Failed to retrieve or save processed image.', 500);
            }

            // 5. Return success response
            $this->successResponse(['resultUrl' => $savedResultPath], 'Background removed successfully.'); // Return a local URL/path

        } catch (\Exception $e) {
            // Log the exception
            $this->errorResponse('An internal error occurred during background removal.', 500);
        }
    }

    /**
     * Handles requests to execute an advanced processing pipeline.
     */
    public function runPipeline(): void
    {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->errorResponse('Invalid request method. Only POST is allowed.', 405);
        }

        if (empty($_FILES['images'])) {
             $this->errorResponse('No image files provided for the pipeline.', 400);
        }
        if (empty($_POST['pipeline'])) {
             $this->errorResponse('Pipeline definition not provided.', 400);
        }

        $uploadedFiles = $_FILES['images'];
        $pipelineJson = $_POST['pipeline'];
        $pipelineSteps = json_decode($pipelineJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($pipelineSteps)) {
            $this->errorResponse('Invalid pipeline definition format. Must be valid JSON array.', 400);
        }

        // 1. Validate uploaded files
        $validationErrors = $this->validator->validateImageUploads($uploadedFiles);
        if (!empty($validationErrors)) {
            $this->errorResponse('Invalid file uploads.', 400, ['errors' => $validationErrors]);
        }

        // 2. Validate pipeline structure and parameters (using Validator)
        $pipelineErrors = $this->validator->validatePipeline($pipelineSteps);
        if (!empty($pipelineErrors)) {
            $this->errorResponse('Invalid pipeline definition.', 400, ['errors' => $pipelineErrors]);
        }

        try {
            // 3. Process initial uploads
            $processedFilesInfo = $this->fileHandler->processUploads($uploadedFiles);
             if (empty($processedFilesInfo)) {
                 $this->errorResponse('Failed to process uploaded files.', 500);
            }

            // 4. Execute pipeline steps sequentially
            $currentFiles = $processedFilesInfo; // Start with the initially uploaded files
            $stepResults = [];

            foreach ($pipelineSteps as $index => $step) {
                $operation = $step['operation'] ?? null;
                $params = $step['params'] ?? [];

                // Based on operation, call the appropriate service
                // Each service method should accept current file info and return updated file info
                switch ($operation) {
                    case 'optimize':
                        // Note: Optimization might apply to multiple files
                        $currentFiles = $this->optimizationService->optimizeImages($currentFiles, $params);
                        break;
                    case 'remove_background':
                        // Note: Background removal usually applies to one file at a time
                        // This logic needs refinement based on how multiple files are handled
                        $results = [];
                        foreach($currentFiles as $fileInfo) {
                            $bgRemoveResult = $this->replicateService->removeBackground($fileInfo['path']);
                            // Save result and update file info structure
                            $savedPath = $this->fileHandler->saveProcessedImage($bgRemoveResult['output_url'] ?? null, 'bg_removed_step'.$index);
                            if ($savedPath) {
                                // Update the structure for the next step
                                $results[] = ['path' => $savedPath, 'originalName' => $fileInfo['originalName'] . '_bgremoved'];
                            } else {
                                throw new \RuntimeException("Failed to save background removal result for " . $fileInfo['originalName']);
                            }
                        }
                        $currentFiles = $results; // Update files for the next step
                        break;
                    case 'resize': // Example - Assuming OptimizationService handles this
                         $currentFiles = $this->optimizationService->resizeImages($currentFiles, $params);
                         break;
                    case 'convert': // Example - Assuming OptimizationService handles this
                         $currentFiles = $this->optimizationService->convertImages($currentFiles, $params);
                         break;
                    default:
                        throw new \InvalidArgumentException("Unsupported pipeline operation: {$operation}");
                }
                $stepResults[] = ['step' => $index + 1, 'operation' => $operation, 'output_files' => count($currentFiles)];
            }

            // 5. Return final results
            $finalFilePaths = array_map(fn($file) => $file['path'], $currentFiles); // Extract final paths
            $this->successResponse([
                'pipeline_summary' => $stepResults,
                'final_results' => $finalFilePaths // Or more detailed info
            ], 'Pipeline executed successfully.');

        } catch (\InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400); // Bad request due to invalid operation/params
        } catch (\RuntimeException $e) {
             $this->errorResponse($e->getMessage(), 500); // Service/file handling errors
        } catch (\Exception $e) {
            // Log the exception
            $this->errorResponse('An internal error occurred during pipeline execution.', 500);
        }
    }
}