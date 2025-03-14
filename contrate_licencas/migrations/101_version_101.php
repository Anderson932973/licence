<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Create domains table
        $CI->db->query("CREATE TABLE IF NOT EXISTS " . db_prefix() . "contrate_licencas_domains (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `license_id` int(11) NOT NULL,
            `domain` varchar(150) NOT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `license_id` (`license_id`),
            CONSTRAINT `" . db_prefix() . "contrate_licencas_domains_ibfk_1` 
            FOREIGN KEY (`license_id`) 
            REFERENCES `" . db_prefix() . "contrate_licencas` (`id`) 
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        // Migrate existing domains
        $CI->db->query("INSERT INTO " . db_prefix() . "contrate_licencas_domains (license_id, domain, created_at)
            SELECT id, domain, created_at FROM " . db_prefix() . "contrate_licencas 
            WHERE domain IS NOT NULL AND domain != ''");

        // Remove domain column from licenses table
        $CI->db->query("ALTER TABLE " . db_prefix() . "contrate_licencas DROP COLUMN domain");
    }
}
