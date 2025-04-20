<header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <a href="<?php echo APP_BASE_PATH; ?>/" class="text-2xl font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                ImgTasks <span class="text-sm font-light text-gray-500 dark:text-gray-400">Simple</span>
                <!-- <img src="<?php echo APP_BASE_PATH; ?>/assets/images/logo.png" alt="ImgTasks Logo" class="h-8 w-auto"> -->
            </a>

            <!-- Navigation -->
            <nav>
                <ul class="flex space-x-4 md:space-x-6">
                    <li>
                        <a href="<?php echo APP_BASE_PATH; ?>/?page=basic" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors pb-1 border-b-2 border-transparent hover:border-blue-500">
                            Basic
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo APP_BASE_PATH; ?>/?page=simple" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors pb-1 border-b-2 border-transparent hover:border-blue-500">
                            Simple Opt
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo APP_BASE_PATH; ?>/?page=advanced" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors pb-1 border-b-2 border-transparent hover:border-blue-500">
                            Advanced
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>