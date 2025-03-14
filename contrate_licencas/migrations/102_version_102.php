<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Adicionar campo max_domains na tabela de licenças
        $CI->db->query("ALTER TABLE " . db_prefix() . "contrate_licencas 
            ADD COLUMN max_domains INT DEFAULT 1 AFTER module_name");

        // Atualizar licenças existentes para ter limite de 5 domínios
        $CI->db->query("UPDATE " . db_prefix() . "contrate_licencas SET max_domains = 5");
    }
}
