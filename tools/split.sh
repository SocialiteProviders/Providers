#!/usr/bin/env bash
set -euo pipefail

# Split monorepo packages into individual repositories using splitsh-lite.
# Preserves full commit history in split repos.
#
# Usage: GITHUB_TOKEN=xxx ./tools/split.sh [--dry-run]
#
# Requires: splitsh-lite, jq, git

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
OVERRIDES_FILE="$REPO_ROOT/split-overrides.json"
ORG="SocialiteProviders"
BRANCH="master"
DRY_RUN=false
MAX_PARALLEL="${MAX_PARALLEL:-8}"

if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
fi

if [[ -z "${GITHUB_TOKEN:-}" ]] && [[ "$DRY_RUN" == false ]]; then
    echo "Error: GITHUB_TOKEN is required"
    exit 1
fi

# Use GIT_ASKPASS to avoid embedding the token in command arguments or error output
# Placed in .git/ so it's never committed and persists for all background jobs
GIT_ASKPASS_SCRIPT="$REPO_ROOT/.git/.askpass"
cat > "$GIT_ASKPASS_SCRIPT" <<'SCRIPT'
#!/bin/sh
echo "$GITHUB_TOKEN"
SCRIPT
chmod +x "$GIT_ASKPASS_SCRIPT"
export GIT_ASKPASS="$GIT_ASKPASS_SCRIPT"
export GIT_TERMINAL_PROMPT=0

# Build associative array of overrides
declare -A OVERRIDES
while IFS='=' read -r key value; do
    OVERRIDES["$key"]="$value"
done < <(jq -r 'to_entries[] | "\(.key)=\(.value)"' "$OVERRIDES_FILE")

split_package() {
    set +e
    local package="$1"
    local repo="${OVERRIDES[$package]:-$package}"
    local label="$package -> $ORG/$repo"

    local sha
    sha=$(splitsh-lite --prefix="src/$package")

    if [[ -z "$sha" ]]; then
        echo "[error] $label - splitsh-lite failed"
        return 1
    fi

    if [[ "$DRY_RUN" == true ]]; then
        echo "[dry-run] $label"
        return 0
    fi

    if git push --force "https://x-access-token@github.com/${ORG}/${repo}.git" "$sha:refs/heads/$BRANCH"; then
        echo "[ok] $label"
    else
        echo "[error] $label - push failed"
        return 1
    fi
}

cd "$REPO_ROOT"

# Collect all packages
packages=()
for dir in src/*/; do
    packages+=("$(basename "$dir")")
done

echo "Splitting ${#packages[@]} packages (max $MAX_PARALLEL parallel)..."

# Run splits in parallel batches, tracking failures
failed=0
running=0
for package in "${packages[@]}"; do
    split_package "$package" &
    running=$((running + 1))

    if [[ $running -ge $MAX_PARALLEL ]]; then
        wait -n || failed=$((failed + 1))
        running=$((running - 1))
    fi
done

# Wait for remaining jobs
while [[ $running -gt 0 ]]; do
    wait -n || failed=$((failed + 1))
    running=$((running - 1))
done

rm -f "$GIT_ASKPASS_SCRIPT"

if [[ $failed -gt 0 ]]; then
    echo "$failed package(s) failed to split."
    exit 1
fi

echo "All ${#packages[@]} packages split successfully."
