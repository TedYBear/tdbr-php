/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import './styles/app.css';

// Import Alpine.js for interactivity
import Alpine from 'alpinejs';

// Start Alpine
window.Alpine = Alpine;
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
