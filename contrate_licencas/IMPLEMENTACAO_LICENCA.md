# Implementação do Sistema de Licenciamento para Módulos

Este documento descreve como implementar o sistema de licenciamento em módulos do CRM.

## Estrutura de Arquivos Necessária

A estrutura correta de arquivos e diretórios é crucial para o funcionamento do sistema de licença:

```
seu_modulo/
├── helpers/
│   └── seu_modulo_license_helper.php  # Nome do arquivo DEVE corresponder ao nome usado no load_helper()
├── models/
│   └── Seu_modulo_license_model.php
├── views/
│   └── settings.php
└── seu_modulo.php
```

### Observações Importantes sobre a Estrutura

1. O nome do arquivo helper deve ser igual ao nome usado no `load_helper()` (sem o sufixo '_helper')
2. O arquivo helper deve estar diretamente na pasta `helpers/` do módulo
3. Não crie subdiretórios dentro de `helpers/`
4. O nome do arquivo model deve começar com letra maiúscula

### Componentes Principais

1. **Helper de Licença** (`helpers/seu_modulo_license_helper.php`):
   - Funções auxiliares para verificação de licença
   - Deve conter funções como `is_module_licensed()`, `get_module_license_status()` e `check_module_license_or_die()`

2. **Modelo de Licença** (`models/Module_license_model.php`):
   - Classe responsável pela validação da licença com o servidor
   - Implementa a lógica de verificação e comunicação

3. **Configurações** (`views/settings.php`):
   - Interface para configuração da chave de licença
   - Exibição do status da licença
   - Configurações adicionais do módulo (ex: toggle de logs)

### Implementando Toggle de Configuração

Para adicionar um toggle de configuração (como o toggle de logs), siga este exemplo:

1. **Na View** (`views/settings.php`):
```php
<div class="form-group">
    <label for="modulo_toggle_name" class="control-label clearfix">
        <?php echo _l('modulo_toggle_label'); ?>
        <div class="pull-right">
            <div class="onoffswitch">
                <!-- Campo hidden para garantir valor 0 quando desativado -->
                <input type="hidden" name="settings[modulo_toggle_name]" value="0">
                <input type="checkbox" id="modulo_toggle_name" 
                       name="settings[modulo_toggle_name]" 
                       class="onoffswitch-checkbox" 
                       <?php echo get_option('modulo_toggle_name', 1) == 1 ? 'checked' : ''; ?> 
                       value="1">
                <label class="onoffswitch-label" for="modulo_toggle_name"></label>
            </div>
        </div>
    </label>
</div>
```

2. **No Controller**:
```php
// Salva configurações
if (isset($data['settings'])) {
    foreach ($data['settings'] as $key => $value) {
        update_option($key, $value);
    }
}
```

3. **Uso no Código**:
```php
// Verifica estado do toggle
if (get_option('modulo_toggle_name', 1) == 1) {
    // Executa ação quando ativado
} else {
    // Executa ação quando desativado
}
```

Notas importantes:
- Use o campo hidden para garantir que o valor 0 seja enviado quando o toggle estiver desativado
- O segundo parâmetro de `get_option()` é o valor padrão caso a opção não exista
- Use nomes descritivos para as opções, prefixando com o nome do módulo

## Implementação Passo a Passo

### 1. Helper de Licença

Crie um arquivo `modulo_license_helper.php` com as seguintes funções:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verifica se o módulo está licenciado
 * @return boolean
 */
function is_module_licensed()
{
    $CI = &get_instance();
    $CI->load->model('module/module_license_model');
    return $CI->module_license_model->is_valid();
}

/**
 * Retorna o status detalhado da licença
 * @return array
 */
function get_module_license_status()
{
    $CI = &get_instance();
    $CI->load->model('module/module_license_model');
    return $CI->module_license_model->validate_module_license();
}

/**
 * Verifica licença ou redireciona
 * @return void
 */
function check_module_license_or_die()
{
    if (!is_module_licensed()) {
        $license_status = get_module_license_status();
        set_alert('warning', 'Módulo: ' . $license_status['message']);
        redirect(admin_url('module/settings'), 'refresh');
        exit;
    }
}
```

### 2. Modelo de Licença

Crie um arquivo `Module_license_model.php`:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Module_license_model extends App_Model
{
    private $module_name = 'nome_do_modulo';
    private $license_server = 'https://contratecrm.contratesolutions.com.br';

    public function validate_module_license()
    {
        $license_key = get_option($this->module_name . '_license_key');
        $domain = preg_replace('#^https?://#', '', rtrim(site_url(), '/'));
        
        if (empty($license_key)) {
            return [
                'valid' => false,
                'message' => 'Chave de licença não configurada'
            ];
        }

        $postData = [
            'license_key' => $license_key,
            'domain' => $domain,
            'module' => $this->module_name
        ];

        $url = $this->license_server . '/validate_license.php';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'valid' => false,
                'message' => 'Erro ao validar licença'
            ];
        }

        $result = json_decode($response, true);
        
        if (!$result) {
            return [
                'valid' => false,
                'message' => 'Resposta inválida do servidor'
            ];
        }

        return [
            'valid' => $result['valid'] ?? false,
            'message' => $result['message'] ?? 'Erro ao validar licença'
        ];
    }

    public function is_valid()
    {
        $result = $this->validate_module_license();
        return $result['valid'];
    }
}
```

