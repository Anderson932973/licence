<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Contrate Licenças
Description: Sistema de gerenciamento de licenças para módulos
Version: 1.0.0
Requires at least: 2.3.*
Author: Contrate Solutions
Author URI: https://contratesolutions.com.br
*/

hooks()->add_action('admin_init', 'contrate_licencas_module_init_menu_items');
hooks()->add_action('admin_init', 'contrate_licencas_permissions');

/**
 * Register activation module hook
 */
register_activation_hook('contrate_licencas', 'contrate_licencas_activation_hook');

function contrate_licencas_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files
 */
register_language_files('contrate_licencas', ['contrate_licencas']);

/**
 * Init module menu items in admin_init hook
 * @return null
 */
function contrate_licencas_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('contrate_licencas', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('contrate_licencas', [
            'name'     => _l('contrate_licencas'),
            'href'     => admin_url('contrate_licencas'),
            'icon'     => 'fa fa-key',
            'position' => 30,
        ]);
    }
}

/**
 * Register new permissions
 */
function contrate_licencas_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('contrate_licencas', $capabilities, _l('contrate_licencas'));
}
