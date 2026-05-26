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

  if (userMenu) {
    userMenu.style.display = isDesktop ? 'block' : 'none';
  }
  if (authLinks) {
    authLinks.style.display = isDesktop ? 'flex' : 'none';
  }
}

// Initial check and listen for resize
updateDesktopMenuVisibility();
window.addEventListener('resize', updateDesktopMenuVisibility);

console.log('TDBR Symfony app is running!');
