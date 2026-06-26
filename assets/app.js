/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import './styles/app.css';

// Import Alpine.js for interactivity
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Drag-and-drop reordering (admin organisation screen)
import Sortable from 'sortablejs';

// Délégation d'événements déclarative (remplace les handlers inline onX=)
import { initInteractions } from './interactions';

function initSortableLists() {
  document.querySelectorAll('[data-sortable]').forEach((list) => {
    if (list._sortableInit) return;
    list._sortableInit = true;
    new Sortable(list, {
      handle: list.dataset.handle || undefined,
      animation: 150,
      ghostClass: 'drag-ghost',
      onEnd: () => saveOrder(list),
    });
  });
}

async function saveOrder(list) {
  const url = list.dataset.reorderUrl;
  if (!url) return;
  // Only direct children carry the order for THIS list (avoids nested lists)
  const ids = Array.from(list.children)
    .filter((el) => el.dataset && el.dataset.id)
    .map((el) => el.dataset.id);
  try {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': meta ? meta.content : '',
      },
      body: JSON.stringify({ ids }),
    });
    const data = await res.json();
    if (data && data.success) {
      window.showToast && window.showToast('Ordre enregistré');
    } else {
      window.showToast && window.showToast((data && data.error) || 'Erreur lors de l\'enregistrement', 'error');
    }
  } catch (e) {
    window.showToast && window.showToast('Erreur réseau', 'error');
  }
}

document.addEventListener('DOMContentLoaded', initSortableLists);
if (document.readyState !== 'loading') initSortableLists();

// Écouteurs délégués pour les data-attributes (data-confirm, data-autosubmit, …)
initInteractions();

// Start Alpine
window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();

// Show/hide desktop menu elements based on viewport width
function updateDesktopMenuVisibility() {
  const isDesktop = window.innerWidth >= 1024;
  const userMenu = document.getElementById('user-menu-desktop');
  const authLinks = document.getElementById('auth-links-desktop');

  console.log('[Desktop Menu] Width:', window.innerWidth, 'isDesktop:', isDesktop);
  console.log('[Desktop Menu] userMenu found:', !!userMenu);
  console.log('[Desktop Menu] authLinks found:', !!authLinks);

  if (userMenu) {
    userMenu.style.display = isDesktop ? 'block' : 'none';
    console.log('[Desktop Menu] userMenu display:', userMenu.style.display);
  }
  if (authLinks) {
    authLinks.style.display = isDesktop ? 'flex' : 'none';
    console.log('[Desktop Menu] authLinks display:', authLinks.style.display);
  }
}

// Initial check and listen for resize
updateDesktopMenuVisibility();
window.addEventListener('resize', updateDesktopMenuVisibility);

console.log('TDBR Symfony app is running!');
