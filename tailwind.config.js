/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.{html.twig,twig}",
  ],
  safelist: [
    'hidden',
    'block',
    'flex',
    'inline-block',
    'lg:hidden',
    'lg:block',
    'lg:inline-block',
    'lg:flex',
    'lg:inline',
    'md:hidden',
    'md:flex',
    'md:block',
    'md:inline-block',
    'relative',
    'items-center',
    'gap-2',
    'hover:text-primary',
    'transition',
    'space-x-1',
    'space-x-3',
    'p-2',
    'rounded-lg',
    'hover:bg-gray-100',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#7A2E3F',
        secondary: '#B65C6E',
        accent: '#F8EAED',
        dark: '#2C1117'
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        heading: ['Space Grotesk', 'sans-serif']
      }
    },
  },
  plugins: [],
}
