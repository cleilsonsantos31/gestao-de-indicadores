<?php

declare(strict_types=1);

use Glpi\Plugin\Hooks;
use GlpiPlugin\Indicadores\Menu;

define('PLUGIN_INDICADORES_VERSION', '0.4.7');
define('PLUGIN_INDICADORES_BUILD', '2026.09.09-r12-safe-recovery-zabbix-parity');
define('PLUGIN_INDICADORES_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_INDICADORES_MAX_GLPI_VERSION', '12.0.0');

function plugin_init_indicadores(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['indicadores'] = true;
    $PLUGIN_HOOKS[Hooks::MENU_TOADD]['indicadores'] = [
        'tools' => Menu::class,
    ];
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['indicadores'] = [
        'js/menu-target-v052.js',
    ];
}

function plugin_version_indicadores(): array
{
    return [
        'name'         => 'Central de Indicadores',
        'version'      => PLUGIN_INDICADORES_VERSION,
        'author'       => 'Cleilson Santos',
        'license'      => 'MIT',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_INDICADORES_MIN_GLPI_VERSION,
                'max' => PLUGIN_INDICADORES_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

function plugin_indicadores_check_prerequisites(): bool
{
    return version_compare(GLPI_VERSION, PLUGIN_INDICADORES_MIN_GLPI_VERSION, '>=')
        && version_compare(GLPI_VERSION, PLUGIN_INDICADORES_MAX_GLPI_VERSION, '<');
}

function plugin_indicadores_check_config(bool $verbose = false): bool
{
    return true;
}
