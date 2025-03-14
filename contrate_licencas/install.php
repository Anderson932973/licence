<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'contrate_licencas')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'contrate_licencas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `license_key` varchar(255) NOT NULL,
        `domain` varchar(255) NOT NULL,
        `module_name` varchar(100) NOT NULL,
        `client_id` int(11) NOT NULL,
        `status` varchar(20) NOT NULL DEFAULT "active",
        `start_date` datetime NOT NULL,
        `end_date` datetime NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `license_key` (`license_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
}

if (!$CI->db->table_exists(db_prefix() . 'contrate_licencas_log')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'contrate_licencas_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `license_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `description` text,
        `ip_address` varchar(45) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `license_id` (`license_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
}