### 3. Página de Configurações

Crie um arquivo `settings.php` na pasta views. Inclua o formulário de licença e o modal de ticket de suporte:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('seu_modulo/settings/save'), ['id'=>'module-settings-form']); ?>
                        <h4 class="no-margin">
                            <?php echo _l('configurações_do_modulo'); ?>
                            <small class="pull-right">
                                <?php $license_status = get_module_license_status(); ?>
                                <?php if($license_status['valid']): ?>
                                    <span class="label label-success">Licença Válida</span>
                                <?php else: ?>
                                    <span class="label label-danger">Licença Inválida - <?php echo $license_status['message']; ?></span>
                                <?php endif; ?>
                            </small>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('module_license_key', 'Chave de Licença', get_option('module_license_key')); ?>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <p>Para obter uma licença válida, entre em contato com o suporte:</p>
                            <ul>
                                <li>Email: suporte@contratesolutions.com.br</li>
                                <li>WhatsApp: <a href="https://wa.me/558821568397" target="_blank">(88) 2156-8397</a></li>
                                <li><button type="button" class="btn btn-info" data-toggle="modal" data-target="#ticketModal">Abrir Ticket de Suporte</button></li>
                            </ul>
                        </div>

                        <!-- Modal do Ticket -->
                        <div class="modal fade" id="ticketModal" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel">
                            <div class="modal-dialog modal-lg" role="document" style="width: 90%; max-width: 1000px;">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="ticketModalLabel">Ticket de Suporte</h4>
                                    </div>
                                    <div class="modal-body" style="padding: 0;">
                                        <iframe width="100%" height="850" src="https://contratecrm.contratesolutions.com.br/forms/ticket" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-info">
                                <?php echo _l('save'); ?>
                            </button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    var form = $('#module-settings-form');
    
    appValidateForm(form, {
        module_license_key: 'required'
    });

    form.on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serialize();

        $.post(form.attr('action'), data).done(function(response) {
            response = JSON.parse(response);
            
            if (response.success) {
                alert_float('success', response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                alert_float('danger', response.message);
            }
        });

        return false;
    });
});
</script>
```

#### Características do Modal de Ticket

1. **Botão de Abertura**:
   - Localizado na lista de opções de contato
   - Estilo consistente com o tema (btn-info)
   - Trigger via data-attributes do Bootstrap

2. **Modal**:
   - Tamanho grande (modal-lg)
   - Largura personalizada (90% da tela)
   - Limite máximo de largura (1000px)
   - Padding removido do corpo para melhor visualização do iframe

3. **iFrame**:
   - Largura responsiva (100%)
   - Altura fixa (850px)
   - Sem bordas (frameborder="0")
   - Suporte a tela cheia (allowfullscreen)

4. **Benefícios**:
   - Interface limpa e organizada
   - Fácil acesso ao suporte
   - Experiência integrada sem sair do sistema
   - Melhor utilização do espaço da tela

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('module/settings/save'), ['id'=>'module-settings-form']); ?>
                        <h4 class="no-margin">
                            <?php echo _l('configurações_do_modulo'); ?>
                            <small class="pull-right">
                                <?php $license_status = get_module_license_status(); ?>
                                <?php if($license_status['valid']): ?>
                                    <span class="label label-success">Licença Válida</span>
                                <?php else: ?>
                                    <span class="label label-danger">Licença Inválida - <?php echo $license_status['message']; ?></span>
                                <?php endif; ?>
                            </small>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('module_license_key', 'Chave de Licença', get_option('module_license_key')); ?>
                            </div>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-info">
                                <?php echo _l('save'); ?>
                            </button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
```

## Implementação do Menu de Licença

Existem duas formas de implementar o menu de licença, dependendo da estrutura do seu módulo:

### 1. Como Menu Principal

Se seu módulo tem seu próprio menu principal, adicione o submenu de licença assim:

```php
function module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('seu_modulo', '', 'view')) {
        // Menu principal do módulo
        $CI->app_menu->add_sidebar_menu_item('seu-modulo', [
            'name'     => _l('seu_modulo'),
            'href'     => admin_url('seu_modulo'),
            'icon'     => 'fa fa-magic',
            'position' => 30,
        ]);

        // Submenu de licença
        $CI->app_menu->add_sidebar_menu_item('seu-modulo-license', [
            'name'     => _l('Licença Seu Módulo'),
            'href'     => admin_url('seu_modulo/settings'),
            'icon'     => 'fa fa-key',
            'position' => 31,
            'parent'   => 'seu-modulo',
        ]);
    }
}
```

