// Wait for the DOM to be fully loaded
$(function () {
    // --- Configuration ---
    const MAX_FILE_SIZE_MB = 10; // Match backend validation if possible
    const ALLOWED_FILES = 'image/jpeg,image/png,image/gif,image/webp'; // Match backend validation

    // --- UI Element Selectors ---
    const resultsArea = $('#results-area');
    const basicDropzoneEl = '#basic-dropzone'; // Form element for basic mode
    const simpleOptDropzoneEl = '#simple-opt-dropzone'; // Form element for simple opt mode
    const advancedDropzoneEl = '#advanced-dropzone'; // Form element for advanced mode
    const pipelineStepsContainer = '#pipeline-steps'; // UL/DIV containing pipeline steps
    const addStepButton = '#add-pipeline-step'; // Button to add a new step
    const runPipelineButton = '#run-pipeline'; // Button to trigger pipeline execution

    // --- Helper Functions ---

    /**
     * Displays a notification using SweetAlert2.
     * @param {string} title The title of the alert.
     * @param {string} text The main text of the alert.
     * @param {string} icon 'success', 'error', 'warning', 'info', 'question'.
     * @param {object|null} customClasses Optional Tailwind classes for styling.
     */
    function showNotification(title, text, icon = 'info', customClasses = null) {
        const defaultClasses = {
            popup: 'bg-white dark:bg-gray-800 rounded-lg shadow-lg',
            title: `text-xl font-semibold ${icon === 'error' ? 'text-red-600 dark:text-red-400' : icon === 'success' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'}`,
            htmlContainer: 'text-gray-700 dark:text-gray-300',
            confirmButton: 'px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-blue-300',
            cancelButton: 'px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded ml-2 focus:outline-none focus:ring-2 focus:ring-gray-200',
            // Add more elements as needed: closeButton, icon, image, input, etc.
        };
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            customClass: customClasses || defaultClasses,
            buttonsStyling: false, // Use customClass for buttons
        });
    }

    /**
     * Clears the results display area.
     */
    function clearResults() {
        resultsArea.empty().append('<p class="text-gray-500 dark:text-gray-400">Results will appear here...</p>'); // Add placeholder back
    }

    /**
     * Displays processed image results in the results area.
     * @param {array} resultsData Array of result objects from the backend. Expected format: { status: 'success', data: { results: [{ url: '...', filename: '...' }, ...] } } or similar.
     */
// In your app.js file, find the displayResults function
/**
 * Displays processed image results in the results area.
 * @param {array} resultsData Array of result objects from the backend. Expected format: { status: 'success', data: { results: [{ url: '...', filename: '...' }, ...] } } or similar.
 */
