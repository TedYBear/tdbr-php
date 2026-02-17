# Instructions pour Créer le Repo GitHub et Push

## Option 1 : Via l'interface GitHub (Recommandé)

1. **Aller sur GitHub :** https://github.com/new
2. **Créer le repo avec ces paramètres :**
   - Repository name: `tdbr-php`
   - Description: `E-commerce Symfony pour TDBR - Créations artisanales de teddy bears`
   - Public
   - **NE PAS** initialiser avec README, .gitignore ou licence (déjà créés)

3. **Exécuter ces commandes dans le terminal :**

```bash
cd C:\Users\Manu\Documents\TDBR\site_v3

# Ajouter le remote (remplacer USERNAME par votre username GitHub)
git remote add origin https://github.com/USERNAME/tdbr-php.git

# Vérifier le remote
git remote -v

# Push vers GitHub
git push -u origin master
```

## Option 2 : Via GitHub CLI (si installé)

```bash
cd C:\Users\Manu\Documents\TDBR\site_v3

# Créer le repo et push automatiquement
gh repo create tdbr-php --public --source=. --description="E-commerce Symfony pour TDBR" --push
```

## Vérification

Une fois le push effectué, vérifiez sur GitHub que tous les fichiers sont présents :
- ✅ 129 fichiers
- ✅ README.md visible
- ✅ MIGRATION_RESUME.md visible
- ✅ Code source dans src/
- ✅ Templates dans templates/
- ✅ Assets dans assets/

## Commandes Git Utiles

```bash
# Voir le statut
git status

# Voir l'historique
git log --oneline

# Voir les fichiers ignorés
git status --ignored

# Ajouter des modifications futures
git add .
git commit -m "Description des changements"
git push
```

## Important

Le fichier `.gitignore` est configuré pour exclure :
- `/vendor/` (dépendances Composer)
- `/node_modules/` (dépendances NPM)
- `/var/` (cache Symfony)
- `/public/build/` (assets compilés)
- `/public/uploads/*` (images uploadées, sauf .gitkeep)
- `.env.local` (configuration locale)

Ces dossiers/fichiers seront recréés lors de l'installation :
```bash
composer install
npm install
npm run build
```
