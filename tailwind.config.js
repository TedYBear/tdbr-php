/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.{html.twig,twig}",
  ],
  safelist: [
    'hidden',
    'lg:hidden',
    'lg:block',
    'lg:inline-block',
    'lg:flex',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#8B7355',
        secondary: '#D4AF7A',
        accent: '#F5E6D3',
        dark: '#2C2416'
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        heading: ['Space Grotesk', 'sans-serif']
      }
    },
  },
  plugins: [],
}
