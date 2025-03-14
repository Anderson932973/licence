<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open($this->uri->uri_string()); ?>
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-success" onclick="refreshPage()">
                                    <i class="fa fa-refresh"></i> <?php echo _l('refresh_page'); ?>
                                </button>
                            </div>
                        </div>
                        <script>
                        function refreshPage() {
                            location.reload();
                        }
                        </script>
                        <?php if(isset($license)){ ?>
                        <p class="text-muted">
                            <?php echo _l('license_key'); ?>: <span id="license-key"><?php echo $license->license_key; ?></span>
                            <button type="button" class="btn btn-xs btn-primary mleft5" onclick="copyLicenseKey()" data-toggle="tooltip" title="<?php echo _l('copy_to_clipboard'); ?>"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-xs btn-info mleft5" onclick="sendLicenseSMS()" data-toggle="tooltip" title="<?php echo _l('send_sms'); ?>"><i class="fa fa-mobile"></i></button>
                            <script>
                            function copyLicenseKey() {
                                var licenseKey = document.getElementById('license-key').innerText;
                                navigator.clipboard.writeText(licenseKey).then(function() {
                                    alert('<?php echo _l('copied_to_clipboard'); ?>');
                                });
                            }
                            
                            function sendLicenseSMS() {
                                var data = {
                                    client_id: '<?php echo $license->client_id; ?>',
                                    license_key: '<?php echo $license->license_key; ?>',
                                    start_date: '<?php echo _d($license->start_date); ?>',
                                    end_date: '<?php echo _d($license->end_date); ?>',
                                    status: '<?php echo $license->status; ?>'
                                };
                                
                                $.post(admin_url + 'contrate_licencas/send_sms', data)
                                    .done(function(response) {
                                        response = JSON.parse(response);
                                        if (response.success) {
                                            alert_float('success', response.message);
                                        } else {
                                            alert_float('danger', response.message);
                                        }
                                    })
                                    .fail(function() {
                                        alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
                                    });
                            }
                            </script>
                        </p>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $this->load->model('clients_model');
                                $clients = $this->clients_model->get('', [db_prefix() . 'clients.active' => 1]);
                                $clients_array = array_map(function($client) {
                                    return [
                                        'id' => $client['userid'],
                                        'name' => $client['company']
                                    ];
                                }, $clients);
                                echo render_select('client_id', $clients_array, ['id', 'name'], 'client', isset($license) ? $license->client_id : '');
                                echo render_input('module_name', 'module_name', isset($license) ? $license->module_name : '');
                                echo render_input('max_domains', 'max_domains', isset($license) ? $license->max_domains : '5', 'number');
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_date_input('start_date', 'start_date', isset($license) ? _d($license->start_date) : _d(date('Y-m-d')));
                                echo render_date_input('end_date', 'end_date', isset($license) ? _d($license->end_date) : '');
                                echo render_select('status', [
                                    ['id' => 'active', 'name' => _l('active')],
                                    ['id' => 'inactive', 'name' => _l('inactive')],
                                    ['id' => 'expired', 'name' => _l('expired')]
                                ], ['id', 'name'], 'status', isset($license) ? $license->status : 'active');
                                ?>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
            <?php if(isset($license)){ ?>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('domains'); ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php 
                        $domains_info = get_license_domains($license->id);
                        $domains = $domains_info['domains'];
                        ?>
                        <p class="text-muted">
                            <?php echo sprintf(_l('domains_available'), 
                                $domains_info['current_count'], 
                                $domains_info['max_domains']); ?>
                        </p>
                        
                        <!-- Lista de domínios -->
                        <div id="domains-list">
                            <?php foreach($domains as $domain) { ?>
                                <div class="domain-item">
                                    <span><?php echo $domain['domain']; ?></span>
                                    <button type="button" class="btn btn-danger btn-xs pull-right" 
                                        onclick="removeDomain('<?php echo $domain['domain']; ?>')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <!-- Formulário para adicionar domínio -->
                        <div class="form-group mtop15">
                            <div class="input-group">
                                <input type="text" id="new-domain" class="form-control" 
                                    placeholder="<?php echo _l('enter_domain'); ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-info" type="button" onclick="addDomain()">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                        <script>
                        function addDomain() {
                            var domain = $('#new-domain').val();
                            if (!domain) return;
                            
                            $.post(admin_url + 'contrate_licencas/add_domain', {
                                license_id: '<?php echo $license->id; ?>',
                                domain: domain
                            }).done(function(response) {
                                response = JSON.parse(response);
                                if (response.success) {
                                    // Adiciona o domínio à lista
                                    var html = '<div class="domain-item">'
                                        + '<span>' + domain + '</span>'
                                        + '<button type="button" class="btn btn-danger btn-xs pull-right"'
                                        + ' onclick="removeDomain(\'' + domain + '\')">'                                        + '<i class="fa fa-trash"></i>'
                                        + '</button>'
                                        + '</div>';
                                    $('#domains-list').append(html);
                                    $('#new-domain').val('');
                                } else {
                                    alert(response.message);
                                }
                            });
                        }
                        
                        function removeDomain(domain) {
                            if (!confirm('<?php echo _l('confirm_action'); ?>')) return;
                            
                            $.post(admin_url + 'contrate_licencas/remove_domain', {
                                license_id: '<?php echo $license->id; ?>',
                                domain: domain
                            }).done(function(response) {
                                response = JSON.parse(response);
                                if (response.success) {
                                    // Remove o domínio da lista
                                    $('#domains-list').find('span:contains("' + domain + '")').parent().remove();
                                } else {
                                    alert(response.message);
                                }
                            });
                        }
                        </script>
                        
                        <style>
                        .domain-item {
                            padding: 8px;
                            border-bottom: 1px solid #ddd;
                            margin-bottom: 5px;
                        }
                        .domain-item:last-child {
                            border-bottom: none;
                        }
                        </style>
                        <h4 class="no-margin"><?php echo _l('license_logs'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <div class="activity-feed">
                        <?php foreach($logs as $log){ ?>
                            <div class="feed-item">
                                <div class="date"><?php echo _dt($log['created_at']); ?></div>
                                <div class="text">
                                    <p class="bold"><?php echo $log['action']; ?></p>
                                    <?php echo $log['description']; ?><br/>
                                    <small class="text-muted"><?php echo _l('ip').': '.$log['ip_address']; ?></small>
                                </div>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
