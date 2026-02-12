=== Clevers Image Optimizer ===
Contributors: cleversdevs
Donate link: https://clevers.dev
Tags: images, optimizer, webp, avif, performance, media
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 0.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Clevers Image Optimizer optimiza imágenes locales en WordPress y genera formatos modernos (`.webp` y opcionalmente `.avif`).

Desde la versión 0.3.0 el procesamiento se ejecuta en background con cola y límites de lote/tiempo para evitar bloqueos en cargas masivas.

= Características =
* Optimización local del archivo original (JPG/PNG).
* Generación de WebP.
* Generación de AVIF opcional.
* Reglas `.htaccess` para entrega transparente.
* Acción masiva en Biblioteca para encolar optimizaciones.
* Procesamiento en segundo plano con límites configurables.

= Política Composer/Vendor =
Este plugin usa Composer como fuente de verdad de dependencias.

* En desarrollo/CI: instalar dependencias con `composer install`.
* En release: el ZIP publicado incluye `vendor/` para entornos WordPress sin Composer.

== Installation ==
1. Sube el plugin a `/wp-content/plugins/clevers-image-optimizer` o instálalo desde el ZIP de release.
2. Actívalo en WordPress.
3. Ve a `Ajustes > Clever Image Optimizer`.
4. Configura calidad WebP/AVIF y límites del proceso en background.

== Frequently Asked Questions ==
= ¿Cómo funciona el procesamiento en background? =
Las imágenes nuevas y acciones masivas se encolan. Un evento programado procesa la cola por lotes (`Lote por ejecución`) y respeta un máximo de tiempo (`Límite de tiempo por ejecución`).

= ¿Qué pasa si no hay soporte AVIF? =
El plugin muestra el estado en ajustes. Si `imageavif` no está disponible, AVIF no se generará.

= ¿Necesito Composer en producción? =
No, si usas el ZIP de release oficial. Sí lo necesitas para desarrollo local y CI.

== Changelog ==
= 0.3.0 =
* Added: cola de optimización en background con límites de lote y tiempo.
* Added: acción masiva ahora encola trabajos (no bloquea request admin).
* Added: tests unitarios con PHPUnit.
* Added: workflow CI (lint + tests).
* Changed: requisitos mínimos y hardening de sanitización/escaping en admin.
* Changed: inicialización de plugin y carga de text domain.
