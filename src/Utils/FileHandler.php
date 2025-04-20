<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Handles file uploads, saving processed files, generating web-accessible URLs,
 * and cleanup within the 'uploads' directory.
 * Assumes the APP_BASE_PATH constant is defined in public/index.php.
 */
class FileHandler
{
    /**
     * Absolute filesystem path to the uploads directory.
     * @var string
     */
    private string $uploadDir;

    /**
     * Web-accessible URL path prefix for the uploads directory.
     * Example: /opti/uploads
     * @var string
     */
    private string $uploadUrlPath;

    // Define allowed MIME types for uploads for security
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    // Define a maximum upload size (e.g., 10MB)
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    public function __construct()
    {
        // Assumes 'uploads' directory is at the project root, sibling to 'src' and 'public'
        // BASE_PATH should be defined in public/index.php as dirname(__DIR__)
        $this->uploadDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/uploads/';

        // Construct the URL path based on APP_BASE_PATH constant (defined in public/index.php)
        // and the 'uploads' folder name. Fallback to just '/uploads' if constant not defined.
        $base = defined('APP_BASE_PATH') ? rtrim(APP_BASE_PATH, '/') : '';
        $this->uploadUrlPath = $base . '/uploads';

        // Ensure the upload directory exists and is writable
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true)) { // Use appropriate permissions (0775 recommended)
                error_log("CRITICAL: Upload directory does not exist and could not be created: {$this->uploadDir}");
                throw new \RuntimeException("Upload directory could not be created.");
            }
        } elseif (!is_writable($this->uploadDir)) {
            error_log("CRITICAL: Upload directory is not writable: {$this->uploadDir}");
            throw new \RuntimeException("Upload directory is not writable.");
        }
    }

    /**
     * Generates a unique filename for storing uploaded/processed files.
     *
     * @param string $originalName Original filename to extract extension.
     * @param string $prefix Optional prefix for the filename.
     * @return string The generated unique filename (e.g., prefix_timestamp_random.ext).
     */
    private function generateUniqueFilename(string $originalName, string $prefix = ''): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        // Ensure extension is safe and lowercase, default to 'tmp' if empty
        $safeExtension = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'tmp');
        // Basic sanitization for prefix
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
        // Generate unique name: prefix_timestamp_randomString.extension
        return ($safePrefix ? $safePrefix . '_' : '') . time() . '_' . bin2hex(random_bytes(8)) . '.' . $safeExtension;
    }

    /**
     * Processes a single uploaded file from the $_FILES array structure.
     * Validates basic upload status, moves the file to the upload directory.
     *
     * @param array $fileUploadEntry An entry from the $_FILES array (e.g., $_FILES['image']).
     * @return array|null File info map ['path' => ..., 'url' => ..., 'filename' => ..., 'originalName' => ..., 'size' => ..., 'type' => ...] on success, null on failure.
     */
    public function processUpload(array $fileUploadEntry): ?array
    {
        
        // Check for upload errors provided by PHP
        if (!isset($fileUploadEntry['error']) || is_array($fileUploadEntry['error'])) {
            error_log("FileHandler Error: Invalid upload data structure passed to processUpload.");
            return null;
        }
    
        switch ($fileUploadEntry['error']) {
            case UPLOAD_ERR_OK:
                break; // Continue processing
            case UPLOAD_ERR_NO_FILE:
                // This is not necessarily an error, might be an optional upload. Controller should decide.
                return null;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                error_log("FileHandler Error: Upload failed for '{$fileUploadEntry['name']}'. File too large (UPLOAD_ERR_INI_SIZE/UPLOAD_ERR_FORM_SIZE).");
                return null; // Or throw specific exception
            default:
                error_log("FileHandler Error: Unknown upload error for '{$fileUploadEntry['name']}'. Code: {$fileUploadEntry['error']}.");
                return null; // Or throw specific exception
        }
    
        // Check file size against application limit
        if ($fileUploadEntry['size'] > self::MAX_FILE_SIZE) {
            error_log("FileHandler Error: Upload failed for '{$fileUploadEntry['name']}'. File exceeds application size limit ({$fileUploadEntry['size']} bytes).");
            return null;
        }
    
        // Check MIME type (more reliable than extension)
        if (empty($fileUploadEntry['tmp_name']) || !is_readable($fileUploadEntry['tmp_name'])) {
            error_log("FileHandler Error: Uploaded file '{$fileUploadEntry['name']}' temporary data is missing or not readable at '{$fileUploadEntry['tmp_name']}'.");
            return null;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileUploadEntry['tmp_name']);
        finfo_close($finfo);
    
        if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            error_log("FileHandler Error: Upload failed for '{$fileUploadEntry['name']}'. Invalid MIME type: '{$mimeType}'.");
            return null;
        }
    
        // Generate a unique filename and destination path
        $originalName = basename($fileUploadEntry['name']); // Use basename for basic security
        $uniqueFilename = $this->generateUniqueFilename($originalName, 'upload');
        $destinationPath = $this->uploadDir . $uniqueFilename;
        
        // Check if uploads directory exists and is writable
        
        // Try to create directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true)) {
                error_log("FileHandler Error: Failed to create upload directory: {$this->uploadDir}");
                return null;
            }
        }
    
        // Move the uploaded file from temporary location
        if (!move_uploaded_file($fileUploadEntry['tmp_name'], $destinationPath)) {
            error_log("FileHandler Error: Failed to move uploaded file '{$fileUploadEntry['name']}' from '{$fileUploadEntry['tmp_name']}' to '{$destinationPath}'. Check permissions and paths.");
            return null;
        }
    
        
        // Return structured info about the saved file including the URL
        return [
            'path' => $destinationPath, // Absolute server path (for internal processing)
            'filename' => $uniqueFilename, // Just the filename
            'url' => $this->uploadUrlPath . '/' . $uniqueFilename, // Web accessible URL path
            'originalName' => $originalName,
            'size' => $fileUploadEntry['size'],
            'type' => $mimeType,
        ];
    }

    /**
     * Processes multiple uploaded files (standard $_FILES structure for multiple files).
     * e.g., <input type="file" name="images[]" multiple> results in $_FILES['images'] being an array of arrays.
     *
     * @param array $filesUploadEntry The entry from $_FILES for multiple uploads (e.g., $_FILES['images']).
     * @return array An array of file info maps (including 'url') for successfully processed files. Failures are skipped/logged.
     */
    public function processUploads(array $filesUploadEntry): array
    {
        
        $processedFiles = [];
        // Check if the structure is for multiple files
        if (!isset($filesUploadEntry['name']) || !is_array($filesUploadEntry['name'])) {
            // Handle as single file
            $singleResult = $this->processUpload($filesUploadEntry);
            if ($singleResult !== null) {
                $processedFiles[] = $singleResult;
            } else {
                error_log("FileHandler Error: Failed to process single file upload");
            }
            return $processedFiles;
        }
    
        $numFiles = count($filesUploadEntry['name']);
        $fileKeys = array_keys($filesUploadEntry); // ['name', 'type', 'tmp_name', 'error', 'size']
    
        for ($i = 0; $i < $numFiles; $i++) {
            // Reconstruct the single file array structure for processUpload method
            $singleFileEntry = [];
            foreach ($fileKeys as $key) {
                // Check if the key exists for this index (robustness for malformed arrays)
                if (isset($filesUploadEntry[$key][$i])) {
                    $singleFileEntry[$key] = $filesUploadEntry[$key][$i];
                } else {
                    // Handle missing key, e.g., set to null or log warning
                    $singleFileEntry[$key] = null;
                    error_log("FileHandler Warning: Missing key '{$key}' for file index {$i} in processUploads.");
                }
            }
    
            // Skip empty file inputs often created by browsers if error is UPLOAD_ERR_NO_FILE
            if (($singleFileEntry['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
    
            // Process each file individually
            $result = $this->processUpload($singleFileEntry);
            if ($result !== null) {
                $processedFiles[] = $result;
            } else {
                error_log("FileHandler Info: Failed to process file '{$singleFileEntry['name']}' during multiple upload.");
            }
        }
    
        return $processedFiles; // Contains entries with 'url' field
    }

    /**
     * Saves a file downloaded from a URL (e.g., Replicate output) to the uploads directory.
     *
     * @param string|null $sourceUrl The URL to download the file from.
     * @param string $prefix Prefix for the generated filename.
     * @return string|null The web accessible URL path to the saved file, or null on failure.
     */
    public function saveProcessedImage(?string $sourceUrl, string $prefix = 'processed'): ?string
    {
        if (empty($sourceUrl) || !filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            error_log("FileHandler Error: Invalid or empty source URL provided to saveProcessedImage: '{$sourceUrl}'.");
            return null;
        }

        // Fetch the file content using cURL for better error handling and options
        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $sourceUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false, // Don't include header in output
            CURLOPT_FOLLOWLOCATION => true, // Follow redirects
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 60, // Timeout for download (adjust as needed)
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, // Verify SSL cert (important for security)
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $fileContent = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $error = curl_error($curlHandle);
        curl_close($curlHandle);

        if ($error || $httpCode >= 400 || $fileContent === false || empty($fileContent)) {
            error_log("FileHandler Error: Failed to download file from '{$sourceUrl}'. HTTP Code: {$httpCode}. Curl Error: {$error}.");
            return null;
        }

        // Try to guess a reasonable filename/extension from URL
        $guessedOriginalName = basename(parse_url($sourceUrl, PHP_URL_PATH)) ?: 'downloaded_file';
        if (empty(pathinfo($guessedOriginalName, PATHINFO_EXTENSION))) {
             $guessedOriginalName .= '.tmp'; // Add default extension if missing
        }

        $uniqueFilename = $this->generateUniqueFilename($guessedOriginalName, $prefix);
        $destinationPath = $this->uploadDir . $uniqueFilename;

        if (file_put_contents($destinationPath, $fileContent) === false) {
            error_log("FileHandler Error: Failed to save downloaded file to '{$destinationPath}'. Check permissions.");
            return null;
        }

        // Optional: Validate saved file (e.g., check if it's a valid image, check size/type)

        // Return the web accessible URL path
        return $this->uploadUrlPath . '/' . $uniqueFilename;
    }


    /**
     * Deletes a file from the uploads directory.
     * Use with caution. Ensures the path is within the upload directory.
     *
     * @param string $filename The filename (not the full path) within the upload directory.
     * @return bool True on success or if file doesn't exist, false on failure.
     */
    public function deleteFile(string $filename): bool
    {
        // Prevent directory traversal attacks
        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false || $filename === '.' || $filename === '..') {
            error_log("FileHandler Security Warning: Invalid filename provided for deletion: '{$filename}'.");
            return false;
        }

        $filePath = $this->uploadDir . $filename;

        // Double check the resolved path is still within the upload directory (paranoid check)
        if (strpos(realpath($filePath), realpath($this->uploadDir)) !== 0) {
             error_log("FileHandler Security Warning: Attempt to delete file outside upload directory: '{$filePath}'.");
            return false;
        }

        if (file_exists($filePath) && is_file($filePath)) { // Ensure it's a file
            if (!unlink($filePath)) {
                error_log("FileHandler Error: Failed to delete file: '{$filePath}'. Check permissions.");
                return false;
            }
        }
        return true; // Return true even if file didn't exist (idempotent)
    }

    /**
     * Placeholder for cleanup logic (e.g., delete files older than X hours).
     * This would typically be run by a cron job or occasionally triggered request.
     *
     * @param int $maxAgeSeconds Maximum age of files to keep in seconds (e.g., 3600 * 24 for 24 hours).
     * @return array Report of deleted files or errors.
     */
    public function cleanupOldFiles(int $maxAgeSeconds = 86400): array
    {
        $deletedCount = 0;
        $errorCount = 0;
        $now = time();

        try {
            // Use RecursiveDirectoryIterator to handle potential subdirs if needed in future
            $iterator = new \DirectoryIterator($this->uploadDir);
            foreach ($iterator as $fileinfo) {
                // Skip dots, directories, and protected files like .htaccess or .gitkeep
                if ($fileinfo->isFile() && !$fileinfo->isDot() && $fileinfo->getFilename() !== '.gitkeep' && $fileinfo->getFilename() !== '.htaccess') {
                    if ($now - $fileinfo->getMTime() > $maxAgeSeconds) {
                        if (unlink($fileinfo->getRealPath())) {
                            $deletedCount++;
                        } else {
                            error_log("FileHandler Cleanup Error: Failed to delete old file: " . $fileinfo->getRealPath());
                            $errorCount++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
             error_log("FileHandler Cleanup Exception: " . $e->getMessage());
             return ['status' => 'error', 'message' => $e->getMessage()];
        }

        return ['status' => 'success', 'deleted' => $deletedCount, 'errors' => $errorCount];
    }

    /**
     * Helper to get the filesystem path for a given upload URL path.
     * Used internally if a service needs the path after getting a URL.
     * Basic implementation assumes direct mapping.
     *
     * @param string|null $urlPath The web URL path (e.g., /opti/uploads/file.jpg)
     * @return string|null The corresponding filesystem path or null if invalid.
     */
    public function getPathForUrl(?string $urlPath): ?string
    {
        if ($urlPath === null || strpos($urlPath, $this->uploadUrlPath . '/') !== 0) {
            // URL doesn't start with the expected upload URL path
            return null;
        }
        $filename = basename($urlPath);
        $potentialPath = $this->uploadDir . $filename;

        // Basic check if file exists (optional, depends on use case)
        // if (!file_exists($potentialPath)) {
        //     return null;
        // }
        return $potentialPath;
    }
}