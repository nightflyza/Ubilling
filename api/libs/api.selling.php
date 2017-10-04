<?php

/**
 * Selling management API
 */

/**
 * Returns available selling lister with some controls
 *
 * @return string
 */
function web_SellingLister() {
    $selling = zb_GetAllSellingData();

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Selling name'));
    $cells.= wf_TableCell(__('Selling address'));
    $cells.= wf_TableCell(__('Selling geo data'));
    $cells.= wf_TableCell(__('Selling contact'));
    $cells.= wf_TableCell(__('Selling count cards'));
    $cells.= wf_TableCell(__('Selling comment'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($selling)) {
        foreach ($selling as $row) {
            $cells = wf_TableCell($row['id']);
            $cells.= wf_TableCell($row['name']);
            $cells.= wf_TableCell($row['address']);
            $cells.= wf_TableCell($row['geo']);
            $cells.= wf_TableCell($row['contact']);
            $cells.= wf_TableCell($row['count_cards']);
            $cells.= wf_TableCell($row['comment']);

            $acts = wf_JSAlert('?module=selling&action=delete&id=' . $row['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $acts.= wf_JSAlert('?module=selling&action=edit&id=' . $row['id'], web_edit_icon(), 'Are you serious') . ' ';

            $cells.= wf_TableCell($acts);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }
    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    return $result;
}

/**
 * Returns selling creation form
 *
 * @return string
 */
function web_SellingCreateForm() {
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('new_selling[name]', __('Selling name') . $sup, '', true);
    $inputs.= wf_TextInput('new_selling[address]', __('Selling address'), '', true);
    $inputs.= wf_TextInput('new_selling[geo]', __('Selling geo data'), '', true, 20, 'geo');
    $inputs.= wf_TextInput('new_selling[contact]', __('Selling contact'), '', true);
    $inputs.= wf_TextArea('new_selling[comment]', __('Selling comment'), '', true);
    $inputs.= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return $form;
}

/**
 * Returns existing selling editing form
 *
 * @param int $sellingId
 *
 * @return string
 */
function web_SellingEditForm($sellingId) {
    $data = zb_GetSellingData($sellingId);

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('edit_selling[name]', __('Selling name') . $sup, $data['name'], true);
    $inputs.= wf_TextInput('edit_selling[address]', __('Selling address'), $data['address'], true);
    $inputs.= wf_TextInput('edit_selling[geo]', __('Selling geo data'), $data['geo'], true, 20 , 'geo');
    $inputs.= wf_TextInput('edit_selling[contact]', __('Selling contact'), $data['contact'], true);
    $inputs.= wf_TextArea('edit_selling[comment]', __('Selling comment'), $data['comment'], true);
    $inputs.= wf_Submit(__('Save'));

    $form = wf_Form('', 'POST', $inputs, 'glamour');
    $form.= wf_BackLink('?module=selling');

    return $form;
}

/**
 * Returns selling data from DB by its ID
 *
 * @param int $sellingId
 *
 * @return array
 */
function zb_GetSellingData($sellingId) {
    $sellingId = vf($sellingId, 3);
    $query = sprintf("SELECT * from `selling` WHERE `id`='%s'", $sellingId);
    $city_data = simple_query($query);

    return $city_data;
}

/**
 * Creates new selling in database
 *
 * @param string $name
 * @param array  $newSelling
 *
 * @return void
 */
function zb_CreateSellingData($name, $newSelling) {
    foreach ($newSelling as $key => $field) {
        $newSelling[$key] = isset($field) ? mysql_real_escape_string($field) : null;
    }

    $address = '';
    $geo = '';
    $contact = '';
    $comment = '';

    extract($newSelling, EXTR_OVERWRITE);

    $query = sprintf(
            "INSERT INTO `selling` (`id`, `name`, `address`, `geo`, `contact`, `comment`) VALUES (NULL, '%s', '%s', '%s', '%s', '%s'); ", $name, $address, $geo, $contact, $comment
    );
    nr_query($query);
    log_register(sprintf('CREATE Selling `%s` `%s` `%s` `%s` `%s`', $name, $address, $geo, $contact, $comment));
}

/**
 * Returns all available selling full data
 *
 * @return array
 */
function zb_GetAllSellingData() {
    $query = 'SELECT `sel`.`id` AS `id`, `sel`.`name` AS `name`, `sel`.`address` AS `address`, `sel`.`geo` AS `geo`, `sel`.`contact` AS `contact`, `sel`.`comment` AS `comment`, COUNT(`ca`.`id`) AS `count_cards`
        FROM `selling` AS `sel`
        LEFT JOIN `cardbank` AS `ca` ON `ca`.`selling_id` = `sel`.`id` AND `ca`.`active` = 1 AND `ca`.`used` = 0
        GROUP BY `sel`.`id` ORDER by `sel`.`id` ASC ;';
    $all_data = simple_queryall($query);

    return $all_data;
}

/**
 * Changes selling alias by its ID
 *
 * @param int   $sellingId
 * @param array $editSelling
 *
 * @return void
 */
function zb_UpdateSellingData($sellingId, $editSelling) {
    $sellingId = vf($sellingId, 3);
    foreach ($editSelling as $key => $field) {
        $editSelling[$key] = isset($field) ? mysql_real_escape_string($field) : null;
    }

    $name = '';
    $address = '';
    $geo = '';
    $contact = '';
    $comment = '';

    extract($editSelling, EXTR_OVERWRITE);

    $query = sprintf(
            "UPDATE `selling` SET `name` = '%s', `address` = '%s', `geo` = '%s', `contact` = '%s', `comment` = '%s' WHERE `id` = '%u'; ", $name, $address, $geo, $contact, $comment, $sellingId
    );
    nr_query($query);
    log_register(sprintf('UPDATE Selling [%u] `%s` `%s` `%s` `%s` `%s`', $sellingId, $name, $address, $geo, $contact, $comment));
}

/**
 * Deletes selling from database by its ID
 * 
 * @param int $sellingId
 *
 * @return void
 */
function zb_DeleteSellingData($sellingId) {
    $sellingId = vf($sellingId, 3);
    $query = sprintf("DELETE from `selling` WHERE `id` = '%u';", $sellingId);
    nr_query($query);
    log_register(sprintf('DELETE Selling [%u]', $sellingId));
}

/**
 * @return array|string
 */
function zb_SelectAllSellingData() {
    $query = "SELECT * FROM `selling` ORDER BY `name` ASC";
    $allData = simple_queryall($query);
    $allData = !empty($allData) ? $allData : array();

    return $allData;
}

/**
 * @return array|string
 */
function zb_BuilderSelectSellingData() {
    $select = zb_SelectAllSellingData();
    $allData[] = '';

    foreach ($select as $row) {
        $allData[$row['id']] = $row['name'];
    }

    return $allData;
}

/**
 * @param array $params
 *
 * @return array
 */
function zb_SellingReport(array $params) {
    $queryCardId = '';
    if ($params['idfrom'] || $params['idto']) {
        if (empty($params['idfrom'])) {
            $params['idfrom'] = $params['idto'];
        }
        if (empty($params['idto'])) {
            $params['idto'] = $params['idfrom'];
        }
        $idFrom = mysql_real_escape_string($params['idfrom']);
        $idTo = mysql_real_escape_string($params['idto']);
        $queryCardId = sprintf("AND `ca`.`id` BETWEEN %s AND %s", $idFrom, $idTo);
    }
    $queryCardDate = '';
    if ($params['datefrom'] || $params['dateto']) {
        if (empty($params['datefrom'])) {
            $params['datefrom'] = $params['dateto'];
        }
        if (empty($params['dateto'])) {
            $params['dateto'] = $params['datefrom'];
        }
        $dateFrom = mysql_real_escape_string($params['datefrom']);
        $dateTo = mysql_real_escape_string($params['dateto']);
        $queryCardDate = sprintf("AND DATE(`ca`.`receipt_date`) BETWEEN STR_TO_DATE('%s', '%s') AND STR_TO_DATE('%s', '%s')", $dateFrom, '%Y-%m-%d %H:%i:%s', $dateTo, '%Y-%m-%d %H:%i:%s');
    }

    $querySellingIdWhere = '';
    if ($params['selling']) {
        $id = mysql_real_escape_string($params['selling']);
        $querySellingIdWhere = sprintf('WHERE `sel`.`id` = 1', $id);
    }

    $select = ' SELECT `sel`.`id` AS `id`, `sel`.`name` AS `name`,
                SUM(case when `ca`.`active`= 1 then `ca`.`cash` end) as `cash_total`,
                COUNT(case when `ca`.`active`= 1 then `ca`.`id` end) as `count_total`,
                SUM(case when `ca`.`active`= 1 AND `ca`.`used`= 1 then `ca`.`cash` end) as `cash_sel`,
                COUNT(case when `ca`.`active`= 1 AND `ca`.`used`= 1 then `ca`.`id` end) as `count_sel`,
                SUM(case when `ca`.`active`= 1 AND `ca`.`used`= 0 then `ca`.`cash` end) as `cash_balabce`,
                COUNT(case when `ca`.`active`= 1 AND `ca`.`used`= 0 then `ca`.`id` end) as `count_balance`
                FROM `selling` AS `sel`';
    $leftJoin = sprintf('LEFT JOIN `cardbank` AS `ca` ON `ca`.`selling_id` = `sel`.`id` %s %s', $queryCardId, $queryCardDate);
    $query = sprintf('%s %s %s GROUP BY `sel`.`id` ORDER by `sel`.`id` ASC ;', $select, $leftJoin, $querySellingIdWhere);

    $data = simple_queryall($query);

    return $data;
}

?>
