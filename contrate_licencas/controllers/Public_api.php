<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Public_api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contrate_licencas/contrate_licencas_model');
        
        // Permite CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Define tipo de resposta como JSON
        header('Content-Type: application/json');
    }

    public function validate_license()
    {
        log_activity('Public API License - Request Method: ' . $_SERVER['REQUEST_METHOD']);
        log_activity('Public API License - POST Data: ' . json_encode($_POST));

        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->output->set_status_header(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Pega os dados do POST
        $license_key = $this->input->post('license_key');
        $domain = $this->input->post('domain');
        $module = $this->input->post('module');

        // Valida dados obrigatórios
        if (empty($license_key) || empty($domain) || empty($module)) {
            $this->output->set_status_header(400);
            echo json_encode([
                'valid' => false,
                'message' => 'Dados incompletos'
            ]);
            return;
        }

        // Valida a licença
        $result = $this->contrate_licencas_model->validate_license($license_key, $domain, $module);

        // Retorna o resultado
        echo json_encode($result);
    }
}
