/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
      "./templates/**/*.php", // Scan all .php files in the templates directory and subdirectories
      "./public/js/**/*.js",   // Scan JavaScript files for dynamic classes if needed
    ],
    theme: {
      extend: {
        // You can extend the default Tailwind theme here
        // e.g., colors, fonts, spacing, etc.
        // colors: {
        //   'brand-blue': '#1992d4',
        // },
      },
    },
    plugins: [
      // You can add Tailwind plugins here if needed
      // e.g., require('@tailwindcss/forms'),
    ],
  }