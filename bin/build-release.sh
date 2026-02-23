#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PLUGIN_SLUG="${PLUGIN_SLUG:-clevers-image-optimizer}"
PLUGIN_MAIN="${PLUGIN_MAIN:-clevers-image-optimizer.php}"
DIST_DIR="${DIST_DIR:-dist}"
STAGE_DIR="${ROOT_DIR}/${PLUGIN_SLUG}"
DISTIGNORE_FILE="${DISTIGNORE_FILE:-.distignore}"

if [[ ! -f "$PLUGIN_MAIN" ]]; then
  echo "No se encontró el archivo principal del plugin: $PLUGIN_MAIN" >&2
  exit 1
fi

if [[ ! -f "$DISTIGNORE_FILE" ]]; then
  echo "No se encontró $DISTIGNORE_FILE" >&2
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "Se requiere rsync para empaquetar el plugin." >&2
  exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
  echo "Se requiere zip para generar el archivo final." >&2
  exit 1
fi

VERSION="$(
  grep -Ei '^[[:space:]]*[*#/]{0,2}[[:space:]]*Version:[[:space:]]*[0-9]+([.][0-9]+){1,3}' -m1 "$PLUGIN_MAIN" \
  | sed -E 's/.*Version:[[:space:]]*([0-9]+([.][0-9]+){1,3}).*/\1/' \
  || true
)"

if [[ -z "$VERSION" ]]; then
  echo "No se pudo extraer la versión desde $PLUGIN_MAIN" >&2
  exit 1
fi

mkdir -p "$DIST_DIR"
TMP_STAGE_PARENT="$(mktemp -d "${TMPDIR:-/tmp}/${PLUGIN_SLUG}.XXXXXX")"
STAGE_DIR="${TMP_STAGE_PARENT}/${PLUGIN_SLUG}"
mkdir -p "$STAGE_DIR"
trap 'rm -rf "$TMP_STAGE_PARENT"' EXIT

RSYNC_EXCLUDES=()
while IFS= read -r line || [[ -n "$line" ]]; do
  case "$line" in
    ""|\#*) continue ;;
  esac
  RSYNC_EXCLUDES+=(--exclude "$line")
done < "$DISTIGNORE_FILE"

RSYNC_EXCLUDES+=(--exclude "$PLUGIN_SLUG")
RSYNC_EXCLUDES+=(--exclude "clevers-image-optimizer")
RSYNC_EXCLUDES+=(--exclude "$DIST_DIR")
RSYNC_EXCLUDES+=(--exclude "svn-dist")
RSYNC_EXCLUDES+=(--exclude "dist*")
RSYNC_EXCLUDES+=(--exclude "svn-dist*")

rsync -a "${RSYNC_EXCLUDES[@]}" ./ "$STAGE_DIR"

if [[ -d "build" ]]; then
  rsync -a build "$STAGE_DIR/build"
fi

ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
ZIP_PATH_ABS="${ROOT_DIR}/${ZIP_PATH}"
rm -f "$ZIP_PATH_ABS"

(
  cd "$TMP_STAGE_PARENT"
  zip -rq "$ZIP_PATH_ABS" "$PLUGIN_SLUG"
)

echo "ZIP generado: ${ZIP_PATH}"
ls -lh "$ZIP_PATH_ABS"
