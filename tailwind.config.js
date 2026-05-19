/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./src/Resources/js/**/*.svelte",
    "./src/Resources/js/**/*.js",
    "./src/Views/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'shadow-black': '#0a0a0a',
        'shadow-dark': '#0f0f0f',
        'shadow-brown': '#2a231e',
        'shadow-gold': '#c5a059',
        'shadow-lightgold': '#e2c792',
        'shadow-green': '#3f6b2f',
        'shadow-lightgreen': '#5ea346'
      }
    },
  },
  plugins: [],
}