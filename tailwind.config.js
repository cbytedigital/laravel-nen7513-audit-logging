/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: 'al-',
  important: '.al-scope',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './public/js/**/*.js',
    './src/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        'custom-bg': '#eee',
      },
      maxWidth: {
        '20': '5rem',
        '24': '6rem',
        '32': '8rem',
        '40': '10rem',
        '44': '11rem',
      }
    },
  },
  plugins: [],
}

