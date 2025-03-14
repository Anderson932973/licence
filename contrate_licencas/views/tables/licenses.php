<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'license_key',
    'module_name',
    'client_id',
    'status',
    'start_date',
    'end_date',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'contrate_licencas';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'contrate_licencas.client_id',
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], ['id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // License Key
    $row[] = '<a href="' . admin_url('contrate_licencas/license/' . $aRow['id']) . '">' . $aRow['license_key'] . '</a>';
    

    
    // Module Name
    $row[] = $aRow['module_name'];
    
    // Client
    $client = get_client($aRow['client_id']);
    $row[] = $client ? '<a href="' . admin_url('clients/client/' . $aRow['client_id']) . '">' . $client->company . '</a>' : '';
    
    // Status
    $status_class = '';
    switch ($aRow['status']) {
        case 'active':
            $status_class = 'success';
            break;
        case 'inactive':
            $status_class = 'warning';
            break;
        case 'expired':
            $status_class = 'danger';
            break;
    }
    $row[] = '<span class="label label-' . $status_class . '">' . _l($aRow['status']) . '</span>';
    
    // Start Date
    $row[] = _d($aRow['start_date']);
    
    // End Date
    $row[] = _d($aRow['end_date']);
    
    // Options
    $options = '';
    if (has_permission('contrate_licencas', '', 'edit')) {
        $options .= icon_btn('contrate_licencas/license/' . $aRow['id'], 'pencil-square-o');
    }
    if (has_permission('contrate_licencas', '', 'delete')) {
        $options .= icon_btn('contrate_licencas/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
    }
    $row[] = $options;

    $output['aaData'][] = $row;
}
