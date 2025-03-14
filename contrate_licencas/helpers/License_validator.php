<?php

defined('BASEPATH') or exit('No direct script access allowed');

class License_validator
{
    private $CI;
    private $license_key;
    private $module_name;
    private $domain;
    private $webhook_url;
    private $cache_key;
    private $cache_duration = 86400; // 24 horas em segundos

    public function __construct($license_key, $module_name)
    {
        $this->CI =& get_instance();
        $this->license_key = $license_key;
        $this->module_name = $module_name;
        $this->domain = preg_replace("(^https?://)", "", base_url());
        $this->domain = rtrim(str_replace('www.', '', $this->domain), '/');
        $this->webhook_url = 'https://contratecrm.contratesolutions.com.br/modules/contrate_licencas/License';
        $this->cache_key = 'license_' . md5($this->license_key . $this->domain . $this->module_name);
        
        // Carregar bibliotecas necessárias
        $this->CI->load->driver('cache', array('adapter' => 'file'));
    }

    public function validate()
    {
        try {
            // Verificar cache primeiro
            $cached_result = $this->CI->cache->get($this->cache_key);
            if ($cached_result !== false) {
                $result = json_decode($cached_result, true);
                if (!$result['valid']) {
                    throw new Exception($result['message']);
                }
                return true;
            }

            // Preparar dados para envio
            $post_data = array(
                'license_key' => $this->license_key,
                'domain' => $this->domain,
                'module_name' => $this->module_name
            );

            // Inicializar cURL
            $ch = curl_init($this->webhook_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // Executar requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception('Erro ao validar licença. HTTP Code: ' . $http_code);
            }

            $result = json_decode($response, true);
            if (!$result) {
                throw new Exception('Resposta inválida do servidor de licenças');
            }

            // Armazenar em cache
            $this->CI->cache->save($this->cache_key, $response, $this->cache_duration);

            if (!$result['valid']) {
                throw new Exception($result['message']);
            }

            return true;

        } catch (Exception $e) {
            // Log do erro
            log_activity('Erro de Licença - ' . $this->module_name . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function get_expiry_date()
    {
        $cached_result = $this->CI->cache->get($this->cache_key);
        if ($cached_result !== false) {
            $result = json_decode($cached_result, true);
            return isset($result['expiry_date']) ? $result['expiry_date'] : null;
        }
        return null;
    }
}