function displayResults(resultsData) {
    console.log("displayResults called with:", resultsData); // For debugging
    resultsArea.empty(); // Clear previous results or placeholder

    if (!resultsData || !resultsData.data || !Array.isArray(resultsData.data.results) || resultsData.data.results.length === 0) {
        resultsArea.append('<p class="text-red-500 dark:text-red-400">No results were returned or the format was unexpected.</p>');
        return;
    }

    const resultsGrid = $('<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>');
    console.log("Results array:", resultsData.data.results); // For debugging

    resultsData.data.results.forEach((result, index) => {
        // Assuming result object has url (path relative to public/uploads or absolute) and filename
        const resultUrl = result.url || result.path || result.resultUrl || (typeof result === 'string' ? result : null);
        const originalName = result.originalName || `result_${index + 1}`;
        const downloadFilename = result.filename || originalName;

        console.log("Processing result item:", { resultUrl, originalName }); // For debugging

        if (!resultUrl) {
            console.error("Result item missing URL/Path:", result);
            // Optionally display an error placeholder for this specific item
            const errorCard = $(`
                <div class="border border-red-300 dark:border-red-700 rounded-lg p-2 text-center bg-red-50 dark:bg-gray-700">
                    <p class="text-red-600 dark:text-red-400 text-sm truncate" title="Error processing ${originalName}">Error</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Processing failed</p>
                </div>
            `);
            resultsGrid.append(errorCard);
            return; // Skip this result
        }

        // Basic check if it's an image based on common extensions
        const isImage = /\.(jpe?g|png|gif|webp|avif)$/i.test(resultUrl);
        let previewContent;

        if (isImage) {
            previewContent = `<img src="${resultUrl}" alt="${originalName}" class="w-full h-24 object-contain mb-2 rounded">`;
        } else {
            previewContent = `<div class="w-full h-24 mb-2 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                           </div>`;
        }

        // Add optimization info if available
        let optimizationInfo = '';
        if (result.originalSize && result.processedSize) {
            const savedPercent = ((result.originalSize - result.processedSize) / result.originalSize * 100).toFixed(1);
            optimizationInfo = `<p class="text-xs text-green-600 dark:text-green-400">Saved ${savedPercent}% (${(result.processedSize / 1024).toFixed(1)} KB)</p>`;
        } else if (result.compressionRatio) {
            optimizationInfo = `<p class="text-xs text-green-600 dark:text-green-400">Compressed to ${result.compressionRatio}</p>`;
        }

        const resultCard = $(`
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 text-center bg-white dark:bg-gray-800 shadow hover:shadow-md transition-shadow duration-200">
                ${previewContent}
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title="${originalName}">${originalName}</p>
                ${optimizationInfo}
                <a href="${resultUrl}" download="${downloadFilename}" class="mt-2 inline-block text-xs bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 px-3 rounded transition-colors duration-200">
                    Download
                </a>
            </div>
        `);
        
        resultsGrid.append(resultCard);
        console.log("Added result card to grid");
    });

    resultsArea.append(resultsGrid);
    console.log("Added results grid to results area");
}

    /**
     * Shows a generic processing overlay or progress indicator.
     * @param {string} message Text to display.
     */
    function showProcessing(message = 'Processing...') {
        // Simple overlay example using SweetAlert2's loading state
        Swal.fire({
            title: message,
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            customClass: { // Apply Tailwind
                 popup: 'bg-white dark:bg-gray-800 rounded-lg shadow-lg',
                 title: 'text-lg font-semibold text-gray-900 dark:text-white',
                 htmlContainer: 'text-gray-700 dark:text-gray-300',
            },
             buttonsStyling: false
        });
    }

    /**
     * Hides the processing indicator.
     */
    function hideProcessing() {
        Swal.close();
    }

    // --- Dropzone Initialization ---

    // Shared Dropzone config
    const commonDropzoneConfig = {
        paramName: "images", // Default parameter name for files (can be overridden)
        maxFilesize: MAX_FILE_SIZE_MB, // MB
        acceptedFiles: ALLOWED_FILES,
        addRemoveLinks: true, // Allow removing files from preview
        dictDefaultMessage: "Drop files here or click to upload",
        dictFileTooBig: `File is too big ({{filesize}}MB). Max filesize: ${MAX_FILE_SIZE_MB}MB.`,
        dictInvalidFileType: "You can't upload files of this type.",
        timeout: 180000, // 3 minutes timeout for uploads / processing
        init: function () {
            this.on("addedfile", function (file) {
                // Optional: Add custom preview elements or checks
                clearResults(); // Clear previous results when new files are added
            });
            this.on("error", function (file, errorMessage) {
                // Handle errors reported by Dropzone itself (size, type) or server (if non-200 response)
                let messageText = errorMessage;
                if (typeof errorMessage === 'object' && errorMessage.message) {
                    messageText = errorMessage.message; // Use message from JSON error response if available
                }
                showNotification('Upload Error', `Could not upload ${file.name}: ${messageText}`, 'error');
                this.removeFile(file); // Remove the errored file preview
                hideProcessing();
            });
            this.on("processing", function() {
                 showProcessing('Uploading and processing...');
            });
            this.on("queuecomplete", function () {
                // Called when all files in the queue have been processed (or failed)
                hideProcessing();
                 // If autoProcessQueue is true, this indicates completion.
                 // If false, this might not be the right place to hide processing.
            });
            this.on("success", function (file, response) {
                // Hide processing indicator first
                hideProcessing();
                
                console.log("Dropzone success handler received response:", response);
                
                // Handle successful upload AND processing from the backend
                // Assumes backend returns JSON with status:'success' or status:'error'
                if (response && response.status === 'success') {
                    showNotification('Success', response.message || 'Files processed successfully.', 'success');
                    
                    // Make sure we're calling displayResults with the response
                    displayResults(response);
                } else {
                    // Server processed but returned an error status in JSON
                    const errorMsg = response?.message || 'An unknown error occurred during processing.';
                    showNotification('Processing Error', errorMsg, 'error');
                    if (response?.details?.errors) {
                        console.error("Validation Errors:", response.details.errors);
                        // Optionally display detailed errors
                    }
                }
                
                // Optional: Remove the file preview after success
                // this.removeFile(file);
            });
        }
    };

    // Basic Mode Dropzone
