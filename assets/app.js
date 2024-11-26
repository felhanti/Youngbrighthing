import "./bootstrap.js";
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "./styles/app.css";

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    const alert = document.querySelector('.alert'); // Récupère uniquement le premier
    if (alert) { // Vérifie si l'élément existe
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";

      // Supprime l'alerte après l'animation
      setTimeout(() => alert.remove(), 500);
    }
  }, 5000);
});


document.addEventListener("DOMContentLoaded", () => {
  // Sélection du champ mot de passe et du bouton
  const togglePassword = document.querySelector(".toggle-password");
  const passwordField = document.querySelector(".password");

  // Gestion de l'affichage/masquage du mot de passe
  if (togglePassword && passwordField) {
    togglePassword.addEventListener("click", () => {
      const img = togglePassword; // L'élément bouton avec l'icône

      if (passwordField.type === "password") {
        passwordField.type = "text"; // Afficher le mot de passe
        if (img.dataset.eyeOpen) img.src = img.dataset.eyeOpen; // Icône "œil ouvert"
      } else {
        passwordField.type = "password"; // Masquer le mot de passe
        if (img.dataset.eyeClose) img.src = img.dataset.eyeClose; // Icône "œil fermé"
      }
    });
  }

  // Validation des champs du formulaire
  const inputs = document.querySelectorAll(".form-control"); // Tous les champs avec la classe "form-control"

  inputs.forEach((input) => {
    input.addEventListener("input", () => {
      if (input.classList.contains("password-field")) {
        // Validation spécifique pour le champ mot de passe
        if (input.value.length >= 8) {
          input.classList.remove("is-invalid");
          input.classList.add("is-valid");
        } else {
          input.classList.remove("is-valid");
          input.classList.add("is-invalid");
        }
      } else {
        // Validation pour les autres champs selon les règles HTML5
        if (input.checkValidity()) {
          input.classList.remove("is-invalid");
          input.classList.add("is-valid");
        } else {
          input.classList.remove("is-valid");
          input.classList.add("is-invalid");
        }
      }

      // Retirer les classes si le champ est vide
      if (input.value.trim() === "") {
        input.classList.remove("is-valid", "is-invalid");
      }
    });
  });
});

