<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Contrate Licenças
Description: Sistema de gerenciamento de licenças para módulos
Version: 1.0.2
Requires at least: 2.3.*
Author: Contrate Solutions
Author URI: https://contratesolutions.com.br
*/

// Definir constantes do módulo primeiro
define('CONTRATE_LICENCAS_MODULE_NAME', 'contrate_licencas');

require_once(__DIR__ . '/init.php');

/**
 * Ativa o módulo no Perfex CRM
 */
function contrate_licencas_activation()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Desativa o módulo no Perfex CRM
 */
function contrate_licencas_deactivation()
{
    // Código de desativação, se necessário
}

/**
 * Desinstala o módulo no Perfex CRM
 */
function contrate_licencas_uninstall()
{
    // Remover tabelas e dados se necessário
    $CI = &get_instance();
    
    // Remover tabelas
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'contrate_licencas');
    $CI->db->query('DROP TABLE IF EXISTS ' . db_prefix() . 'contrate_licencas_log');
}

// Registrar hooks de ativação/desativação
register_activation_hook(CONTRATE_LICENCAS_MODULE_NAME, 'contrate_licencas_activation');
register_deactivation_hook(CONTRATE_LICENCAS_MODULE_NAME, 'contrate_licencas_deactivation');
register_uninstall_hook(CONTRATE_LICENCAS_MODULE_NAME, 'contrate_licencas_uninstall');
