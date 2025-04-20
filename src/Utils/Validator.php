<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Provides methods for validating input data like uploaded files and parameters.
 */
class Validator
{
    // Re-use constants from FileHandler or define separately
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    // Allowed operations and basic parameter types for pipeline validation
    private const ALLOWED_PIPELINE_OPERATIONS = ['optimize', 'remove_background', 'resize', 'convert'];
    private const PIPELINE_PARAM_TYPES = [
        'optimize' => ['quality' => 'numeric', 'format' => 'string'],
        'remove_background' => [], // No specific params expected here usually
        'resize' => ['width' => 'numeric', 'height' => 'numeric', 'mode' => 'string'],
        'convert' => ['format' => 'string'],
    ];

    /**
     * Validates a single file upload entry from $_FILES.
     * Checks for upload errors, size, and MIME type.
     *
     * @param array $fileUploadEntry An entry from $_FILES.
     * @param string $fieldName The name of the form field for error messages.
     * @return array Array of error messages, empty if valid.
     */
    public function validateImageUpload(array $fileUploadEntry, string $fieldName = 'file'): array
    {
        $errors = [];

        // Check PHP upload errors
        if (!isset($fileUploadEntry['error']) || is_array($fileUploadEntry['error'])) {
            $errors[] = "Invalid upload data structure for field '{$fieldName}'.";
            return $errors; // Cannot proceed further
        }

        switch ($fileUploadEntry['error']) {
            case UPLOAD_ERR_OK:
                break; // No upload error, continue validation
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "No file uploaded for field '{$fieldName}'.";
                return $errors; // Stop validation if no file
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "File '{$fileUploadEntry['name']}' for field '{$fieldName}' exceeds the maximum allowed size.";
                return $errors; // Stop validation if too large based on server/form config
            default:
                $errors[] = "An unknown error occurred during upload for file '{$fileUploadEntry['name']}' (field '{$fieldName}', error code: {$fileUploadEntry['error']}).";
                return $errors; // Stop validation on unknown error
        }

        // Check application file size limit
        if ($fileUploadEntry['size'] > self::MAX_FILE_SIZE) {
            $errors[] = "File '{$fileUploadEntry['name']}' exceeds the application size limit (" . (self::MAX_FILE_SIZE / 1024 / 1024) . " MB).";
        }

        // Check MIME type using finfo (more reliable than ['type'])
        if (empty($fileUploadEntry['tmp_name']) || !file_exists($fileUploadEntry['tmp_name'])) {
             $errors[] = "Uploaded file '{$fileUploadEntry['name']}' temporary data is missing or inaccessible.";
             return $errors; // Cannot check MIME type
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileUploadEntry['tmp_name']);
        finfo_close($finfo);

        if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = "File '{$fileUploadEntry['name']}' has an invalid type ('{$mimeType}'). Allowed types: " . implode(', ', self::ALLOWED_MIME_TYPES);
        }

