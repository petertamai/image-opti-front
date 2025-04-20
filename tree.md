# ImgTasks Project Tree

This document outlines the file and directory structure for the simplified ImgTasks project.
The status `(none)` indicates that the item has not yet been created or populated.

```text
ImgTasks/
├── .env                (none) # Environment variables (API keys, etc.) - !! Add to .gitignore !!
├── .gitignore          (none) # Specifies intentionally untracked files that Git should ignore
├── README.md           (none) # Project overview, setup instructions
├── package.json        (none) # Node.js dependencies (for Tailwind CSS build process)
├── tailwind.config.js  (none) # Tailwind CSS configuration file
├── postcss.config.js   (none) # PostCSS configuration (often used with Tailwind)
├── config/
│   └── (empty or placeholder for future config files if needed) (none)
├── src/                (none) # Core application logic
│   ├── Controllers/    (none) # Handles requests for different modes/actions
│   │   └── IndexController.php    (none) # Example: Handles the main page / mode switching
│   │   └── ProcessController.php  (none) # Example: Handles image processing requests
│   ├── Services/       (none) # API integration logic
│   │   └── OptimizationService.php (none) # Interacts with the custom optimization API
│   │   └── ReplicateService.php    (none) # Interacts with the Replicate API
│   └── Utils/          (none) # Helper functions
│       └── FileHandler.php      (none) # File upload/saving/cleanup logic
│       └── Validator.php        (none) # Input validation logic
│       └── SessionManager.php   (none) # Manages session data (if needed beyond basic PHP sessions)
├── public/             (none) # Web server root - ONLY this directory should be publicly accessible
│   ├── index.php       (none) # Main application entry point / Front Controller / Router
│   ├── js/             (none) # JavaScript files
│   │   ├── vendor/         (none) # Third-party libraries
│   │   │   ├── jquery.min.js    (none) # jQuery library
│   │   │   ├── dropzone.min.js  (none) # Dropzone.js library
│   │   │   ├── Sortable.min.js  (none) # SortableJS library
│   │   │   ├── sweetalert2.all.min.js (none) # SweetAlert2 library
│   │   │   └── progressbar.min.js (none) # ProgressBar.js library
│   │   └── app.js          (none) # Custom application JavaScript (initialization, event handling)
│   ├── css/            (none) # Compiled CSS
│   │   └── style.css     (none) # Output from Tailwind CSS build process
│   └── assets/         (none) # Static assets
│       └── images/       (none) # Site images (logo, icons etc.)
│           └── .gitkeep    (none) # Keep directory in Git even if empty
│       └── fonts/        (none) # Custom fonts (if any)
│           └── .gitkeep    (none) # Keep directory in Git even if empty
├── templates/          (none) # PHP/HTML view templates
│   ├── layout/         (none) # Base layout/structure files
│   │   └── main.php      (none) # Main HTML structure (header, footer, content area)
│   │   └── header.php    (none) # Site header component
│   │   └── footer.php    (none) # Site footer component
│   ├── partials/       (none) # Reusable UI components/snippets
│   │   └── dropzone_area.php (none) # Dropzone component template
│   │   └── results_area.php  (none) # Template for displaying results
│   │   └── pipeline_step.php (none) # Template for a single step in the advanced pipeline
│   ├── pages/          (none) # Specific page views for different modes
│   │   └── basic_mode.php     (none) # View for Basic Mode
│   │   └── simple_opt_mode.php(none) # View for Simple Optimization Mode
│   │   └── advanced_mode.php  (none) # View for Advanced Pipeline Mode
│   └── error.php       (none) # Generic error page template
└── uploads/            (none) # Temporary storage for uploaded/processed files - !! MUST NOT be web-accessible !!
    └── .gitkeep        (none) # Keep directory in Git even if empty - !! Add rule to .gitignore to ignore contents !!