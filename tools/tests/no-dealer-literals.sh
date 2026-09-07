#!/usr/bin/env bash
# The rule that lets this theme run for the next dealership without a code edit:
# the dealer's name, address, telephone numbers and email appear nowhere in it.
# Everything is read from the profile record. This is the check that says so.
set -uo pipefail
DIR="$(cd "$(dirname "$0")/../.." && pwd)/theme"
fail=0

check() {   # description, regex
  local hits
  hits=$(grep -rInE "$2" "$DIR" || true)
  if [ -n "$hits" ]; then
    echo "FAIL  $1"
    echo "$hits" | sed 's/^/      /'
    fail=1
  else
    echo " ok   $1"
  fi
}

check "no telephone numbers"      '[0-9]{3}[-. ][0-9]{3}[-. ][0-9]{4}'
check "no street address"         '(Ingra|[0-9]{3,5} +[A-Z][a-z]+ +(Street|St|Avenue|Ave|Road|Rd|Drive))'
check "no dealer email address"   '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|net|org)'
check "no dealer domain"          'downtownauto[a-z]*\.com'
check "no retired outlook address" 'outlook\.com'
check "no ZIP code"               '\b995[0-9]{2}\b'

exit $fail
