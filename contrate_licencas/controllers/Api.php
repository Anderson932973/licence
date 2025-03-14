<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contrate_licencas_model');
        
        // Desabilita CSRF para endpoints públicos
        if ($this->uri->segment(2) === 'api' && $this->uri->segment(3) === 'validate_license') {
            $this->config->set_item('csrf_protection', false);
        }
    }

    public function validate_license()
    {
        // Headers para CORS e JSON
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        // Lê dados do POST ou input JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        log_activity('API License - Input: ' . $input);

        // Se não for JSON, tenta POST normal
        if (!$data) {
            $data = $this->input->post();
        }

        // Valida dados obrigatórios
        if (empty($data['license_key']) || empty($data['domain']) || empty($data['module'])) {
            $this->output->set_status_header(400);
            echo json_encode([
                'valid' => false,
                'message' => 'Dados incompletos'
            ]);
            return;
        }

        log_activity(sprintf(
            'API License - Validando - Key: %s, Domain: %s, Module: %s',
            $data['license_key'],
            $data['domain'],
            $data['module']
        ));

        // Valida a licença
        $result = $this->contrate_licencas_model->validate_license(
            $data['license_key'],
            $data['domain'],
            $data['module']
        );

        log_activity('API License - Resultado: ' . json_encode($result));

        // Retorna o resultado
        $this->output->set_status_header($result['valid'] ? 200 : 404);
        echo json_encode($result);
    }
}
