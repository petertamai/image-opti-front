<?php
// Page-specific variables or logic can go here if needed
?>

<div class="max-w-6xl mx-auto">

    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
        Advanced Pipeline Mode
    </h1>
    <p class="text-center text-gray-600 dark:text-gray-400 mb-8">
        Build a custom image processing pipeline. Add files, define steps, then run the pipeline.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Column 1: File Upload -->
        <div class="lg:col-span-1">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4">1. Add Files</h2>
            <?php
            // Configure and include the dropzone partial for advanced mode
            $formId = 'advanced-dropzone'; // Matches JS selector
            $actionUrl = '#'; // Action is handled by separate AJAX call, not form submission
            $clickableId = $formId . '-clickable'; // Specific clickable area
            // No submit button needed within the dropzone form itself

            require dirname(__DIR__) . '/partials/dropzone_area.php';
            ?>
        </div>

        <!-- Column 2: Pipeline Builder -->
        <div class="lg:col-span-2">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4">2. Define Pipeline Steps</h2>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <ul id="pipeline-steps" class="space-y-2 mb-4 min-h-[50px]">
                    <?php /* Pipeline steps will be added here by JS */ ?>
                    <?php /* You could include one initial step using the partial if desired: */ ?>
                    <?php /* require dirname(__DIR__) . '/partials/pipeline_step.php'; */ ?>
                    <li class="pipeline-step-placeholder text-center text-gray-500 dark:text-gray-400 italic py-4">
                        Click "Add Step" to build your pipeline. Drag steps to reorder.
                    </li>
                </ul>
                <div class="flex justify-between items-center">
                    <button type="button" id="add-pipeline-step" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Step
                    </button>
                     <button type="button" id="run-pipeline" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                        </svg>
                        Run Pipeline
                    </button>
                </div>
            </div>
        </div>

    </div> <?php // End grid ?>

    <!-- Results Area (Full Width Below Grid) -->
    <div class="mt-10">
         <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4">3. Results</h2>
        <?php
        // Include the results area partial
        require dirname(__DIR__) . '/partials/results_area.php';
        ?>
    </div>

</div>