### 2. Como Submenu de Outro Menu

Se seu módulo é parte de outro menu (como o menu Leads), adicione assim:

```php
function module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('seu_modulo', '', 'view')) {
        // Adiciona como submenu de outro menu (exemplo: 'leads')
        $CI->app_menu->add_sidebar_children_item('menu_pai', [
            'slug'     => 'seu_modulo_license',
            'name'     => _l('Licença Seu Módulo'),
            'href'     => admin_url('seu_modulo/settings'),
            'icon'     => 'fa fa-key',
            'position' => 12, // Ajuste conforme necessário
        ]);
    }
}
```

### Observações Importantes

1. Substitua 'seu_modulo' pelo nome do seu módulo
2. Ajuste a 'position' conforme a ordem desejada no menu
3. O ícone padrão para licença é 'fa fa-key'
4. Sempre verifique as permissões antes de mostrar o menu
5. O link deve apontar para a página de configurações (settings)

## Carregamento do Helper de Licença

O helper de licença deve ser carregado apenas nos controllers e arquivos onde será utilizado, evitando carregamento global desnecessário.

### Carregamento no Controller

A melhor prática é carregar o helper no construtor do controller que precisa da funcionalidade de licença:

Nos controllers que precisam verificar a licença, especialmente no Settings controller:

```php
class Settings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('seu_modulo_license_model');
        $this->load->helper('seu_modulo_license');
    }

    // ...
}
```

### Observações Importantes

1. EVITE fazer o carregamento global do helper no arquivo principal do módulo
2. Carregue o helper apenas nos controllers e arquivos que realmente precisam das funções de licença
3. O carregamento global pode causar conflitos quando outros módulos são acessados
4. Certifique-se de que o helper está carregado antes de usar qualquer função de licença
5. Se tiver problemas com funções não definidas, verifique se o helper está sendo carregado no local correto

## Implementação da Verificação de Licença

### 1. No Controller Principal

A verificação de licença deve ser implementada no construtor do controller principal do módulo:

```php
class Seu_modulo extends AdminController
{
    public function __construct()
    {
        if (!is_cli()) {
            parent::__construct();
            
            // Carrega o helper de licença
            $this->load->helper('seu_modulo_license');
            
            // Verifica se o módulo está licenciado
            check_seu_modulo_license_or_die();
            
            // Outras verificações de permissão
            if (!has_permission('seu_modulo', '', 'view')) {
                access_denied('Seu Modulo');
            }
            
            // Carrega os modelos necessários
            $this->load->model('seu_modulo_model');
        }
    }
}
```

### 2. Em Controllers Adicionais

Se seu módulo tem controllers adicionais que precisam ser protegidos, implemente a verificação neles também:

```php
class Outro_controller extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Carrega o helper de licença
        $this->load->helper('seu_modulo_license');
        
        // Verifica se o módulo está licenciado
        check_seu_modulo_license_or_die();
    }
}
```

### Observações Importantes

1. A verificação de licença deve ser feita ANTES de qualquer outra verificação de permissão
2. Implemente a verificação em TODOS os controllers que fazem parte do módulo
3. Não esqueça de carregar o helper antes de usar a função de verificação
4. A verificação não é necessária em chamadas CLI (por isso o if (!is_cli()))
5. Se a licença for inválida, o usuário será redirecionado para a página de configurações

## Como Usar

1. **Copie os Arquivos Base**:
   - Copie os arquivos modelo acima para seu módulo
   - Substitua "module" pelo nome do seu módulo
   - Ajuste o `module_name` no modelo de licença

2. **Implemente a Verificação**:
   - Adicione a verificação de licença nos pontos de entrada do módulo:
   ```php
   check_module_license_or_die();
   ```

3. **Configure a Rota de Settings**:
   - Crie um controller para gerenciar as configurações
   - Implemente o salvamento da chave de licença

4. **Teste a Implementação**:
   - Verifique se a validação está funcionando
   - Teste casos de licença válida e inválida
   - Confirme se o bloqueio funciona corretamente

## Observações Importantes

1. **Segurança**:
   - Nunca exponha a chave de licença publicamente
   - Use HTTPS para comunicação com o servidor de licenças
   - Valide todas as entradas de usuário

2. **Performance**:
   - Considere implementar cache para resultados de validação
   - Evite validações desnecessárias em cada requisição

3. **UX**:
   - Forneça mensagens claras sobre o status da licença
   - Facilite o processo de inserção da chave de licença

4. **Manutenção**:
   - Mantenha logs de validações para debug
   - Implemente um sistema de notificação para licenças próximas do vencimento

## Suporte e Licenciamento

Para obter uma licença válida, entre em contato com o suporte:

- Email: suporte@contratesolutions.com.br
- WhatsApp: [(88) 2156-8397](https://wa.me/558821568397)
- Ticket de Suporte: [Abrir Ticket](https://contratecrm.contratesolutions.com.br/forms/ticket)
