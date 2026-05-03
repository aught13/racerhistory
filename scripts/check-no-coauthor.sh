#!/bin/sh
set -eu

# Check for Co-authored-by: trailers in commits not yet pushed (compares to
# upstream if present), otherwise checks recent commits.

UPSTREAM="$(git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>/dev/null || true)"
if [ -n "$UPSTREAM" ]; then
  RANGE="$UPSTREAM..HEAD"
else
  RANGE="HEAD~200..HEAD"
fi

if git rev-list --count $RANGE >/dev/null 2>&1; then
  if git log --pretty=%B $RANGE | grep -i -n '^Co-authored-by:' >/dev/null 2>&1; then
    echo "ERROR: Found Co-authored-by: trailers in commit messages within $RANGE"
    git log --pretty=format:'%h %s%n%b' $RANGE | sed -n '1,200p'
    exit 1
  else
    echo "No Co-authored-by trailers found in $RANGE"
    exit 0
  fi
else
  echo "No commits to check in range $RANGE"
  exit 0
fi
