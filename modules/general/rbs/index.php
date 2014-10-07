<?php if ( cfr('RBS') ) {
  // Объект:
  $object = new RosKomNadzor();
  // Контроллер:
  if ( wf_CheckGet(array('action')) ) {
    $action = vf($_GET['action']);
    switch ( $action ) {
      case 'truncateLog':
        zb_StorageDelete($object::STORAGE_LASTSYNC_KEY);
        nr_query("TRUNCATE `rbs_requests`");
        nr_query("TRUNCATE `rbs_results`");
        rcms_redirect('?module=rbs', true);
        break;
    }
  }
  
  /**
   * Форма добавления информации об операторе
   */
  if ( wf_CheckPost(array($object::FORM_NAME)) ) {
    // При сабмите формы - сохраняем данные в `ubstorage`:
    $data = serialize($_POST[$object::FORM_NAME]);
    $data = base64_encode($data);
    zb_StorageSet($object::STORAGE_OPERATOR_KEY, $data);
    // Сохраняем pem-сертификат:
    switch ( $_FILES[$object::FORM_NAME]['error']['pem'] ) {
      case UPLOAD_ERR_OK:
        if ( !move_uploaded_file($_FILES[$object::FORM_NAME]['tmp_name']['pem'], $object::FILE_OPENSSL_PEM) )
          show_window(__('Error'), __('Error while moving file from tmp-directory'));
        break;
      case UPLOAD_ERR_NO_FILE:
        // Nothing to do...
        break;
      default:
        show_window(__('Error'), __('Error while loading file to server!'));
        break;
    }
  }
  // Извлекаем уже сохраннённые данные
  $data = zb_StorageGet($object::STORAGE_OPERATOR_KEY);
  $data = base64_decode($data);
  $data = unserialize($data);
  // Генерируем саму форму:
  $form = new InputForm(null, 'POST', __('Save'), null, null, 'multipart/form-data', $object::FORM_NAME, null);
  /* Раздел информации об операторе связи */
  $form->addmessage(__('Necessary information about operator'));
  // Полное название:
  $label  = __('operatorName');
  $label .= wf_tag('span', false, '', 'style="float: right; margin-top: -1px"');
  $label .= web_bool_led( !empty($data['operatorName']) );
  $label .= wf_tag('span', true);
  $contents  = $form->text_box($object::FORM_NAME . '[operatorName]', @$data['operatorName'], 60, 0, false,  '');
  $form->addrow($label, $contents);
  // ИНН:
  $label  = __('inn');
  $label .= wf_tag('span', false, '', 'style="float: right; margin-top: -1px"');
  $label .= web_bool_led( !empty($data['inn']) );
  $label .= wf_tag('span', true);
  $contents  = $form->text_box($object::FORM_NAME . '[inn]',   @$data['inn'],   30, 12, false,  '');
  $form->addrow($label, $contents);
  // ОГРН:
  $label  = __('ogrn');
  $label .= wf_tag('span', false, '', 'style="float: right; margin-top: -1px"');
  $label .= web_bool_led( !empty($data['ogrn']) );
  $label .= wf_tag('span', true);
  $contents  = $form->text_box($object::FORM_NAME . '[ogrn]',  @$data['ogrn'],  30, 15, false,  '');
  $form->addrow($label, $contents);
  // Электронный адрес:
  $label  = __('email');
  $label .= wf_tag('span', false, '', 'style="float: right; margin-top: -1px"');
  $label .= web_bool_led( !empty($data['email']) );
  $label .= wf_tag('span', true);
  $contents  = $form->text_box($object::FORM_NAME . '[email]', @$data['email'], 30,  0, false, '');
  $form->addrow($label, $contents);
  /* Раздел добавления сертификата */
  $form->addmessage(__('Adding of new *.pem certificate'));
  // Сертификат:
  $label  = __('*.pem certificate');
  $label .= wf_tag('span', false, '', 'style="float: right; margin-top: -1px"');
  $label .= web_bool_led( file_exists($object::FILE_OPENSSL_PEM) );
  $label .= wf_tag('span', true);
  $contents = $form->file($object::FORM_NAME . '[pem]');
  $form->addrow($label, $contents, 'middle', 'left');
  // Отображаем форму
  show_window(__('Registry of banned sites'), $form->show(true));
  
  /**
   * Лог получения выгрузки из реестра Роскомнадзора
   */
  $controls  = wf_Link('?module=rbs', 'Обновить', 0, 'ubButton');
  $controls .= wf_Link('?module=rbs&action=truncateLog', 'Очистить', 0, 'ubButton');
  $controls .= wf_tag('span', false, '', 'style="float: right"');
  $controls .= wf_Link('?module=rbs&limit=25', '25', 0, 'ubButton');
  $controls .= wf_Link('?module=rbs&limit=50', '50', 0, 'ubButton');
  $controls .= wf_Link('?module=rbs&limit=100', '100', 0, 'ubButton');
  $controls .= wf_Link('?module=rbs&limit=250', '250', 0, 'ubButton');
  $controls .= wf_Link('?module=rbs&limit=500', '500', 0, 'ubButton');
  $controls .= wf_tag('span', true);
  $limit = isset($_GET['limit']) ? vf($_GET['limit'], 3) : 25;
  // Заголовок таблицы
  $cells  = wf_TableCell(__('ID'));
  $cells .= wf_TableCell(__('code'));
  $cells .= wf_TableCell(__('requestStatus'), '', '', 'colspan="2"');
  $cells .= wf_TableCell(__('requestTime'), 125);
  $cells .= wf_TableCell(__('resultStatus'), '', '', 'colspan="2"');
  $cells .= wf_TableCell(__('resultTime'), 125);
  $rows   = wf_TableRow($cells, 'row1');
  $query = "
    SELECT
      `rbs_requests`.`id`               AS `id`,
      `rbs_requests`.`code`             AS `code`,
      `rbs_requests`.`requestStatus`    AS `requestStatus`,
      `rbs_requests`.`requestComment`   AS `requestComment`,
      `rbs_requests`.`requestTime`      AS `requestTime`,
      `rbs_requests`.`isUrgent`         AS `isUrgent`,
      
      `rbs_results`.`resultStatus`      AS `resultStatus`,
      `rbs_results`.`resultStatusCode`  AS `resultStatusCode`,
      `rbs_results`.`resultComment`     AS `resultComment`,
      `rbs_results`.`resultTime`        AS `resultTime` 
    FROM `rbs_requests`
    LEFT JOIN `rbs_results` ON `rbs_results`.`requestID` = `rbs_requests`.`id`
    ORDER BY `rbs_requests`.`id` DESC
    LIMIT $limit
  ";
  $results = simple_queryall($query);
  if ( !empty($results) ) {
    foreach ( $results as $result ) {
      $cells  = wf_TableCell($result['id']);
      $cells .= wf_TableCell($result['code'], 250);
      
      $cells .= wf_TableCell(web_bool_led($result['requestStatus']), 16);
      $cells .= wf_TableCell('<i>' . $result['requestComment'] . '</i>');
      $cells .= wf_TableCell($result['requestTime'], 125);
      
      $cells .= wf_TableCell(web_bool_led($result['resultStatus']), 16);
      $cells .= wf_TableCell('<i>' . $result['resultComment'] . '</i>');
      $cells .= wf_TableCell($result['resultTime'], 125);
      $rows  .= wf_TableRow($cells, $result['isUrgent'] ? 'row2' : 'row3');
    }
  }
  // Отображаем лог последних выргузок:
  show_window(__("Лог последних $limit выгрузок из реестра"), $controls . wf_TableBody($rows, '100%', '0', 'sortable'));
} else show_error(__('Access denied'));