import "./bootstrap.js";
import "./styles/app.css";

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");

// Gestion des alertes avec auto-masquage
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500);
    }
  }, 2000);
});

// Gestion de l'affichage/masquage du mot de passe
document.addEventListener("DOMContentLoaded", () => {
  const togglePassword = document.querySelector(".toggle-password");
  const passwordField = document.querySelector(".password");

  if (togglePassword && passwordField) {
    togglePassword.addEventListener("click", () => {
      const img = togglePassword;

      if (passwordField.type === "password") {
        passwordField.type = "text";
        if (img.dataset.eyeOpen) img.src = img.dataset.eyeOpen;
      } else {
        passwordField.type = "password";
        if (img.dataset.eyeClose) img.src = img.dataset.eyeClose;
      }
    });
  }

  // Validation des champs du formulaire
  const inputs = document.querySelectorAll(".form-control");

  inputs.forEach((input) => {
    input.addEventListener("input", () => {
      if (input.classList.contains("password-field")) {
        if (input.value.length >= 8) {
          input.classList.remove("is-invalid");
          input.classList.add("is-valid");
        } else {
          input.classList.remove("is-valid");
          input.classList.add("is-invalid");
        }
      } else {
        if (input.checkValidity()) {
          input.classList.remove("is-invalid");
          input.classList.add("is-valid");
        } else {
          input.classList.remove("is-valid");
          input.classList.add("is-invalid");
        }
      }

      if (input.value.trim() === "") {
        input.classList.remove("is-valid", "is-invalid");
      }
    });
  });
});

// SYSTÈME DE PANIER - Version unique et corrigée
class CartManager {
  constructor() {
    this.initializeEventListeners();
    this.createNotificationContainer();
    this.injectStyles();
  }

  initializeEventListeners() {
    document.addEventListener('DOMContentLoaded', () => {
      this.attachCartButtons();
    });

    // Observer pour les nouveaux boutons ajoutés dynamiquement
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.classList && node.classList.contains('add-to-cart-btn')) {
              this.attachCartButton(node);
            } else {
              const buttons = node.querySelectorAll && node.querySelectorAll('.add-to-cart-btn');
              if (buttons) {
                buttons.forEach(button => this.attachCartButton(button));
              }
            }
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  attachCartButtons() {
    const buttons = document.querySelectorAll('.add-to-cart-btn');
    buttons.forEach(button => this.attachCartButton(button));
  }

  attachCartButton(button) {
    // Éviter d'attacher plusieurs fois le même listener
    if (button.dataset.cartListenerAttached) return;
    
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const productId = button.dataset.productId;
      
      if (!productId) {
        console.error('ID du produit manquant');
        this.showNotification('Erreur: ID du produit manquant', 'error');
        return;
      }
      
      this.addToCart(productId, button);
    });
    
    button.dataset.cartListenerAttached = 'true';
  }

  async addToCart(productId, buttonElement) {
    const button = buttonElement;
    const originalContent = button.innerHTML;
    
    // Désactiver le bouton et afficher l'indicateur de chargement
    button.disabled = true;
    button.innerHTML = `
      <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      Ajout...
    `;
    
    try {
      const response = await fetch(`/cart/cart/add/${productId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      
      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.success) {
        this.showNotification(data.message || 'Produit ajouté au panier avec succès!', 'success');
        this.updateCartCounter(data.cartCount);
        
        // Bouton de succès temporaire
        button.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Ajouté!
        `;
        button.classList.remove('bg-yb-dark');
        button.classList.add('bg-green-600');
        
        // Remettre le bouton à l'état initial après 2 secondes
        setTimeout(() => {
          button.innerHTML = originalContent;
          button.classList.remove('bg-green-600');
          button.classList.add('bg-yb-dark');
          button.disabled = false;
        }, 2000);
        
      } else {
        this.showNotification(data.message || 'Erreur lors de l\'ajout au panier', 'error');
        this.resetButton(button, originalContent);
      }
      
    } catch (error) {
      console.error('Erreur lors de l\'ajout au panier:', error);
      this.showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
      this.resetButton(button, originalContent);
    }
  }

  resetButton(button, originalContent) {
    button.innerHTML = originalContent;
    button.disabled = false;
  }

  createNotificationContainer() {
    if (!document.getElementById('cart-notification-container')) {
      const container = document.createElement('div');
      container.id = 'cart-notification-container';
      container.className = 'fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(container);
    }
  }

  showNotification(message, type = 'success') {
    const container = document.getElementById('cart-notification-container');
    if (!container) {
      this.createNotificationContainer();
      return this.showNotification(message, type);
    }

    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification px-6 py-3 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full ${
      type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    
    const iconSvg = type === 'success' 
      ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
      : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />';
    
    notification.innerHTML = `
      <div class="flex items-center">
        <svg class="h-5 w-5 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          ${iconSvg}
        </svg>
        <span class="text-sm font-medium">${message}</span>
        <button class="close-btn ml-3 text-white hover:text-gray-200 focus:outline-none">
          <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    `;
    
    // Ajouter le gestionnaire de fermeture
    notification.querySelector('.close-btn').addEventListener('click', () => {
      this.hideNotification(notification);
    });
    
    // Ajouter au container
    container.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
      notification.classList.remove('translate-x-full');
      notification.classList.add('translate-x-0');
    }, 100);
    
    // Masquage automatique après 4 secondes
    setTimeout(() => {
      this.hideNotification(notification);
    }, 4000);
    
    // Limiter le nombre de notifications affichées
    const notifications = container.querySelectorAll('.notification');
    if (notifications.length > 3) {
      this.hideNotification(notifications[0]);
    }
  }

  hideNotification(notification) {
    if (notification && notification.parentNode) {
      notification.classList.add('translate-x-full');
      setTimeout(() => {
        if (notification && notification.parentNode) {
          notification.remove();
        }
      }, 300);
    }
  }

  updateCartCounter(count) {
    // Mettre à jour tous les compteurs de panier
    const selectors = [
      '.bg-yb-gold.text-yb-dark', // Badge dans la navigation
      '.cart-counter',            // Compteurs génériques
      '[data-cart-count]'         // Éléments avec attribut data
    ];
    
    selectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        // Vérifier si c'est un compteur numérique
        if (element.textContent.match(/^\d+$/)) {
          element.textContent = count;
        } else if (element.dataset.cartCount !== undefined) {
          element.dataset.cartCount = count;
          element.textContent = count;
        }
      });
    });
  }

  injectStyles() {
    if (!document.getElementById('cart-styles')) {
      const styles = `
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        .animate-spin {
          animation: spin 1s linear infinite;
        }
        
        #cart-notification-container {
          pointer-events: none;
        }
        
        #cart-notification-container .notification {
          pointer-events: auto;
        }
        
        .notification {
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
      `;

      const styleSheet = document.createElement('style');
      styleSheet.id = 'cart-styles';
      styleSheet.textContent = styles;
      document.head.appendChild(styleSheet);
    }
  }
}

// Initialiser le gestionnaire de panier
const cartManager = new CartManager();

// Exposer les fonctions globalement pour compatibilité
window.addToCart = (productId, button) => cartManager.addToCart(productId, button);
window.showCartNotification = (message, type) => cartManager.showNotification(message, type);
window.updateCartCounter = (count) => cartManager.updateCartCounter(count);