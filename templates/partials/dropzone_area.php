<?php
// --- Configuration Variables (Expect these to be set before including the partial) ---

// REQUIRED: Unique ID for the form element (used by JS to initialize Dropzone)
$formId = $formId ?? 'my-dropzone-' . uniqid();

// REQUIRED: The URL the form should submit to (or be used by Dropzone's AJAX)
$actionUrl = $actionUrl ?? '/?action=default_action'; // Provide a default or ensure it's always set

// OPTIONAL: ID for a custom clickable element (if not the whole form)
$clickableId = $clickableId ?? $formId . '-clickable'; // Default to formId + '-clickable'

// OPTIONAL: Text for a manual submit button (if autoProcessQueue is false)
$submitButtonText = $submitButtonText ?? null;

// OPTIONAL: Additional hidden inputs or form elements (HTML string)
$extraFormElements = $extraFormElements ?? '';

// OPTIONAL: Custom message for the dropzone area
$dropzoneMessage = $dropzoneMessage ?? 'Drop files here or click to upload';

// OPTIONAL: Add specific classes to the form element
$formClasses = $formClasses ?? '';

?>

<!-- Dropzone Upload Form -->
<form action="<?php echo APP_BASE_PATH . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8'); ?>"
      id="<?php echo htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>"
      class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center bg-gray-50 dark:bg-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-colors cursor-pointer <?php echo htmlspecialchars($formClasses, ENT_QUOTES, 'UTF-8'); ?>"
      method="POST"
      enctype="multipart/form-data">

    <?php /* The .dz-message element is used by Dropzone to display the message */ ?>
    <div class="dz-message" data-dz-message id="<?php echo htmlspecialchars($clickableId, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="flex flex-col items-center justify-center">
            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span class="text-gray-600 dark:text-gray-300 font-medium"><?php echo htmlspecialchars($dropzoneMessage, ENT_QUOTES, 'UTF-8'); ?></span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max file size: 10MB. Allowed types: JPG, PNG, GIF, WebP</p> <?php // TODO: Make size/types dynamic? ?>
        </div>
    </div>

    <?php /* Fallback for browsers without JavaScript */ ?>
    <div class="fallback">
        <input name="images[]" type="file" multiple /> <?php // Adjust name if needed ?>
    </div>

    <?php
    // Include any extra form elements passed in (e.g., hidden fields, parameter inputs)
    echo $extraFormElements;
    ?>

    <?php if ($submitButtonText): ?>
        <div class="mt-6 text-center">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                <?php echo htmlspecialchars($submitButtonText, ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    <?php endif; ?>

</form>