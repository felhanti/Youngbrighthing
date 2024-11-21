import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';




console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');

    // Parcourir chaque alerte et définir un timeout pour les cacher
    alerts.forEach(alert => {
        setTimeout(() => {
            // Ajouter une animation de disparition (facultatif)
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';

            // Retirer l'élément du DOM après l'animation
            setTimeout(() => alert.remove(), 500);
        }, 5000); // Temps avant la disparition : 5000 ms = 5 secondes
    });
});

document.getElementById('toggle-password').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const img = this;

    // Basculer le type du champ mot de passe entre 'password' et 'text'
    if (passwordField.type === 'password') {
        passwordField.type = 'text'; // Afficher le mot de passe
        img.src = img.dataset.eyeOpen; // Changer l'icône vers l'œil ouvert
    } else {
        passwordField.type = 'password'; // Masquer le mot de passe
        img.src = img.dataset.eyeClose; // Changer l'icône vers l'œil fermé
    }
});

