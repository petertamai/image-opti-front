<?php
// Page-specific variables or logic can go here if needed
?>

<div class="max-w-4xl mx-auto">

    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
        Simple Optimization Mode
    </h1>
    <p class="text-center text-gray-600 dark:text-gray-400 mb-8">
        Optimize images with common settings. Adjust parameters, add files, then click "Optimize Images".
    </p>

    <!-- Optimization Parameters Form Section -->
    <?php
    // We'll define the parameter inputs here and pass them as $extraFormElements
    // to the dropzone partial, which is inside a <form>.
    // Alternatively, wrap the dropzone AND these inputs in a single outer form,
    // but Dropzone works best when it *is* the form element.
    // The JS (`app.js`) is currently set up to read these values when sending.

    ob_start(); // Start output buffering to capture the HTML for parameters
    ?>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Optimization Settings</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="opt-quality" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quality (0-100)</label>
                <input type="number" id="opt-quality" name="quality" value="80" min="0" max="100" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="opt-format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Output Format</label>
                <select id="opt-format" name="format" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="auto" selected>Auto Detect</option>
                    <option value="jpg">JPEG</option>
                    <option value="png">PNG</option>
                    <option value="webp">WebP</option>
                    <option value="gif">GIF</option>
                </select>
            </div>
            <div>
                <label for="opt-width" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Width (px, optional)</label>
                <input type="number" id="opt-width" name="width" placeholder="e.g., 1920" min="1" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
             <div>
                <label for="opt-height" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Height (px, optional)</label>
                <input type="number" id="opt-height" name="height" placeholder="e.g., 1080" min="1" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>
    <?php
    $optimizationParamsHtml = ob_get_clean(); // Get buffered HTML
    ?>

    <?php
    // Configure and include the dropzone partial for simple optimization mode
    $formId = 'simple-opt-dropzone'; // Matches JS selector
    $actionUrl = '/?action=optimize'; // Action URL for the form submission
    $clickableId = $formId . '-clickable'; // Specific clickable area within dropzone
    $submitButtonText = 'Optimize Images'; // Text for the manual submit button
    // $extraFormElements = $optimizationParamsHtml; // Pass the captured HTML (JS reads directly now)

    require dirname(__DIR__) . '/partials/dropzone_area.php';
    ?>

    <?php
    // Include the results area partial
    require dirname(__DIR__) . '/partials/results_area.php';
    ?>

</div>

<?php
// Inject the parameters HTML before the dropzone form element
// This ensures the inputs exist in the DOM for the JS to read, even though
// they are visually separated.
echo $optimizationParamsHtml;
?>