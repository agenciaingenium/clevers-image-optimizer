<?php


if (!defined('ABSPATH')) exit;

function cio_get_htaccess_path()
{
    return ABSPATH . '.htaccess';
}

function cio_get_rules_block()
{
    return <<<HTA
# BEGIN Clever_Image_Optimizer
<IfModule mod_rewrite.c>
RewriteEngine On

RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_FILENAME} \.(jpe?g|png)$
RewriteCond %{REQUEST_FILENAME}.webp -f
RewriteRule ^(.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept_webp:1,L]
</IfModule>

<IfModule mod_headers.c>
Header append Vary Accept env=accept_webp
</IfModule>
# END Clever_Image_Optimizer
HTA;
}

function cio_rules_exist()
{
    $path = cio_get_htaccess_path();
    if (!file_exists($path)) return false;

    $contents = file_get_contents($path);
    return strpos($contents, '# BEGIN Clever_Image_Optimizer') !== false;
}

function cio_add_htaccess_rules()
{
    $path = cio_get_htaccess_path();
    $rules = cio_get_rules_block();

    // Si no existe .htaccess lo creamos
    if (!file_exists($path)) {
        file_put_contents($path, $rules . PHP_EOL);
        return true;
    }

    // Si ya existen las reglas, no hacemos nada
    if (cio_rules_exist()) return true;

    // Append al final del archivo
    $contents = file_get_contents($path);
    $contents .= PHP_EOL . $rules . PHP_EOL;

    return file_put_contents($path, $contents);
}

function cio_remove_htaccess_rules()
{
    $path = cio_get_htaccess_path();
    if (!file_exists($path)) return;

    $contents = file_get_contents($path);

    $pattern = '/# BEGIN Clever_Image_Optimizer(.|\n)*?# END Clever_Image_Optimizer/';
    $cleaned = preg_replace($pattern, '', $contents);

    file_put_contents($path, $cleaned);
}