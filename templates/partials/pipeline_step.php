<?php
/**
 * Template for a single pipeline step in the Advanced Mode UI.
 *
 * NOTE: The current public/js/app.js uses a hardcoded JavaScript string
 *       (pipelineStepTemplate) that mirrors this structure. This PHP file
 *       serves as the reference or can be used if JS is refactored to
 *       fetch/clone this template instead of hardcoding it.
 *
 * Required JS Functionality:
 * - Clones/Appends this structure to the pipeline list.
 * - Attaches event listeners for 'change' on '.pipeline-operation'.
 * - Attaches event listeners for 'click' on '.remove-step-btn'.
 * - Uses '.drag-handle' for SortableJS.
 * - Populates '.pipeline-params' based on selected operation.
 * - Reads values using '[data-step-param]' attributes when running the pipeline.
 */

// Generate unique IDs for labels/inputs if needed within this instance
$uniqueIdSuffix = uniqid('step_');

?>
<li class="pipeline-step bg-gray-100 dark:bg-gray-700 p-3 rounded-md border border-gray-300 dark:border-gray-600 mb-2 flex items-center space-x-2">

    <!-- Drag Handle -->
    <span class="drag-handle cursor-move text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex-shrink-0" title="Drag to reorder">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </span>

    <!-- Step Content -->
    <div class="flex-grow">
        <!-- Operation Selection -->
        <select class="pipeline-operation block w-full p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" data-step-param="operation">
            <option value="">-- Select Operation --</option>
            <option value="optimize">Optimize</option>
            <option value="remove_background">Remove Background</option>
            <option value="resize">Resize</option>
            <option value="convert">Convert Format</option>
            <?php /* Add other supported operations here */ ?>
        </select>

        <!-- Parameters Area (Populated by JavaScript) -->
        <div class="pipeline-params mt-2 text-sm space-y-1">
            <span class="text-gray-500 dark:text-gray-400 italic">Select an operation to see parameters.</span>
            <?php /* Parameter inputs will be dynamically loaded here by app.js based on the selected operation */ ?>
            <?php /* Example structure loaded by JS for 'optimize':
            <div class="flex items-center space-x-2">
                <label for="param-quality-<?php echo $uniqueIdSuffix; ?>" class="text-gray-700 dark:text-gray-300">Quality:</label>
                <input type="number" id="param-quality-<?php echo $uniqueIdSuffix; ?>" data-step-param="quality" min="0" max="100" value="80" class="w-20 p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
            </div>
            <div class="flex items-center space-x-2">
                <label for="param-format-<?php echo $uniqueIdSuffix; ?>" class="text-gray-700 dark:text-gray-300">Format:</label>
                <select id="param-format-<?php echo $uniqueIdSuffix; ?>" data-step-param="format" class="p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    <option value="auto">Auto</option><option value="jpg">JPG</option><option value="png">PNG</option><option value="webp">WebP</option><option value="gif">GIF</option>
                </select>
            </div>
            */ ?>
        </div>
    </div>

    <!-- Remove Button -->
    <button type="button" class="remove-step-btn text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 flex-shrink-0" title="Remove Step">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>

</li>