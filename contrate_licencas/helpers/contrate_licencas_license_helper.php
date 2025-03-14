<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Add domain to license
 * @param int $license_id
 * @param string $domain
 * @return bool
 */
function add_domain_to_license($license_id, $domain) {
    $CI =& get_instance();
    
    // Get license details
    $CI->db->where('id', $license_id);
    $license = $CI->db->get(db_prefix() . 'contrate_licencas')->row();
    
    if (!$license) {
        return false;
    }
    
    // Count current domains
    $CI->db->where('license_id', $license_id);
    $current_domains = $CI->db->count_all_results(db_prefix() . 'contrate_licencas_domains');
    
    if ($current_domains >= $license->max_domains) {
        return ['error' => 'max_domains_reached'];
    }
    
    // Verify if domain already exists for this license
    $CI->db->where('license_id', $license_id);
    $CI->db->where('domain', $domain);
    $exists = $CI->db->get(db_prefix() . 'contrate_licencas_domains')->row();
    
    if ($exists) {
        return ['error' => 'domain_already_exists'];
    }
    
    $success = $CI->db->insert(db_prefix() . 'contrate_licencas_domains', [
        'license_id' => $license_id,
        'domain' => $domain,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($success) {
        return ['success' => true];
    }
    
    return ['error' => 'insert_failed'];
}

/**
 * Remove domain from license
 * @param int $license_id
 * @param string $domain
 * @return bool
 */
function remove_domain_from_license($license_id, $domain) {
    $CI =& get_instance();
    $CI->db->where('license_id', $license_id);
    $CI->db->where('domain', $domain);
    return $CI->db->delete(db_prefix() . 'contrate_licencas_domains');
}

/**
 * Get all domains for a license
 * @param int $license_id
 * @return array
 */
function get_license_domains($license_id) {
    $CI =& get_instance();
    
    // Get license details
    $CI->db->where('id', $license_id);
    $license = $CI->db->get(db_prefix() . 'contrate_licencas')->row();
    
    if (!$license) {
        return [];
    }
    
    // Get domains
    $CI->db->where('license_id', $license_id);
    $domains = $CI->db->get(db_prefix() . 'contrate_licencas_domains')->result_array();
    
    // Count current domains
    $current_count = count($domains);
    
    return [
        'domains' => $domains,
        'max_domains' => $license->max_domains,
        'current_count' => $current_count,
        'available_slots' => $license->max_domains - $current_count
    ];
}
