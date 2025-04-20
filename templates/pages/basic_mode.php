<?php
// Page-specific variables or logic can go here if needed
?>

<div class="max-w-4xl mx-auto">

    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6 text-center">
        Basic Mode
    </h1>
    <p class="text-center text-gray-600 dark:text-gray-400 mb-8">
        Quickly optimize images. Drop files below.
        <?php /* Or add a toggle/button here later to switch between optimize/remove_bg */ ?>
    </p>

    <?php
    // Configure and include the dropzone partial for basic mode
    $formId = 'basic-dropzone'; // Matches JS selector
    $actionUrl = '/?action=optimize'; // Default action for basic mode
    // $clickableId = 'basic-dropzone-clickable'; // Optional: If you want a specific clickable area ID
    // $dropzoneMessage = 'Drop files here to optimize'; // Optional: Custom message

    require dirname(__DIR__) . '/partials/dropzone_area.php';
    ?>

    <?php
    // Include the results area partial
    require dirname(__DIR__) . '/partials/results_area.php';
    ?>

</div>