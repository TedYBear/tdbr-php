/*
 * Délégation d'événements déclarative (sans handlers inline onX=).
 *
 * Remplace les anciens attributs onclick/onsubmit/onchange/oninput par des
 * data-attributes, ce qui permet de retirer `script-src-attr 'unsafe-inline'`
 * de la CSP. Tous les écouteurs sont délégués sur `document`, donc le contenu
 * injecté dynamiquement (toasts, lignes ajoutées en AJAX) est pris en charge.
 *
 * Conventions :
 *   data-confirm="message"            (sur <form>)   → confirme avant submit
 *   data-confirm-submit="message"     (sur <button>) → confirme puis requestSubmit(btn)
 *                                                       le message accepte {champ} interpolé
 *                                                       depuis la valeur de [name="champ"]
 *   data-autosubmit                   (sur <select>) → submit du formulaire au change
 *   data-toggle-target="id"           (sur <select>) → affiche #id si value === data-toggle-when
 *   data-toggle-when="valeur"
 *   data-uppercase                    (sur <input>)  → met la valeur en majuscules
 *   data-print                        (sur bouton)   → window.print()
 *   data-copy="texte"                 (sur bouton)   → copie dans le presse-papier + feedback
 *   data-select-on-click              (sur <input>)  → sélectionne le contenu au clic
 *   data-toggle-password              (sur bouton)   → bascule l'affichage du mot de passe
 *   data-submit-form="id"             (sur bouton)   → submit du formulaire #id
 *   data-qty-step="up|down"           (sur bouton)   → incrémente/décrémente la quantité puis submit
 *   data-toast-close                  (sur bouton)   → ferme le toast parent
 */

function interpolate(message, scope) {
  return message.replace(/\{(\w+)\}/g, (match, name) => {
    const field = (scope || document).querySelector('[name="' + name + '"]');
    return field ? field.value : '';
  });
}

export function initInteractions() {
  // --- Confirmation avant soumission d'un formulaire ---
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    const message = form.getAttribute('data-confirm');
    window.showConfirm(message, () => form.submit());
  });

  // --- Clic : la plupart des interactions ---
  document.addEventListener('click', (e) => {
    // Fermeture d'un toast
    const toastClose = e.target.closest('[data-toast-close]');
    if (toastClose) {
      const toast = toastClose.closest('[data-toast]');
      if (toast && typeof toast._remove === 'function') toast._remove();
      return;
    }

    // Bouton submit avec confirmation (préserve name/value via requestSubmit)
    const confirmBtn = e.target.closest('[data-confirm-submit]');
    if (confirmBtn) {
      e.preventDefault();
      const form = confirmBtn.closest('form');
      const message = interpolate(confirmBtn.getAttribute('data-confirm-submit'), document);
      window.showConfirm(message, () => form.requestSubmit(confirmBtn));
      return;
    }

    // Impression
    if (e.target.closest('[data-print]')) {
      window.print();
      return;
    }

    // Copie presse-papier
    const copyBtn = e.target.closest('[data-copy]');
    if (copyBtn) {
      navigator.clipboard.writeText(copyBtn.getAttribute('data-copy'));
      const original = copyBtn.textContent;
      copyBtn.textContent = 'Copié ✓';
      setTimeout(() => { copyBtn.textContent = original; }, 1500);
      return;
    }

    // Bascule affichage du mot de passe
    const pwToggle = e.target.closest('[data-toggle-password]');
    if (pwToggle) {
      const field = pwToggle.closest('.relative').querySelector('input');
      const svgs = pwToggle.querySelectorAll('svg');
      if (field.type === 'password') {
        field.type = 'text';
      } else {
        field.type = 'password';
      }
      svgs.forEach((svg) => svg.classList.toggle('hidden'));
      return;
    }

    // Submit d'un formulaire cible par id
    const submitFormBtn = e.target.closest('[data-submit-form]');
    if (submitFormBtn) {
      const target = document.getElementById(submitFormBtn.getAttribute('data-submit-form'));
      if (target) target.submit();
      return;
    }

    // Pas-à-pas de quantité (panier)
    const qtyBtn = e.target.closest('[data-qty-step]');
    if (qtyBtn) {
      const form = qtyBtn.closest('form');
      const input = form ? form.querySelector('input[type="number"]') : null;
      if (input) {
        if (qtyBtn.getAttribute('data-qty-step') === 'up') input.stepUp();
        else input.stepDown();
        form.submit();
      }
      return;
    }
  });

  // --- Sélection au clic (champ lien en lecture seule) ---
  document.addEventListener('click', (e) => {
    const sel = e.target.closest('[data-select-on-click]');
    if (sel) sel.select();
  });

  // --- Saisie : mise en majuscules ---
  document.addEventListener('input', (e) => {
    const field = e.target.closest('[data-uppercase]');
    if (field) field.value = field.value.toUpperCase();
  });

  // --- Change : auto-submit et bascule de champ conditionnel ---
  document.addEventListener('change', (e) => {
    const auto = e.target.closest('[data-autosubmit]');
    if (auto && auto.form) {
      auto.form.submit();
      return;
    }

    const toggler = e.target.closest('[data-toggle-target]');
    if (toggler) {
      const target = document.getElementById(toggler.getAttribute('data-toggle-target'));
      const when = toggler.getAttribute('data-toggle-when');
      if (target) target.style.display = (toggler.value === when) ? '' : 'none';
    }
  });
}
