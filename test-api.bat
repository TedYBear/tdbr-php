@echo off
REM Script de test API TDBR Symfony pour Windows
REM Usage: test-api.bat

set API_URL=http://localhost:8000
set EMAIL=admin@tdbr.fr
set PASSWORD=admin123

echo.
echo ================================
echo Test API TDBR Symfony
echo ================================
echo.

echo 1. Health Check...
curl -s %API_URL%/api/health
echo.
echo.

echo 2. Inscription admin...
curl -s -X POST %API_URL%/api/auth/inscription ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\",\"prenom\":\"Admin\",\"nom\":\"TDBR\"}"
echo.
echo.

echo 3. Connexion (copier le token pour les prochaines commandes)...
curl -s -X POST %API_URL%/api/auth/connexion ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"%EMAIL%\",\"password\":\"%PASSWORD%\"}"
echo.
echo.

echo.
echo Copier le token ci-dessus et l'utiliser dans les commandes suivantes:
echo.
set /p TOKEN="Entrer le token JWT: "

echo.
echo 4. Mon profil...
curl -s %API_URL%/api/auth/profil ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

echo 5. Creer une categorie...
curl -s -X POST %API_URL%/api/categories/admin ^
  -H "Content-Type: application/json" ^
  -H "Authorization: Bearer %TOKEN%" ^
  -d "{\"nom\":\"Test Categorie\",\"slug\":\"test-categorie\",\"description\":\"Categorie de test\",\"actif\":true,\"ordre\":1}"
echo.
echo.

echo 6. Liste categories publiques...
curl -s %API_URL%/api/categories
echo.
echo.

echo.
echo ================================
echo Tests termines !
echo ================================
echo.

pause