// Basic Mode Dropzone
// Add this code to your app.js file:

// Modify the basic mode Dropzone initialization
if ($(basicDropzoneEl).length) {
    new Dropzone(basicDropzoneEl, {
        ...commonDropzoneConfig,
        url: APP_BASE_PATH + "/?action=optimize",
        paramName: "images[]", // Send as array if backend expects multiple
        autoProcessQueue: true, // Process immediately on drop/select
        init: function() {
            commonDropzoneConfig.init.call(this); // Call shared init
            
            // Add this debugging code
            this.on("success", function(file, response) {
                console.log("DEBUG: Dropzone success event triggered", response);
                // Force display of results after a slight delay to ensure DOM updates
                setTimeout(function() {
                    console.log("DEBUG: Forcing displayResults call");
                    displayResults(response);
                    
                    // As a fallback, if the results still don't show, add a direct HTML insertion
                    if (response && response.data && response.data.results && response.data.results.length > 0) {
                        const result = response.data.results[0];
                        if (result.url) {
                            console.log("DEBUG: Adding fallback result display");
                            const resultHtml = `
                                <div class="mt-4 p-4 border border-green-300 rounded">
                                    <h3 class="text-lg font-bold text-green-700">Optimization Successful!</h3>
                                    <div class="flex items-center justify-center my-3">
                                        <img src="${result.url}" alt="Optimized image" class="max-h-64 max-w-full" />
                                    </div>
                                    <p class="text-sm">Original: ${(result.originalSize / 1024).toFixed(1)} KB → 
                                       Optimized: ${(result.processedSize / 1024).toFixed(1)} KB 
                                       (Saved ${((result.originalSize - result.processedSize) / result.originalSize * 100).toFixed(1)}%)</p>
                                    <a href="${result.url}" download class="inline-block mt-3 px-4 py-2 bg-blue-500 text-white rounded">
                                        Download Optimized Image
                                    </a>
                                </div>
                            `;
                            $('#results-area').html(resultHtml);
                        }
                    }
                }, 500);
            });
        }
    });
}

// Simple Optimization Mode Dropzone
if ($(simpleOptDropzoneEl).length) {
    new Dropzone(simpleOptDropzoneEl, {
        ...commonDropzoneConfig,
        url: APP_BASE_PATH + "/?action=optimize", // Update this line to include base path
        paramName: "images[]",
        autoProcessQueue: false, // Don't upload immediately, wait for submit button
        // Rest of the configuration remains the same
    });
}

