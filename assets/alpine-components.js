/*
 * Composants Alpine réutilisables, enregistrés via Alpine.data().
 *
 * Le build @alpinejs/csp interdit les expressions accédant aux variables
 * globales (window, document, …) directement dans les directives HTML.
 * Les composants qui en ont besoin (ex. lecture de window.innerWidth) sont
 * donc déclarés ici en JS réel, où ces accès restent autorisés, puis
 * référencés par leur nom dans les templates (x-data="adminLayout").
 *
 * Les composants spécifiques à une page qui dépendent de variables Twig
 * restent définis en ligne dans leur template (script à nonce), enregistrés
 * eux aussi via Alpine.data() sur l'évènement alpine:init.
 */

export function registerAlpineComponents(Alpine) {
  // Mise en page admin : sidebar repliable + suivi du point de rupture desktop.
  Alpine.data('adminLayout', () => ({
    sidebarOpen: window.innerWidth >= 768,
    isDesktop: window.innerWidth >= 768,
    onResize() {
      this.isDesktop = window.innerWidth >= 768;
      this.sidebarOpen = window.innerWidth >= 768;
    },
  }));

  // Upload d'une image unique (drag & drop + sélection). Les valeurs Twig
  // (dossier, image actuelle) sont passées en arguments : x-data="imageUploader('categories', '/uploads/…')".
  Alpine.data('imageUploader', (directory = 'articles', currentImage = '') => ({
    imageUrl: currentImage,
    directory: directory,
    uploading: false,
    error: '',
    async handleFile(file) {
      if (!file || !file.type.startsWith('image/')) { this.error = 'Fichier invalide'; return; }
      if (file.size > 5242880) { this.error = 'Taille max : 5 Mo'; return; }
      this.error = '';
      this.uploading = true;
      const fd = new FormData();
      fd.append('file', file);
      try {
        const r = await fetch('/admin/upload/image?dir=' + encodeURIComponent(this.directory), { method: 'POST', body: fd });
        const text = await r.text();
        let d;
        try { d = JSON.parse(text); } catch { this.error = 'Réponse invalide (' + r.status + ')'; this.uploading = false; return; }
        if (d.success) { this.imageUrl = d.path; }
        else { this.error = d.error || 'Erreur upload'; }
      } catch (e) { this.error = 'Erreur réseau : ' + e.message; }
      this.uploading = false;
    },
    onDrop(e) { e.preventDefault(); this.handleFile(e.dataTransfer.files[0]); },
    onFileChange(e) { this.handleFile(e.target.files[0]); e.target.value = ''; },
    removeImage() { this.imageUrl = ''; },
    changeImage() { this.imageUrl = ''; this.$nextTick(() => this.$refs.fileInput2.click()); },
  }));

  // Menu latéral admin : état des groupes (ouverts/fermés) persisté en localStorage.
  Alpine.data('adminNav', () => ({
    groups: JSON.parse(localStorage.getItem('tdbrAdminNav') || '{}'),
    isOpen(k, active) { return this.groups.hasOwnProperty(k) ? this.groups[k] : active; },
    toggle(k, active) {
      this.groups[k] = !this.isOpen(k, active);
      localStorage.setItem('tdbrAdminNav', JSON.stringify(this.groups));
    },
  }));
}
