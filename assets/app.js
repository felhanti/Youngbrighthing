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
  }, 2000);
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

// Fonction globale pour ajouter un produit au panier depuis n'importe quelle page
async function addToCartGlobal(productId, buttonElement = null) {
    let button = buttonElement;
    let originalContent = '';
    
    // Si un bouton est fourni, le désactiver pendant la requête
    if (button) {
        originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Ajout...
        `;
    }

    try {
        const response = await fetch(`/cart/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Afficher la notification de succès
            showCartNotification(data.message, 'success');
            
            // Mettre à jour le compteur du panier
            updateCartCounter(data.cartCount);
            
            // Optionnel : marquer le bouton comme ajouté
            if (button) {
                button.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Ajouté !
                `;
                button.classList.remove('bg-yb-dark');
                button.classList.add('bg-green-600');
                
                // Remettre le bouton à l'état initial après 2 secondes
                setTimeout(() => {
                    if (button && originalContent) {
                        button.innerHTML = originalContent;
                        button.classList.remove('bg-green-600');
                        button.classList.add('bg-yb-dark');
                        button.disabled = false;
                    }
                }, 2000);
            }
        } else {
            // Afficher l'erreur
            showCartNotification(data.message, 'error');
            
            // Remettre le bouton à l'état initial
            if (button && originalContent) {
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        showCartNotification('Une erreur est survenue lors de l\'ajout au panier', 'error');
        
        // Remettre le bouton à l'état initial
        if (button && originalContent) {
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    }
}

// Fonction pour afficher les notifications
function showCartNotification(message, type = 'success') {
    // Créer la notification si elle n'existe pas
    let notification = document.getElementById('cart-notification-global');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'cart-notification-global';
        document.body.appendChild(notification);
    }
    
    // Configurer le style selon le type
    const baseClasses = 'fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 z-50 max-w-sm';
    const typeClasses = type === 'success' 
        ? 'bg-green-500 text-white' 
        : 'bg-red-500 text-white';
    
    notification.className = `${baseClasses} ${typeClasses} translate-x-full opacity-0`;
    
    // Contenu de la notification
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                ${type === 'success' 
                    ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
                    : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />'
                }
            </svg>
            <span class="text-sm font-medium">${message}</span>
            <button onclick="hideCartNotification()" class="ml-4 text-white hover:text-gray-200">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    `;
    
    // Afficher la notification avec animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    // Masquer automatiquement après 4 secondes
    setTimeout(() => {
        hideCartNotification();
    }, 4000);
}

// Fonction pour masquer la notification
function hideCartNotification() {
    const notification = document.getElementById('cart-notification-global');
    if (notification) {
        notification.classList.remove('translate-x-0', 'opacity-100');
        notification.classList.add('translate-x-full', 'opacity-0');
    }
}

// Fonction pour mettre à jour le compteur du panier
function updateCartCounter(count) {
    const cartCounters = document.querySelectorAll('.cart-counter');
    cartCounters.forEach(counter => {
        counter.textContent = count;
    });
    
    // Mettre à jour aussi le badge dans la navigation
    const cartBadge = document.querySelector('.bg-yb-gold.text-yb-dark');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}

// Ajouter les gestionnaires d'événements quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Gérer tous les boutons "Ajouter au panier" avec la classe add-to-cart
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            if (productId) {
                addToCartGlobal(productId, this);
            }
        });
    });
});

// Styles CSS pour l'animation de rotation
const styles = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
`;

// Ajouter les styles à la page
const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

