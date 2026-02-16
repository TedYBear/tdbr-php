#!/bin/bash

# Script de test API TDBR Symfony
# Usage: bash test-api.sh

API_URL="http://localhost:8000"
EMAIL="admin@tdbr.fr"
PASSWORD="admin123"

echo "🚀 Test API TDBR Symfony"
echo "========================"
echo ""

# 1. Health check
echo "1️⃣ Health Check..."
curl -s "$API_URL/api/health" | json_pp
echo ""
echo ""

# 2. Inscription
echo "2️⃣ Inscription admin..."
SIGNUP_RESPONSE=$(curl -s -X POST "$API_URL/api/auth/inscription" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"prenom\":\"Admin\",\"nom\":\"TDBR\"}")

echo "$SIGNUP_RESPONSE" | json_pp
TOKEN=$(echo "$SIGNUP_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo ""
echo "Token: $TOKEN"
echo ""
echo ""

# 3. Connexion
echo "3️⃣ Connexion..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/api/auth/connexion" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

echo "$LOGIN_RESPONSE" | json_pp
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo ""
echo "Token: $TOKEN"
echo ""
echo ""

# 4. Profil
echo "4️⃣ Mon profil..."
curl -s "$API_URL/api/auth/profil" \
  -H "Authorization: Bearer $TOKEN" | json_pp
echo ""
echo ""

# 5. Créer une catégorie
echo "5️⃣ Créer une catégorie..."
CAT_RESPONSE=$(curl -s -X POST "$API_URL/api/categories/admin" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"nom":"Test Catégorie","slug":"test-categorie","description":"Catégorie de test","actif":true,"ordre":1}')

echo "$CAT_RESPONSE" | json_pp
CAT_ID=$(echo "$CAT_RESPONSE" | grep -o '"_id":"[^"]*' | cut -d'"' -f4)
echo ""
echo "Catégorie ID: $CAT_ID"
echo ""
echo ""

# 6. Lister les catégories (public)
echo "6️⃣ Liste catégories publiques..."
curl -s "$API_URL/api/categories" | json_pp
echo ""
echo ""

# 7. Créer un article
echo "7️⃣ Créer un article..."
curl -s -X POST "$API_URL/api/articles/admin" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"nom\":\"Article Test\",\"slug\":\"article-test\",\"description\":\"Description test\",\"prix\":29.99,\"categorieId\":\"$CAT_ID\",\"actif\":true,\"vedette\":false,\"stock\":10}" | json_pp
echo ""
echo ""

# 8. Lister les articles (public)
echo "8️⃣ Liste articles publics..."
curl -s "$API_URL/api/articles" | json_pp
echo ""
echo ""

# 9. Créer une collection
echo "9️⃣ Créer une collection..."
curl -s -X POST "$API_URL/api/collections/admin" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"nom":"Collection Test","slug":"collection-test","description":"Collection de test","actif":true,"ordre":1}' | json_pp
echo ""
echo ""

# 10. Lister les collections (admin)
echo "🔟 Liste collections admin..."
curl -s "$API_URL/api/collections/admin/all" \
  -H "Authorization: Bearer $TOKEN" | json_pp
echo ""
echo ""

echo "✅ Tests terminés !"
echo ""
echo "Pour tester l'upload d'image :"
echo "curl -X POST $API_URL/api/uploads/image \\"
echo "  -H \"Authorization: Bearer $TOKEN\" \\"
echo "  -F \"image=@/path/to/image.jpg\""
