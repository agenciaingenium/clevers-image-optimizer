=== Clever Image Optimizer ===
Contributors: cleversdevs
Donate link: https://clevers.dev
Tags: image optimization, webp, avif, media library, performance
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clever Image Optimizer compresses local WordPress images and generates modern WebP and optional AVIF files.

== Description ==
Clever Image Optimizer optimizes local WordPress images and generates modern formats (`.webp` and optional `.avif`).

Since version 0.3.0, processing runs in the background with queueing and batch/time limits to avoid admin timeouts during large uploads.

= Features =
* Local optimization of original JPG/PNG files.
* WebP generation.
* Optional AVIF generation.
* `.htaccess` rules for transparent delivery.
* Media Library bulk action to queue optimization jobs.
* Background processing with configurable limits.

= Composer/Vendor policy =
This plugin uses Composer as the source of truth for dependencies.

* Development/CI: install dependencies with `composer install`.
* Release: the published ZIP includes `vendor/` for WordPress environments without Composer.

== Installation ==
1. Upload the plugin to `/wp-content/plugins/clevers-image-optimizer` or install it from the release ZIP.
2. Activate it in WordPress.
3. Go to `Settings > Clever Image Optimizer`.
4. Configure WebP/AVIF quality and background processing limits.

== Frequently Asked Questions ==
= How does background processing work? =
New images and bulk actions are queued. A scheduled event processes the queue in batches (`Batch size`) and respects a maximum runtime (`Time limit per run`).

= What if AVIF is not supported on my server? =
The plugin shows capability status in settings. If `imageavif` is not available, AVIF files are not generated.

= Do I need Composer in production? =
No, if you use the official release ZIP. Composer is only needed for local development and CI.

== Changelog ==
= 0.3.0 =
* Added: background optimization queue with batch/time limits.
* Added: Media Library bulk action now queues jobs (non-blocking admin request).
* Added: PHPUnit unit tests.
* Added: CI workflow (lint + tests).
* Changed: minimum requirements and admin sanitization/escaping hardening.
* Changed: plugin bootstrap initialization.
