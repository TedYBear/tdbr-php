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
        primary: '#2F7A5B',
        secondary: '#4FB48A',
        accent: '#E7F4EE',
        dark: '#143027'
      },
      fontFamily: {
        sans: ['Hanken Grotesk', 'sans-serif'],
        heading: ['Bricolage Grotesque', 'sans-serif']
      }
    },
  },
  plugins: [],
}
