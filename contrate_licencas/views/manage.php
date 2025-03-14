<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (has_permission('contrate_licencas', '', 'create')) { ?>
                            <a href="<?php echo admin_url('contrate_licencas/license'); ?>" class="btn btn-info pull-left display-block">
                                <?php echo _l('new_license'); ?>
                            </a>
                            <?php } ?>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="clearfix"></div>
                        <?php
                        $table_data = [
                            _l('license_key'),
                            _l('module_name'),
                            _l('client'),
                            _l('status'),
                            _l('start_date'),
                            _l('end_date')
                        ];

                        if (has_permission('contrate_licencas', '', 'edit') || has_permission('contrate_licencas', '', 'delete')) {
                            $table_data[] = _l('options');
                        }

                        render_datatable($table_data, 'licenses');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-licenses', admin_url + 'contrate_licencas/table', [6], [6], undefined, [4, 'desc']);
});
</script>
