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

# Build associative array of overrides
declare -A OVERRIDES
while IFS='=' read -r key value; do
    OVERRIDES["$key"]="$value"
done < <(jq -r 'to_entries[] | "\(.key)=\(.value)"' "$OVERRIDES_FILE")

split_package() {
    local package="$1"
    local repo="${OVERRIDES[$package]:-$package}"

    echo "[split] $package -> $ORG/$repo"

    local sha
    sha=$(splitsh-lite --prefix="src/$package")

    if [[ "$DRY_RUN" == true ]]; then
        echo "[dry-run] Would push $sha to $ORG/$repo ($BRANCH)"
        return
    fi

    git push --quiet --force "https://${GITHUB_TOKEN}@github.com/${ORG}/${repo}.git" "$sha:refs/heads/$BRANCH"

    echo "[split] $package done"
}

cd "$REPO_ROOT"

# Remove the default GITHUB_TOKEN credential helper set by actions/checkout
git config --unset-all http.https://github.com/.extraheader 2>/dev/null || true

# Collect all packages
packages=()
for dir in src/*/; do
    packages+=("$(basename "$dir")")
done

echo "Splitting ${#packages[@]} packages (max $MAX_PARALLEL parallel)..."

# Run splits in parallel batches
running=0
for package in "${packages[@]}"; do
    split_package "$package" &
    running=$((running + 1))

    if [[ $running -ge $MAX_PARALLEL ]]; then
        wait -n
        running=$((running - 1))
    fi
done

wait
echo "All ${#packages[@]} packages split successfully."
