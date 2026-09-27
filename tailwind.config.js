/**
 * Tailwind CSS v3, compilé par `bin/build-css` (binaire autonome, pas de Node requis).
 * Seules les classes réellement présentes dans ces fichiers finissent dans le CSS.
 */
module.exports = {
  content: [
    './templates/**/*.twig',
    './assets/**/*.js',
    './src/**/*.php', // classes posées depuis les types de formulaire
  ],
  theme: {
    extend: {
      colors: {
        yb: {
          gold: '#e8c547',
          dark: '#0d0c0b',
          light: '#f3f1ea',
          gray: '#151412',
        },
      },
      fontFamily: {
        sans: ['Archivo', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['"Bebas Neue"', 'sans-serif'],
      },
    },
  },
};
