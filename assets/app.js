import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';




console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');


document.getElementById('toggle-password').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const img = this;

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        img.src = "{{ asset('assets/svg/eye.svg') }}"; // Change vers l'image "œil ouvert"
    } else {
        passwordField.type = 'password';
        img.src = "{{ asset('assets/svg/eye_close.svg') }}"; // Change vers l'image "œil fermé"
    }
});

const inputs = document.querySelectorAll('.form-control');
inputs.forEach(input => {
    input.addEventListener('input', function () {
        if (this.checkValidity()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });
});
