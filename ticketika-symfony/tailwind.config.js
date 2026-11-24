/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.twig',
    './src/**/*.php',
    './assets/**/*.{js,css}',
  ],
  theme: {
    extend: {
      colors: {
        olympia: {
          bleu: '#154953',
          fonce: '#103B43',
          sauge: '#7E8E6C',
          dore: '#DFCFAC',
          creme: '#FCFAF5',
          secondaire: '#536267',
          sable: '#F5F0E5',
          'sable-fonce': '#CDBB91',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        soft: '0 12px 32px -12px rgba(16, 59, 67, 0.25)',
      },
    },
  },
  plugins: [],
};
