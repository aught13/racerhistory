#!/usr/bin/env bash
set -euo pipefail

# Guard against reintroducing deprecated runtime compatibility bridges.
BANNED_PATTERNS=(
  "window\\.resetCrop"
  "window\\.setRotation"
  "window\\.setAspectRatio"
  "window\\.resetAll"
  "webroot/js/hotwire/application\\.js"
  "hotwire/native_bridge\\.js"
  "hotwire/pwa\\.js"
  "hotwire/theme\\.js"
  "hotwire/turbo_scroll\\.js"
  "hotwire/controllers/theme_toggle_controller\\.js"
)

TARGET_PATHS=(
  "js"
  "webroot/js"
  "src"
  "templates"
  "tests"
  "e2e"
  "config"
  "bin"
)

failures=0

echo "Checking for banned legacy runtime markers..."
for pattern in "${BANNED_PATTERNS[@]}"; do
  if grep -RInE -- "$pattern" "${TARGET_PATHS[@]}" >/tmp/legacy_guard_matches.txt; then
    echo ""
    echo "[FAIL] Found banned marker pattern: $pattern"
    cat /tmp/legacy_guard_matches.txt
    failures=1
  else
    echo "[OK] $pattern"
  fi
done

rm -f /tmp/legacy_guard_matches.txt

if [[ "$failures" -ne 0 ]]; then
  echo ""
  echo "Legacy runtime compatibility guard failed."
  exit 1
fi

echo ""
echo "Legacy runtime compatibility guard passed."
