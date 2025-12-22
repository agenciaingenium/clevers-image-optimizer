<?php
/**
 * Bump version en:
 * - clevers-product-carousel.php (header Version)
 * - readme.txt (Stable tag)
 *
 * Uso:
 *   php bin/bump-version.php 1.1.0
 */

if ($argc < 2) {
    fwrite(STDERR, "Uso: php bin/bump-version.php X.Y.Z\n");
    exit(1);
}

$newVersion = $argv[1];

if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $newVersion)) {
    fwrite(STDERR, "Versión inválida: {$newVersion}. Usa formato X.Y.Z\n");
    exit(1);
}

$root = dirname(__DIR__);

/**
 * Actualiza la línea " * Version: X.Y.Z" en el header del plugin.
 */
function bump_plugin_header_version(string $file, string $newVersion): void {
    if (!file_exists($file)) {
        echo "Archivo no encontrado: {$file}\n";
        return;
    }

    $lines   = file($file);
    $changed = 0;

    foreach ($lines as &$line) {
        // Cualquier línea que empiece con "* Version" (soporta "Version:" o "Version :")
        if (preg_match('/^\s*\*\s*Version\b/i', $line)) {
            // Conserva la indentación y el "* "
            if (preg_match('/^(\s*\*\s*)Version\b/i', $line, $m)) {
                $prefix = $m[1];
            } else {
                $prefix = " * ";
            }

            // Reemplaza toda la línea por algo limpio
            $line = $prefix . 'Version: ' . $newVersion . "\n";
            $changed++;
        }
    }

    if ($changed > 0) {
        file_put_contents($file, implode('', $lines));
        echo "✔️ Actualizado header del plugin ({$file}) a versión {$newVersion} ({$changed} línea(s)).\n";
    } else {
        echo "⚠️ No se encontró línea 'Version' en {$file}\n";
    }
}

/**
 * Actualiza la línea "Stable tag: X.Y.Z" en readme.txt.
 */
function bump_readme_stable_tag(string $file, string $newVersion): void {
    if (!file_exists($file)) {
        echo "Archivo no encontrado: {$file}\n";
        return;
    }

    $lines   = file($file);
    $changed = 0;

    foreach ($lines as &$line) {
        if (preg_match('/^Stable tag:/i', $line)) {
            $line = "Stable tag: {$newVersion}\n";
            $changed++;
        }
    }

    if ($changed > 0) {
        file_put_contents($file, implode('', $lines));
        echo "✔️ Actualizado readme.txt (Stable tag) a versión {$newVersion} ({$changed} línea(s)).\n";
    } else {
        echo "⚠️ No se encontró línea 'Stable tag:' en {$file}\n";
    }
}

// Ejecutar updates
bump_plugin_header_version($root . '/clevers-product-carousel.php', $newVersion);
bump_readme_stable_tag($root . '/readme.txt', $newVersion);

echo "\nListo. Revisa los cambios con: git diff\n";