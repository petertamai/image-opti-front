/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
      "./templates/**/*.{php,html}", // Include all PHP and HTML files in templates
      "./public/js/**/*.js",         // Include all JS files
      "./src/**/*.php",              // Include PHP files in src (for dynamically generated classes)
      // You might need to add more paths depending on your project structure
    ],
    safelist: [
      // Add frequently used classes that might be missed by the scanner
      'bg-white', 'dark:bg-gray-800', 'text-gray-900', 'dark:text-gray-100',
      'container', 'mx-auto', 'px-4', 'py-8',
      'flex', 'items-center', 'justify-center', 'space-x-4',
      'rounded-lg', 'shadow', 'border', 'border-gray-200', 'dark:border-gray-700'
    ],
    theme: {
      extend: {},
    },
    plugins: [],
  }