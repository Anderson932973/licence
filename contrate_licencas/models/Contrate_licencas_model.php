<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contrate_licencas_model extends App_Model
{
    private $encryption_key;

    public function __construct()
    {
        parent::__construct();
        $this->encryption_key = get_option('encryption_key');
    }

    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'contrate_licencas')->row();
        }
        return $this->db->get(db_prefix() . 'contrate_licencas')->result_array();
    }

    public function add($data)
    {
        $data['license_key'] = $this->generate_license_key();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert(db_prefix() . 'contrate_licencas', $data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            $this->log_activity($insert_id, 'created', 'Licença criada');
            return $insert_id;
        }
        return false;
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $success = $this->db->update(db_prefix() . 'contrate_licencas', $data);
        
        if ($success) {
            $this->log_activity($id, 'updated', 'Licença atualizada');
            return true;
        }
        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $success = $this->db->delete(db_prefix() . 'contrate_licencas');
        
        if ($success) {
            $this->log_activity($id, 'deleted', 'Licença excluída');
            return true;
        }
        return false;
    }

    public function validate_license($license_key, $domain, $module_name)
    {
        $this->db->where('license_key', $license_key);
        $this->db->where('module_name', $module_name);
        $this->db->where('status', 'active');
        $license = $this->db->get(db_prefix() . 'contrate_licencas')->row();

        if (!$license) {
            return [
                'valid' => false,
                'message' => 'Licença inválida ou não encontrada'
            ];
        }

        // Check if domain is allowed for this license
        $this->db->where('license_id', $license->id);
        $this->db->where('domain', $domain);
        $domain_allowed = $this->db->get(db_prefix() . 'contrate_licencas_domains')->row();

        if (!$domain_allowed) {
            return [
                'valid' => false,
                'message' => 'Domínio não autorizado para esta licença'
            ];
        }

        $now = strtotime(date('Y-m-d H:i:s'));
        $end_date = strtotime($license->end_date);

        if ($now > $end_date) {
            $this->update($license->id, ['status' => 'expired']);
            return [
                'valid' => false,
                'message' => sprintf('O período de uso do módulo expirou em %s', 
                    date('d/m/Y H:i:s', $end_date))
            ];
        }

        $this->log_activity($license->id, 'validated', 'Licença validada com sucesso');
        return [
            'valid' => true,
            'message' => 'Licença válida',
            'expiry_date' => date('d/m/Y H:i:s', $end_date)
        ];
    }

    private function generate_license_key()
    {
        $key = md5(uniqid(mt_rand(), true) . $this->encryption_key . time());
        return substr($key, 0, 8) . '-' . 
               substr($key, 8, 4) . '-' . 
               substr($key, 12, 4) . '-' . 
               substr($key, 16, 4) . '-' . 
               substr($key, 20, 12);
    }

    private function log_activity($license_id, $action, $description)
    {
        $data = [
            'license_id' => $license_id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert(db_prefix() . 'contrate_licencas_log', $data);
    }

    public function get_logs($license_id)
    {
        $this->db->where('license_id', $license_id);
        $this->db->order_by('created_at', 'desc');
        return $this->db->get(db_prefix() . 'contrate_licencas_log')->result_array();
    }
}
