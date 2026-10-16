#!/bin/bash
# Clean Architecture Compliance Checker
# This script verifies that Clean Architecture principles are maintained

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Clean Architecture Compliance Check"
echo "======================================"
echo ""

# Track violations
VIOLATIONS=0

# 1. Check Domain Layer Purity
echo "1️⃣  Checking Domain Layer (must be pure PHP)..."
DOMAIN_VIOLATIONS=$(find app/Domain -type f -name "*.php" -exec grep -l "Eloquent\|Illuminate\|DB::\|Model::" {} \; 2>/dev/null || true)

if [ -z "$DOMAIN_VIOLATIONS" ]; then
    echo -e "${GREEN}   ✅ Domain layer is pure PHP${NC}"
else
    echo -e "${RED}   ❌ Domain layer has framework dependencies:${NC}"
    echo "$DOMAIN_VIOLATIONS" | while read line; do
        echo "      - $line"
    done
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 2. Check Application Layer (should only have interfaces)
echo "2️⃣  Checking Application Layer (no Eloquent usage)..."
APP_VIOLATIONS=$(find app/Application -type f -name "*.php" -exec grep -l "Eloquent\|::query()\|::where()\|::find()\|::create()" {} \; 2>/dev/null || true)

if [ -z "$APP_VIOLATIONS" ]; then
    echo -e "${GREEN}   ✅ Application layer has no Eloquent usage${NC}"
else
    echo -e "${RED}   ❌ Application layer uses Eloquent:${NC}"
    echo "$APP_VIOLATIONS" | while read line; do
        echo "      - $line"
    done
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 3. Check Repository Implementation (should use Query Builder)
echo "3️⃣  Checking Repository Implementation..."
REPO_ELOQUENT=$(grep -rn "extends Model\|User::query\|User::where\|User::find\|User::create" app/Infrastructure/Identity/Persistence/ 2>/dev/null || true)

if [ -z "$REPO_ELOQUENT" ]; then
    echo -e "${GREEN}   ✅ Repository uses Query Builder (not Eloquent)${NC}"
else
    echo -e "${RED}   ❌ Repository uses Eloquent methods:${NC}"
    echo "$REPO_ELOQUENT"
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 4. Check Controllers (should only call Use Cases)
echo "4️⃣  Checking Presentation Controllers..."
CONTROLLER_DB=$(grep -rn "DB::table\|User::query\|EloquentUser::\|->repository" app/Presentation/Web/Controllers/ 2>/dev/null || true)

if [ -z "$CONTROLLER_DB" ]; then
    echo -e "${GREEN}   ✅ Controllers only call Use Cases${NC}"
else
    echo -e "${RED}   ❌ Controllers access database directly:${NC}"
    echo "$CONTROLLER_DB"
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 5. Check Eloquent Model Isolation
echo "5️⃣  Checking Eloquent Model Isolation..."
ELOQUENT_USAGE=$(grep -rn "use.*EloquentUser" app/Application app/Domain app/Presentation/Web/Controllers 2>/dev/null || true)

if [ -z "$ELOQUENT_USAGE" ]; then
    echo -e "${GREEN}   ✅ EloquentUser is isolated (only used by Laravel auth)${NC}"
else
    echo -e "${RED}   ❌ EloquentUser leaked into business logic:${NC}"
    echo "$ELOQUENT_USAGE"
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 6. Check for old Models namespace
echo "6️⃣  Checking for old Models namespace..."
OLD_MODELS=$(grep -rn "App\\\\Models\\\\" app/ config/ 2>/dev/null || true)

if [ -z "$OLD_MODELS" ]; then
    echo -e "${GREEN}   ✅ No references to old Models namespace${NC}"
else
    echo -e "${RED}   ❌ Found references to app/Models:${NC}"
    echo "$OLD_MODELS"
    VIOLATIONS=$((VIOLATIONS + 1))
fi
echo ""

# 7. Verify Repository returns Domain entities
echo "7️⃣  Checking Repository return types..."
REPO_RETURNS=$(grep -A 5 "public function" app/Infrastructure/Identity/Persistence/QueryBuilderUserRepository.php | grep -E "return new (User|EloquentUser)" || true)

if echo "$REPO_RETURNS" | grep -q "return new User"; then
    if echo "$REPO_RETURNS" | grep -q "return new EloquentUser"; then
        echo -e "${RED}   ❌ Repository returns EloquentUser (should return Domain User)${NC}"
        VIOLATIONS=$((VIOLATIONS + 1))
    else
        echo -e "${GREEN}   ✅ Repository returns Domain entities${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  Could not verify repository return types${NC}"
fi
echo ""

# Final Summary
echo "======================================"
if [ $VIOLATIONS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed! Clean Architecture compliance verified.${NC}"
    exit 0
else
    echo -e "${RED}❌ Found $VIOLATIONS violation(s). Please fix the issues above.${NC}"
    exit 1
fi
