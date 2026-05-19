#!/bin/bash

# tests/Architecture/test_mvc_integrity.sh
# This script checks for common MVC and separation of concerns violations.
# It exits with a status of 1 if any violations are found, and 0 otherwise.

VIOLATIONS_FOUND=0
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}--- Running MVC Architectural Integrity Tests ---${NC}"

# --- CHECK 1: No direct database calls in Views ---
echo -n "1. Checking for direct database calls in Views..."
DB_CALLS_IN_VIEWS=$(grep -rE '\->prepare|\->execute|new PDO' src/Views/)
if [[ -n "$DB_CALLS_IN_VIEWS" ]]; then
    echo -e " ${RED}FAIL${NC}"
    echo "   Violation: Views should not contain direct database queries."
    echo "   The following files in src/Views/ appear to be calling the database:"
    echo "$DB_CALLS_IN_VIEWS" | sed 's/^/     /'
    VIOLATIONS_FOUND=1
else
    echo -e " ${GREEN}OK${NC}"
fi

# --- CHECK 2: No superglobal usage in Services ---
echo -n "2. Checking for superglobal usage (\$_POST, \$_GET, \$_SESSION) in Services..."
SUPERGLOBALS_IN_SERVICES=$(grep -rE '\$_POST|\$_GET|\$_SESSION' src/Services/)
if [[ -n "$SUPERGLOBALS_IN_SERVICES" ]]; then
    echo -e " ${RED}FAIL${NC}"
    echo "   Violation: Services should not access superglobals directly. They should be passed data from the Controller."
    echo "   The following files in src/Services/ appear to be using superglobals:"
    echo "$SUPERGLOBALS_IN_SERVICES" | sed 's/^/     /'
    VIOLATIONS_FOUND=1
else
    echo -e " ${GREEN}OK${NC}"
fi

# --- CHECK 3: No raw HTML in Controllers ---
echo -n "3. Checking for raw HTML in Controllers..."
HTML_IN_CONTROLLERS=$(grep -rE '<[a-z][\s\S]*>' src/Controllers/)
if [[ -n "$HTML_IN_CONTROLLERS" ]]; then
    echo -e " ${RED}FAIL${NC}"
    echo "   Violation: Controllers should not contain presentation logic (HTML)."
    echo "   The following files in src/Controllers/ appear to contain HTML tags:"
    echo "$HTML_IN_CONTROLLERS" | sed 's/^/     /'
    VIOLATIONS_FOUND=1
else
    echo -e " ${GREEN}OK${NC}"
fi

# --- CHECK 4: No raw HTML in Services ---
echo -n "4. Checking for raw HTML in Services..."
HTML_IN_SERVICES=$(grep -rE '<[a-z][\s\S]*>' src/Services/)
if [[ -n "$HTML_IN_SERVICES" ]]; then
    echo -e " ${RED}FAIL${NC}"
    echo "   Violation: Services should not contain presentation logic (HTML)."
    echo "   The following files in src/Services/ appear to contain HTML tags:"
    echo "$HTML_IN_SERVICES" | sed 's/^/     /'
    VIOLATIONS_FOUND=1
else
    echo -e " ${GREEN}OK${NC}"
fi


# --- Final Result ---
if [[ "$VIOLATIONS_FOUND" -eq 1 ]]; then
    echo -e "
${RED}--- ARCHITECTURAL TEST FAILED ---${NC}"
    exit 1
else
    echo -e "
${GREEN}--- Architectural Integrity Test Passed! ---${NC}"
    exit 0
fi
