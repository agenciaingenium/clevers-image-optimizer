#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PLUGIN_SLUG="${PLUGIN_SLUG:-clevers-image-optimizer}"
PLUGIN_MAIN="${PLUGIN_MAIN:-clevers-image-optimizer.php}"
DISTIGNORE_FILE="${DISTIGNORE_FILE:-.distignore}"
OUTPUT_DIR="${OUTPUT_DIR:-svn-dist}"

TAG_VERSION=""
INCLUDE_TAG=1

usage() {
  cat <<'EOF'
Uso:
  ./bin/prepare-svn.sh [--tag X.Y.Z] [--no-tag] [--output DIR]

Opciones:
  --tag X.Y.Z     Fuerza versión/tag para crear tags/X.Y.Z.
  --no-tag        Solo prepara trunk/ (sin tags/).
  --output DIR    Carpeta de salida (default: svn-dist).

Salida:
  DIR/
    trunk/
    assets/      (si existe en el repo)
    tags/X.Y.Z/  (si no se usa --no-tag)
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --tag)
      [[ $# -ge 2 ]] || { echo "Falta valor para --tag" >&2; exit 1; }
      TAG_VERSION="$2"
      shift 2
      ;;
    --no-tag)
      INCLUDE_TAG=0
      shift
      ;;
    --output)
      [[ $# -ge 2 ]] || { echo "Falta valor para --output" >&2; exit 1; }
      OUTPUT_DIR="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Opción no reconocida: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [[ ! -f "$PLUGIN_MAIN" ]]; then
  echo "No se encontró el archivo principal del plugin: $PLUGIN_MAIN" >&2
  exit 1
fi

if [[ ! -f "$DISTIGNORE_FILE" ]]; then
  echo "No se encontró $DISTIGNORE_FILE" >&2
  exit 1
fi

for cmd in rsync grep sed; do
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "Se requiere '$cmd' para preparar el paquete SVN." >&2
    exit 1
  fi
done

HEADER_VERSION="$(
  grep -Ei '^[[:space:]]*[*#/]{0,2}[[:space:]]*Version:[[:space:]]*[0-9]+([.][0-9]+){1,3}' -m1 "$PLUGIN_MAIN" \
  | sed -E 's/.*Version:[[:space:]]*([0-9]+([.][0-9]+){1,3}).*/\1/' \
  || true
)"

if [[ -z "$HEADER_VERSION" ]]; then
  echo "No se pudo extraer la versión desde $PLUGIN_MAIN" >&2
  exit 1
fi

if [[ -z "$TAG_VERSION" ]]; then
  TAG_VERSION="$HEADER_VERSION"
fi

if [[ "$TAG_VERSION" != "$HEADER_VERSION" ]]; then
  echo "La versión del tag ($TAG_VERSION) no coincide con la del header ($HEADER_VERSION)." >&2
  exit 1
fi

rm -rf "$OUTPUT_DIR"
mkdir -p "$OUTPUT_DIR/trunk"

RSYNC_EXCLUDES=()
while IFS= read -r line || [[ -n "$line" ]]; do
  case "$line" in
    ""|\#*) continue ;;
  esac
  RSYNC_EXCLUDES+=(--exclude "$line")
done < "$DISTIGNORE_FILE"

RSYNC_EXCLUDES+=(--exclude "$OUTPUT_DIR")
RSYNC_EXCLUDES+=(--exclude "$PLUGIN_SLUG")
RSYNC_EXCLUDES+=(--exclude "clevers-image-optimizer")
RSYNC_EXCLUDES+=(--exclude "dist")
RSYNC_EXCLUDES+=(--exclude "dist*")
RSYNC_EXCLUDES+=(--exclude "svn-dist*")

rsync -a "${RSYNC_EXCLUDES[@]}" ./ "$OUTPUT_DIR/trunk/"

if [[ -d assets ]]; then
  mkdir -p "$OUTPUT_DIR/assets"
  rsync -a assets/ "$OUTPUT_DIR/assets/"
fi

if [[ "$INCLUDE_TAG" -eq 1 ]]; then
  mkdir -p "$OUTPUT_DIR/tags/$TAG_VERSION"
  rsync -a "$OUTPUT_DIR/trunk/" "$OUTPUT_DIR/tags/$TAG_VERSION/"
fi

echo "Estructura SVN preparada en: $OUTPUT_DIR"
echo "Versión: $HEADER_VERSION"

if [[ "$INCLUDE_TAG" -eq 1 ]]; then
  echo "Tag incluido: tags/$TAG_VERSION"
else
  echo "Tag omitido (--no-tag)"
fi

cat <<EOF

Siguiente paso (ejemplo):
  svn checkout https://plugins.svn.wordpress.org/${PLUGIN_SLUG} /tmp/${PLUGIN_SLUG}-svn
  rsync -a --delete ${OUTPUT_DIR}/trunk/ /tmp/${PLUGIN_SLUG}-svn/trunk/
  rsync -a --delete ${OUTPUT_DIR}/assets/ /tmp/${PLUGIN_SLUG}-svn/assets/   # si aplica
  rsync -a --delete ${OUTPUT_DIR}/tags/${TAG_VERSION}/ /tmp/${PLUGIN_SLUG}-svn/tags/${TAG_VERSION}/
  cd /tmp/${PLUGIN_SLUG}-svn && svn status
  svn add --force . --auto-props --parents --depth infinity -q
  svn commit -m "Release ${TAG_VERSION}"
EOF
