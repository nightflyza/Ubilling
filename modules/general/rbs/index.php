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
  // Форма добавления информации об операторие и сертификатов:
  if ( wf_CheckPost(array($object::FORM_OPERATOR)) )
    $object->submitOperatorForm($_POST[$object::FORM_OPERATOR]);
  show_window(__('Registry of banned sites'), $object->showOperatorForm());
  
  // Лог получения выгрузки из реестра Роскомнадзора:
  $actions  = wf_Link('?module=rbs', 'Обновить', 0, 'ubButton');
  $actions .= wf_Link('?module=rbs&action=truncateLog', 'Очистить', 0, 'ubButton');
  show_window(__('Лог последних 25 выгрузок из реестра'), $actions . $object->showLog());
} else show_error(__('Access denied'));