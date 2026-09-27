/*
 * Script global du site : ajout au panier sans rechargement.
 * Chargé en module (donc après l'analyse du HTML). Les clics sont écoutés sur
 * `document` (délégation) : un bouton ajouté plus tard fonctionne aussi.
 */

// ---------------------------------------------------------------------------
// Panier
// ---------------------------------------------------------------------------

const SPINNER = `
  <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
  </svg>Ajout...`;

const CHECK = `
  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
  </svg>Ajouté !`;

document.addEventListener('click', (event) => {
  const button = event.target.closest('.add-to-cart-btn');
  if (!button || button.disabled) return;

  event.preventDefault();
  addToCart(button);
});

async function addToCart(button) {
  const original = button.innerHTML;
  button.disabled = true;
  button.innerHTML = SPINNER;

  try {
    const response = await fetch(`/cart/add/${encodeURIComponent(button.dataset.productId)}`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-cart"]')?.content ?? '',
      },
    });

    // Session expirée : le pare-feu redirige vers la page de connexion.
    if (response.redirected) {
      window.location.href = response.url;
      return;
    }

    const data = await response.json();
    if (!data.success) {
      notify(data.message || "Erreur lors de l'ajout au panier", 'error');
      button.innerHTML = original;
      button.disabled = false;
      return;
    }

    notify(data.message, 'success');
    document.querySelectorAll('.cart-counter').forEach((el) => { el.textContent = data.cartCount; });

    button.innerHTML = CHECK;
    setTimeout(() => {
      button.innerHTML = original;
      button.disabled = false;
    }, 2000);
  } catch (error) {
    console.error("Erreur lors de l'ajout au panier :", error);
    notify('Une erreur est survenue. Veuillez réessayer.', 'error');
    button.innerHTML = original;
    button.disabled = false;
  }
}

function notify(message, type = 'success') {
  let container = document.getElementById('cart-notification-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'cart-notification-container';
    container.className = 'fixed top-4 right-4 z-50 space-y-2 pointer-events-none';
    document.body.appendChild(container);
  }

  const notification = document.createElement('div');
  notification.className = `pointer-events-auto px-6 py-3 rounded-lg shadow-lg max-w-sm text-sm font-medium text-white transition-transform duration-300 translate-x-full ${
    type === 'success' ? 'bg-green-500' : 'bg-red-500'
  }`;
  notification.setAttribute('role', 'status');
  // textContent : le message n'est jamais interprété comme du HTML.
  notification.textContent = message;
  notification.addEventListener('click', () => notification.remove());
  container.appendChild(notification);

  requestAnimationFrame(() => notification.classList.replace('translate-x-full', 'translate-x-0'));
  setTimeout(() => {
    notification.classList.replace('translate-x-0', 'translate-x-full');
    setTimeout(() => notification.remove(), 300);
  }, 4000);

  while (container.children.length > 3) container.firstElementChild.remove();
}