        return $errors;
    }

    /**
     * Validates multiple uploaded files based on the $_FILES structure.
     *
     * @param array $filesUploadEntry The entry from $_FILES for multiple uploads (e.g., $_FILES['images']).
     * @param string $fieldName The base name of the form field (e.g., 'images').
     * @return array Associative array where keys are original filenames and values are arrays of errors for that file. Empty if all valid.
     */
    public function validateImageUploads(array $filesUploadEntry, string $fieldName = 'images'): array
    {
        $allErrors = [];

        if (!isset($filesUploadEntry['name']) || !is_array($filesUploadEntry['name'])) {
            // Check if it's a single file upload passed incorrectly
            if (isset($filesUploadEntry['name']) && !is_array($filesUploadEntry['name']) && $filesUploadEntry['error'] !== UPLOAD_ERR_NO_FILE) {
                 $singleErrors = $this->validateImageUpload($filesUploadEntry, $fieldName);
                 if (!empty($singleErrors)) {
                     $allErrors[$filesUploadEntry['name'] ?? 'unknown_file'] = $singleErrors;
                 }
                 return $allErrors;
            } else {
                // Neither single nor multiple structure, or just empty
                if (empty($filesUploadEntry['name']) || $filesUploadEntry['error'][0] === UPLOAD_ERR_NO_FILE) {
                    // This might be acceptable depending on context, controller should check if files are required
                    return []; // No files uploaded is not necessarily a validation error here
                }
                $allErrors['general'] = ["Invalid file upload data structure for field '{$fieldName}'."];
                return $allErrors;
            }
        }


        $numFiles = count($filesUploadEntry['name']);
        $fileKeys = array_keys($filesUploadEntry);

        for ($i = 0; $i < $numFiles; $i++) {
            $singleFileEntry = [];
            foreach ($fileKeys as $key) {
                $singleFileEntry[$key] = $filesUploadEntry[$key][$i] ?? null;
            }

            // Skip empty file inputs often created by browsers
            if ($singleFileEntry['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $fileErrors = $this->validateImageUpload($singleFileEntry, "{$fieldName}[{$i}]");
            if (!empty($fileErrors)) {
                $originalName = $singleFileEntry['name'] ?? "file_{$i}";
                $allErrors[$originalName] = $fileErrors;
            }
        }

        return $allErrors;
    }

    /**
     * Validates parameters for the optimization process.
     *
     * @param array $params Parameters from POST data (e.g., $_POST).
     * @return array Array of error messages, empty if valid.
     */
    public function validateOptimizationParams(array $params): array
    {
        $errors = [];
        $allowedFormats = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'auto']; // Example allowed formats

        // Validate 'quality' parameter
        if (isset($params['quality'])) {
            if (!is_numeric($params['quality']) || $params['quality'] < 0 || $params['quality'] > 100) {
                $errors[] = "Quality parameter must be a number between 0 and 100.";
            }
        }

        // Validate 'format' parameter
        if (isset($params['format'])) {
            if (!is_string($params['format']) || !in_array(strtolower($params['format']), $allowedFormats, true)) {
                $errors[] = "Invalid format specified. Allowed formats: " . implode(', ', $allowedFormats);
            }
        }

        // Validate 'width' parameter (if used for resize during optimization)
        if (isset($params['width'])) {
            if (!is_numeric($params['width']) || $params['width'] <= 0) {
                $errors[] = "Width parameter must be a positive number.";
            }
        }

        // Validate 'height' parameter (if used for resize during optimization)
        if (isset($params['height'])) {
            if (!is_numeric($params['height']) || $params['height'] <= 0) {
                $errors[] = "Height parameter must be a positive number.";
            }
        }

        // Add validation for other expected parameters...

        return $errors;
    }

    /**
     * Validates the structure and parameters of a processing pipeline.
     *
     * @param array $pipelineSteps Decoded array of pipeline steps.
     * @return array Array of error messages, empty if valid.
     */
    public function validatePipeline(array $pipelineSteps): array
    {
        $errors = [];

        if (empty($pipelineSteps)) {
            $errors[] = "Pipeline cannot be empty.";
            return $errors;
        }

        foreach ($pipelineSteps as $index => $step) {
            $stepNum = $index + 1;

            if (!is_array($step)) {
                $errors[] = "Pipeline step {$stepNum} must be an object/array.";
                continue; // Skip further checks for this step
            }

            if (empty($step['operation']) || !is_string($step['operation'])) {
                $errors[] = "Pipeline step {$stepNum} is missing a valid 'operation' string.";
            } elseif (!in_array($step['operation'], self::ALLOWED_PIPELINE_OPERATIONS, true)) {
                $errors[] = "Pipeline step {$stepNum} has an unsupported operation: '{$step['operation']}'. Allowed: " . implode(', ', self::ALLOWED_PIPELINE_OPERATIONS);
            } else {
                // Validate parameters for the specific operation
                $operation = $step['operation'];
                $params = $step['params'] ?? [];
                if (!is_array($params)) {
                     $errors[] = "Pipeline step {$stepNum} ('{$operation}') has invalid 'params'. Must be an object/array.";
                } else {
                    // Check specific parameter types for this operation
                    if (isset(self::PIPELINE_PARAM_TYPES[$operation])) {
                        foreach (self::PIPELINE_PARAM_TYPES[$operation] as $paramName => $type) {
                            if (isset($params[$paramName])) {
                                $value = $params[$paramName];
                                $isValid = false;
                                switch ($type) {
                                    case 'numeric':
                                        $isValid = is_numeric($value);
                                        break;
                                    case 'string':
                                        $isValid = is_string($value);
                                        break;
                                    case 'boolean':
                                         $isValid = is_bool($value);
                                         break;
                                    // Add more types as needed
                                }
                                if (!$isValid) {
                                    $errors[] = "Pipeline step {$stepNum} ('{$operation}'): Parameter '{$paramName}' must be of type {$type}.";
                                }
                                // Add more specific checks (e.g., range for quality, allowed values for format/mode)
                                if ($operation === 'optimize' && $paramName === 'quality' && ($value < 0 || $value > 100)) {
                                     $errors[] = "Pipeline step {$stepNum} ('{$operation}'): Parameter 'quality' must be between 0 and 100.";
                                }
                                // ... add more specific param validations
                            }
                            // Check for required parameters if necessary
                            // Example: if ($operation === 'resize' && !isset($params['width']) && !isset($params['height'])) { ... }
                        }
                    }
                }
            }
        }

        return $errors;
    }
}