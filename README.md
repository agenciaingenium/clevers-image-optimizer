# Clevers Image Optimizer

## Desarrollo

### Dependencias
Este plugin gestiona dependencias con Composer.

```bash
composer install
```

### Calidad

```bash
composer lint
composer test
```

## Arquitectura de optimización

1. Al subir un adjunto, el plugin no optimiza en el request principal.
2. El adjunto se agrega a una cola (`cio_pending_queue`).
3. Un evento programado (`cio_process_queue`) procesa la cola en background.
4. Cada ejecución respeta:
- `cio_batch_limit` (máximo de adjuntos por corrida).
- `cio_time_limit` (máximo de segundos por corrida).

Esto reduce riesgo de timeout en servidores compartidos y lotes grandes.

## Release

El workflow de release genera ZIP con dependencias de Composer incluidas para instalación directa en WordPress.