// Advanced Pipeline Mode Dropzone & Logic
let advancedDropzoneInstance = null;
if ($(advancedDropzoneEl).length) {
    advancedDropzoneInstance = new Dropzone(advancedDropzoneEl, {
        ...commonDropzoneConfig,
        url: APP_BASE_PATH + "/?action=run_pipeline", // Update this line to include base path
        paramName: "images[]",
        autoProcessQueue: false,
        // Rest of the configuration remains the same
    });
}
    // --- SortableJS for Advanced Pipeline ---
    if ($(pipelineStepsContainer).length) {
        const sortableList = document.getElementById(pipelineStepsContainer.substring(1)); // Get raw DOM element
        Sortable.create(sortableList, {
            animation: 150, // ms, animation speed moving items when sorting, `0` — without animation
            handle: '.drag-handle', // Class name for the drag handle element within each step
            ghostClass: 'bg-blue-100 dark:bg-blue-900 opacity-50', // Class name for the drop placeholder
            // onEnd: function (evt) { // Optional: Callback when sorting ends
            //     var itemEl = evt.item; // dragged HTMLElement
            //     console.log("Pipeline order changed:", evt.oldIndex, '->', evt.newIndex);
            // }
        });
    }

    // --- Advanced Pipeline - Add/Remove Steps ---
    // Template for a new pipeline step (adjust structure and parameters based on your needs)
    // Use data attributes to store operation type and parameter names
    const pipelineStepTemplate = `
        <li class="pipeline-step bg-gray-100 dark:bg-gray-700 p-3 rounded-md border border-gray-300 dark:border-gray-600 mb-2 flex items-center space-x-2">
            <span class="drag-handle cursor-move text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" title="Drag to reorder">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </span>
            <div class="flex-grow">
                <select class="pipeline-operation block w-full p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" data-step-param="operation">
                    <option value="">-- Select Operation --</option>
                    <option value="optimize">Optimize</option>
                    <option value="remove_background">Remove Background</option>
                    <option value="resize">Resize</option>
                    <option value="convert">Convert Format</option>
                </select>
                <div class="pipeline-params mt-2 text-sm space-y-1">
                    <!-- Parameters will be loaded here based on selected operation -->
                    <span class="text-gray-500 dark:text-gray-400 italic">Select an operation to see parameters.</span>
                </div>
            </div>
            <button type="button" class="remove-step-btn text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="Remove Step">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
        </li>
    `;

    // Function to load parameters UI based on selected operation
    function loadPipelineParams(selectElement) {
        const operation = $(selectElement).val();
        const paramsContainer = $(selectElement).closest('.pipeline-step').find('.pipeline-params');
        paramsContainer.empty(); // Clear existing params

        switch (operation) {
            case 'optimize':
                paramsContainer.html(`
                    <div class="flex items-center space-x-2">
                        <label for="param-quality-${Date.now()}" class="text-gray-700 dark:text-gray-300">Quality:</label>
                        <input type="number" id="param-quality-${Date.now()}" data-step-param="quality" min="0" max="100" value="80" class="w-20 p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    </div>
                    <div class="flex items-center space-x-2">
                        <label for="param-format-${Date.now()}" class="text-gray-700 dark:text-gray-300">Format:</label>
                        <select id="param-format-${Date.now()}" data-step-param="format" class="p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="auto">Auto</option><option value="jpg">JPG</option><option value="png">PNG</option><option value="webp">WebP</option><option value="gif">GIF</option>
                        </select>
                    </div>`);
                break;
            case 'resize':
                 paramsContainer.html(`
                    <div class="flex items-center space-x-2">
                        <label for="param-width-${Date.now()}" class="text-gray-700 dark:text-gray-300">Width:</label>
                        <input type="number" id="param-width-${Date.now()}" data-step-param="width" min="1" placeholder="px" class="w-20 p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    </div>
                     <div class="flex items-center space-x-2">
                        <label for="param-height-${Date.now()}" class="text-gray-700 dark:text-gray-300">Height:</label>
                        <input type="number" id="param-height-${Date.now()}" data-step-param="height" min="1" placeholder="px" class="w-20 p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    </div>
                    <div class="flex items-center space-x-2">
                         <label for="param-mode-${Date.now()}" class="text-gray-700 dark:text-gray-300">Mode:</label>
                         <select id="param-mode-${Date.now()}" data-step-param="mode" class="p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="fit">Fit (default)</option><option value="cover">Cover</option><option value="fill">Fill</option>
                        </select>
                    </div>`);
                break;
            case 'convert':
                 paramsContainer.html(`
                    <div class="flex items-center space-x-2">
                        <label for="param-format-${Date.now()}" class="text-gray-700 dark:text-gray-300">Target Format:</label>
                        <select id="param-format-${Date.now()}" data-step-param="format" class="p-1 border border-gray-300 dark:border-gray-500 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="jpg">JPG</option><option value="png">PNG</option><option value="webp">WebP</option><option value="gif">GIF</option>
                        </select>
                    </div>`);
                break;
            case 'remove_background':
                paramsContainer.html(`<span class="text-gray-600 dark:text-gray-400">No parameters needed.</span>`);
                break;
            default:
                paramsContainer.html(`<span class="text-gray-500 dark:text-gray-400 italic">Select an operation to see parameters.</span>`);
        }
    }

    // Event listener for adding a new step
    $(addStepButton).on('click', function () {
        $(pipelineStepsContainer).append(pipelineStepTemplate);
    });

    // Event listener for removing a step (using event delegation)
    $(pipelineStepsContainer).on('click', '.remove-step-btn', function () {
        $(this).closest('.pipeline-step').remove();
    });

    // Event listener for changing operation selection (using event delegation)
    $(pipelineStepsContainer).on('change', '.pipeline-operation', function () {
        loadPipelineParams(this);
    });

    // --- Advanced Pipeline - Run Pipeline ---
    $(runPipelineButton).on('click', function () {
        if (!advancedDropzoneInstance) {
            showNotification('Error', 'Dropzone not initialized.', 'error');
            return;
        }

        const files = advancedDropzoneInstance.getAcceptedFiles();
        if (files.length === 0) {
            showNotification('No Files', 'Please add files to the dropzone first.', 'warning');
            return;
        }

        const pipelineSteps = [];
        let validPipeline = true;
        $(pipelineStepsContainer).find('.pipeline-step').each(function (index) {
            const step = $(this);
            const operation = step.find('[data-step-param="operation"]').val();
            const params = {};

            if (!operation) {
                showNotification('Invalid Pipeline', `Step ${index + 1} has no operation selected.`, 'error');
                validPipeline = false;
                return false; // Exit .each loop
            }

            step.find('.pipeline-params [data-step-param]').each(function () {
                const paramInput = $(this);
                const paramName = paramInput.data('step-param');
                if (paramName !== 'operation') { // Exclude the operation select itself
                    params[paramName] = paramInput.val();
                    // Basic validation (ensure numeric fields are numbers)
                    if (paramInput.attr('type') === 'number' && isNaN(parseFloat(params[paramName]))) {
                         // Allow empty strings for optional numbers (e.g. width/height)
                         if (params[paramName] !== '') {
                            showNotification('Invalid Parameter', `Step ${index + 1} (${operation}): '${paramName}' must be a number.`, 'error');
                            validPipeline = false;
                            return false; // Exit inner .each loop
                         } else {
                             delete params[paramName]; // Remove empty optional numeric param
                         }
                    }
                }
            });
             if (!validPipeline) return false; // Exit outer .each loop

            pipelineSteps.push({
                operation: operation,
                params: params
            });
        });

        if (!validPipeline || pipelineSteps.length === 0) {
            if (validPipeline && pipelineSteps.length === 0) {
                 showNotification('Empty Pipeline', 'Please add at least one operation step.', 'warning');
            }
            return; // Stop if pipeline is invalid or empty
        }

        // Prepare FormData for AJAX request
        const formData = new FormData();
        formData.append('pipeline', JSON.stringify(pipelineSteps));

        // Append all accepted files
        files.forEach((file) => {
            formData.append('images[]', file, file.name); // Use 'images[]' to match backend expectation
        });

        // Make the AJAX request to run the pipeline
        showProcessing('Running pipeline...');
        $.ajax({
            url: APP_BASE_PATH + '/?action=run_pipeline',
            type: 'POST',
            data: formData,
            processData: false, // Prevent jQuery from processing the FormData
            contentType: false, // Prevent jQuery from setting contentType
            dataType: 'json', // Expect JSON response
            success: function (response) {
                hideProcessing();
                if (response && response.status === 'success') {
                    showNotification('Pipeline Complete', response.message || 'Pipeline executed successfully.', 'success');
                    displayResults(response); // Display final results
                    // Clear dropzone after successful pipeline run?
                    advancedDropzoneInstance.removeAllFiles(true);
                    // Clear pipeline steps? Optional.
                    // $(pipelineStepsContainer).empty();
                } else {
                    const errorMsg = response?.message || 'An unknown error occurred during pipeline execution.';
                    showNotification('Pipeline Error', errorMsg, 'error');
                     if (response?.details?.errors) {
                        console.error("Pipeline Validation/Execution Errors:", response.details.errors);
                        // Optionally display detailed errors
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                hideProcessing();
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                let errorMsg = 'An unexpected network or server error occurred.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                } else if (jqXHR.responseText) {
                    // Try to show backend plain text error if no JSON
                    // errorMsg = jqXHR.responseText.substring(0, 200); // Limit length
                }
                showNotification('Request Failed', errorMsg, 'error');
            }
        });
    });

    // --- Initial Setup ---
    clearResults(); // Show initial placeholder in results area

}); // End document ready