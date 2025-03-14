<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contrate_licencas extends AdminController
{
    public function send_sms()
    {
        log_activity('Contrate Licenças: Iniciando envio de SMS');
        
        if (!has_permission('contrate_licencas', '', 'view')) {
            log_activity('Contrate Licenças: Acesso negado ao tentar enviar SMS');
            ajax_access_denied();
        }

        $client_id = $this->input->post('client_id');
        $license_key = $this->input->post('license_key');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $status = $this->input->post('status');

        log_activity('Contrate Licenças: Dados recebidos - Cliente ID: ' . $client_id . ', Licença: ' . $license_key);

        // Verificar se todos os dados necessários foram recebidos
        if (empty($client_id) || empty($license_key)) {
            log_activity('Contrate Licenças: Dados obrigatórios faltando');
            echo json_encode([
                'success' => false,
                'message' => 'Dados obrigatórios faltando'
            ]);
            return;
        }

        // Get client contact
        log_activity('Contrate Licenças: Buscando contatos do cliente ' . $client_id);
        $contacts = $this->lead_automation_sms_model->get_client_contacts([$client_id]);
        
        if (empty($contacts)) {
            log_activity('Contrate Licenças: Nenhum contato encontrado para o cliente ' . $client_id);
            echo json_encode([
                'success' => false,
                'message' => _l('no_contact_found_with_phone_number')
            ]);
            return;
        }

        log_activity('Contrate Licenças: ' . count($contacts) . ' contatos encontrados. Usando o primeiro contato: ' . $contacts[0]['phonenumber']);

        
        $message = sprintf(
            "🔑 *Sua Licença*\n\n" .
            "📝 Chave: %s\n\n" .
            "📅 *Período de Validade*\n" .
            "Início: %s\n" .
            "Fim: %s\n\n" ,
            $license_key,
            $start_date,
            $end_date,
        );

        log_activity('Contrate Licenças: Mensagem preparada: ' . $message);

        try {
            // Send SMS to first contact
            log_activity('Contrate Licenças: Tentando enviar SMS para ' . $contacts[0]['phonenumber']);
            $result = $this->lead_automation_sms_model->trigger_sms(
                $contacts[0]['phonenumber'],
                $message
            );

            if ($result) {
                log_activity('Contrate Licenças: SMS enviado com sucesso');
                echo json_encode([
                    'success' => true,
                    'message' => _l('sms_sent_successfully')
                ]);
            } else {
                log_activity('Contrate Licenças: Falha ao enviar SMS - Retorno false do trigger_sms');
                echo json_encode([
                    'success' => false,
                    'message' => _l('failed_to_send_sms')
                ]);
            }
        } catch (Exception $e) {
            log_activity('Contrate Licenças: Erro ao enviar SMS - ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao enviar SMS: ' . $e->getMessage()
            ]);
        }
    }

    public function __construct()
    {
        parent::__construct();
        require_once(dirname(__DIR__) . '/helpers/contrate_licencas_license_helper.php');
        $this->load->model('contrate_licencas_model');
        $this->load->model('clients_model');
        $this->load->model('lead_automation/lead_automation_sms_model');
    }

    public function index()
    {
        if (!has_permission('contrate_licencas', '', 'view')) {
            access_denied('contrate_licencas');
        }

        $data['title'] = _l('contrate_licencas');
        $this->load->view('contrate_licencas/manage', $data);
    }

    public function table()
    {
        if (!has_permission('contrate_licencas', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('contrate_licencas', 'tables/licenses'));
    }

    public function add_domain()
    {
        if (!has_permission('contrate_licencas', '', 'edit')) {
            ajax_access_denied();
        }

        $license_id = $this->input->post('license_id');
        $domain = $this->input->post('domain');

        $result = add_domain_to_license($license_id, $domain);

        if (isset($result['success'])) {
            echo json_encode([
                'success' => true,
                'message' => _l('domain_added_successfully')
            ]);
        } else {
            $error_message = _l('domain_already_exists');
            if ($result['error'] === 'max_domains_reached') {
                $error_message = _l('max_domains_reached');
            }
            
            echo json_encode([
                'success' => false,
                'message' => $error_message
            ]);
        }
    }

    public function remove_domain()
    {
        if (!has_permission('contrate_licencas', '', 'edit')) {
            ajax_access_denied();
        }

        $license_id = $this->input->post('license_id');
        $domain = $this->input->post('domain');

        if (remove_domain_from_license($license_id, $domain)) {
            echo json_encode([
                'success' => true,
                'message' => _l('domain_removed_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('domain_remove_failed')
            ]);
        }
    }

    public function license($id = '')
    {
        if (!has_permission('contrate_licencas', '', 'view')) {
            access_denied('contrate_licencas');
        }

        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            // Formatar as datas para o formato do banco de dados
            if (isset($post_data['start_date'])) {
                $post_data['start_date'] = to_sql_date($post_data['start_date'], true);
            }
            if (isset($post_data['end_date'])) {
                $post_data['end_date'] = to_sql_date($post_data['end_date'], true);
            }

            if ($id == '') {
                if (!has_permission('contrate_licencas', '', 'create')) {
                    access_denied('contrate_licencas');
                }
                $id = $this->contrate_licencas_model->add($post_data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('license')));
                    redirect(admin_url('contrate_licencas/license/' . $id));
                }
            } else {
                if (!has_permission('contrate_licencas', '', 'edit')) {
                    access_denied('contrate_licencas');
                }
                $success = $this->contrate_licencas_model->update($id, $post_data);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('license')));
                }
                redirect(admin_url('contrate_licencas/license/' . $id));
            }
        }

        if ($id == '') {
            $title = _l('add_new', _l('license_lowercase'));
        } else {
            $data['license'] = $this->contrate_licencas_model->get($id);
            $data['logs'] = $this->contrate_licencas_model->get_logs($id);
            $title = _l('edit', _l('license_lowercase'));
        }

        $data['title'] = $title;
        $this->load->view('contrate_licencas/license', $data);
    }

    public function delete($id)
    {
        if (!has_permission('contrate_licencas', '', 'delete')) {
            access_denied('contrate_licencas');
        }

        if ($this->contrate_licencas_model->delete($id)) {
            set_alert('success', _l('deleted', _l('license')));
        }

        redirect(admin_url('contrate_licencas'));
    }

    public function validate_license()
    {
        $license_key = $this->input->post('license_key');
        $domain = $this->input->post('domain');
        $module_name = $this->input->post('module_name');

        $result = $this->contrate_licencas_model->validate_license($license_key, $domain, $module_name);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
