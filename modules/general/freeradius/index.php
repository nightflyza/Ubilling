<?php if ( cfr('FREERADIUS') ) {
  $alter = $ubillingConfig->getAlter();
  if ( $alter['FREERADIUS_ENABLED'] ) {
    // Содержимое страницы
    $title = ''; $html = '';
    // Доступные сценарии
    $scenarios = array(
      'check' => 'check',
      'reply' => 'reply'
    );
    // Доступные операторы
    $operators = array(
      '='  => '=', ':='  => ':=', '==' => '==', '+=' => '+=',
      '!=' => '!=', '>'  => '>',  '>=' => '>=', '<'  => '<',
      '<=' => '<=', '=~' => '=~', '!~' => '!~', '=*' => '=*',
      '!*' => '!*'
    );
    
    /**
     * Возвращает массив, в котором ключи - это IP-адреса серверов, а
     * значения - "IP-адрес NAS - Имя NAS"
     * @return  array
     */
    function getNasIPName() {
      $return = array('');
      $nasses = zb_NasGetAllData();
      foreach ( $nasses as $nas )
        $return[$nas['nasip']] = $nas['nasip'] . ' - ' . $nas['nasname'];
      return $return;
    }
    
    /**
     * Возвращает массив, в котором ключи - это ID сети, а
     * значения - "ID сети - Название сервиса"
     * @return  array
     */
    function getServiceIdDesc() {
      $return = array('');
      $services = multinet_get_services();
      foreach ( $services as $service )
        $return[$service['id']] = $service['id'] . ' - ' . $service['desc'];
      return $return;
    }
    
    if ( wf_CheckGet(array('username')) ) {
      /* Редактирование атрибутов для конкретного пользователя */
      $title = __('RADIUS-attributes for user');
      $login = vf($_GET['username'], 4);
     
      // Сабмит формы добавления атрибута
      if ( wf_CheckPost(array('add'))  ) {
        // Экранируем все введённые данные
        foreach ( $_POST['add'] as &$value)
          $value = mysql_real_escape_string($value);
        extract($_POST['add'], EXTR_SKIP);
        $query = "INSERT INTO `radius_attributes` (`scenario`, `login`, `Attribute`, `op`, `Value`) VALUES ('$scenario', '$login', '$Attribute', '$op', '$Value')";
        if ( nr_query($query) ) 
          rcms_redirect("?module=freeradius&username=$login");
      }
      
      // Удаление атрибута
      if ( wf_CheckGet(array('delete')) ) {
        $id = vf($_GET['delete'], 3);
        $query = "DELETE FROM `radius_attributes` WHERE `id` = '$id'";
        if ( nr_query($query) ) 
          rcms_redirect("?module=freeradius&username=$login");
      }
      
      // Редактирование атрибута
      if ( wf_CheckGet(array('edit')) ) {
        // ID редактируемого атрибута
        $id = vf($_GET['edit'], 3);
        // Сабмит формы редактирования атрибута
        if ( wf_CheckPost(array('edit'))  ) {
          // Экранируем все введённые данные
          foreach ( $_POST['edit'] as &$value)
            $value = mysql_real_escape_string($value);
          extract($_POST['edit'], EXTR_SKIP);
          $query = "UPDATE `radius_attributes` SET `scenario` = '$scenario', `Attribute` = '$Attribute', `op` = '$op', `Value` = '$Value' WHERE `id` = '$id'";
          if ( nr_query($query) ) 
            rcms_redirect("?module=freeradius&username=$login");
        }
        // Получаем уже существующие данные об атрибуте
        $query  = "SELECT * FROM `radius_attributes` WHERE `id` = '$id'";
        $result = simple_query($query);
        // Форма редактирования
        $form = new InputForm('', 'POST', __('Save'), '', '', '', 'edit');
        // Сценарий
        $content = $form->radio_button('edit[scenario]', $scenarios, $result['scenario']);
        $form->addrow(__('Scenario'), $content);
        // Логин пользователя (disabled)
        $content = $form->text_box('edit[login]', $result['login'], 0, 0, false, 'disabled');
        $form->addrow(__('Login'), $content);
        // Атрибут
        $content = $form->text_box('edit[Attribute]', $result['Attribute']);
        $form->addrow(__('Attribute'), $content);
        // Оператор
        $content = $form->select_tag('edit[op]', $operators, $result['op']);
        $form->addrow(__('op'), $content);
        // Значение
        $content = $form->text_box('edit[Value]', $result['Value']);
        $form->addrow(__('Value'), $content);
        // Добавляем в код страницы открытое модальное окно
        $html .= wf_modalOpened(__('Editing of RADIUS-attribute'), $form->show(1), 450, 275);
      }
      
      $query = "
SELECT `radius_attributes`.`id`, `radius_attributes`.`login`, `radius_attributes`.`scenario`, `radius_attributes`.`Attribute`, `radius_attributes`.`Value` AS `Macros`, `radius_attributes`.`op`,
CASE 
	WHEN `radius_attributes`.`Value` LIKE '%{user[login]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[login]}',    `users`.`login`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Password]}%' THEN REPLACE(`radius_attributes`.`Value`, '{user[Password]}', `users`.`Password`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Tariff]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{user[Tariff]}',   `users`.`Tariff`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{nethost[ip]}',    `nethosts`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[mac]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{nethost[mac]}',   `nethosts`.`mac`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[id]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[id]}',    `networks`.`id`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[ip]}',    SUBSTRING_INDEX(`networks`.`desc`, '/',  1))
	WHEN `radius_attributes`.`Value` LIKE '%{network[start]}%' THEN REPLACE(`radius_attributes`.`Value`, '{network[start]}', `networks`.`startip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[end]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{network[end]}',   `networks`.`endip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[desc]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[desc]}',  `networks`.`desc`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[cidr]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[cidr]}',  SUBSTRING_INDEX(`networks`.`desc`, '/', -1))
	WHEN `radius_attributes`.`Value` LIKE '%{switch[ip]}%'     THEN REPLACE(`radius_attributes`.`Value`, '{switch[ip]}',     `switches`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{switch[port]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{switch[port]}',   `switchportassign`.`port`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[up]}%'      THEN REPLACE(`radius_attributes`.`Value`, '{speed[up]}',      `speeds`.`speedup`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[down]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{speed[down]}',    `speeds`.`speeddown`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[state]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[state]}',   (
    CASE
      WHEN `users`.`Down`     THEN 'DOWN'
      WHEN `users`.`Passive`  THEN 'PASSIVE'
      WHEN `users`.`Cash` < -`users`.`Credit`
                              THEN 'OFF-LINE'
      ELSE 'ON-LINE'
    END
  ))
  ELSE `radius_attributes`.`Value`
END as `Value`
 FROM `users`
      JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
      JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
      JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
      JOIN `radius_attributes` ON  `radius_attributes`.`login` = `users`.`login`
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`netid` = `networks`.`id` )
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`))
 LEFT JOIN `switchportassign` ON `switchportassign`.`login` = `users`.`login`
 LEFT JOIN `switches` ON `switches`.`id` = `switchportassign`.`switchid`
 LEFT JOIN `speeds`   ON `speeds`.`tariff` = `users`.`Tariff`
WHERE `users`.`login` = '$login'
    ";
      $results = simple_queryall($query);
      $cells  = wf_TableCell(__('ID'));
      $cells .= wf_TableCell(__('Scenario'));
      $cells .= wf_TableCell(__('Attribute'));
      $cells .= wf_TableCell(__('op'));
      $cells .= wf_TableCell(__('Value'));
      $cells .= wf_TableCell(__('Inherited'));
      $cells .= wf_TableCell(__('Actions'));
      $rows   = wf_TableRow($cells, 'row1');
      if ( !empty($results) ) {
        foreach ( $results as $result ) {
          $cells  = wf_TableCell($result['id']);
          $cells .= wf_TableCell($result['scenario']);
          $cells .= wf_TableCell($result['Attribute']);
          $cells .= wf_TableCell($result['op']);
          $content = '<abbr title="' . __('Macros') . ': ' . $result['Macros'] . '" style="cursor: help">' . $result['Value'] . '</abbr>';
          $cells .= wf_TableCell($content);
          $content  = web_bool_led($result['login'] == '*');
          $cells .= wf_TableCell($content);
          $content = '';
          if ( $result['login'] == $login ) {
            $content  = wf_Link("?module=freeradius&username=$login&edit=" . $result['id'], web_edit_icon());
            $content .= wf_JSAlert("?module=freeradius&username=$login&delete=" . $result['id'], web_delete_icon(), 'Are you serious');
          }
          $cells .= wf_TableCell($content);
          $rows .= wf_TableRow($cells, 'row3');
        }
      }
      // Форма добавления атрибута
      $form = new InputForm('', 'POST', __('Save'), '', '', '', 'add');
      // Сценарий
      $content = $form->radio_button('add[scenario]', $scenarios, 'check');
      $form->addrow(__('Scenario'), $content);
      // Логин (disabled)
      $content = $form->text_box('add[login]', $login, 0, 0, false, 'disabled');
      $form->addrow(__('Login'), $content);
      // Атрибут 
      $content = $form->text_box('add[Attribute]', '');
      $form->addrow(__('Attribute'), $content);
      // Оператор
      $content = $form->select_tag('add[op]', $operators, '');
      $form->addrow(__('op'), $content);
      // Значение
      $content = $form->text_box('add[Value]', '');
      $form->addrow(__('Value'), $content);
      // Таблица со списком атрибутов для пользователя
      $html .= wf_BackLink("?module=userprofile&username=$login");
      $html .= wf_modal(__('Append'), __('Adding of RADIUS-attribute'), $form->show(1), 'ubButton', 450, 275);
      $html .= wf_TableBody($rows, '100%', '0', 'sortable');
    } elseif ( wf_CheckGet(array('netid')) ) {
      /* Редактирование атрибутов для конкретной сети */
      $title = __('RADIUS-attributes for network');
      $netid = vf($_GET['netid']);
      
      // Сабмит формы добавления атрибута
      if ( wf_CheckPost(array('add'))  ) {
        // Экранируем все введённые данные
        foreach ( $_POST['add'] as &$value)
          $value = mysql_real_escape_string($value);
        extract($_POST['add']);
        $login = isset($login) ? "'$login'" : 'NULL';
        $query = "INSERT INTO `radius_attributes` (`scenario`, `login`, `netid`, `Attribute`, `op`, `Value`) VALUES ('$scenario', $login, '$netid', '$Attribute', '$op', '$Value')";
        if ( nr_query($query) ) 
          rcms_redirect("?module=freeradius&netid=$netid");
      }
      
      // Удаление атрибута
      if ( wf_CheckGet(array('delete')) ) {
        $id = vf($_GET['delete'], 3);
        $query = "DELETE FROM `radius_attributes` WHERE `id` = '$id'";
        if ( nr_query($query) ) 
          rcms_redirect("?module=freeradius&netid=$netid");
      }
      
      // Редактирование атрибута
      if ( wf_CheckGet(array('edit')) ) {
        // ID редактируемого атрибута
        $id = vf($_GET['edit'], 3);
        // Сабмит формы редактирования атрибута
        if ( wf_CheckPost(array('edit'))  ) {
          // Экранируем все введённые данные
          foreach ( $_POST['edit'] as &$value)
            $value = mysql_real_escape_string($value);
          extract($_POST['edit']);
          $login = isset($login) ? "'$login'" : 'NULL';
          $query = "UPDATE `radius_attributes` SET `scenario` = '$scenario', `login` = $login, `Attribute` = '$Attribute', `op` = '$op', `Value` = '$Value' WHERE `id` = '$id'";
          if ( nr_query($query) ) 
            rcms_redirect("?module=freeradius&netid=$netid");
        }
        $query  = "SELECT * FROM `radius_attributes` WHERE `id` = '$id'";
        $result = simple_query($query);
        // Форма редактирования
        $form = new InputForm('', 'POST', __('Save'), '', '', '', 'edit');
        // Сценарий
        $content = $form->radio_button('edit[scenario]', $scenarios, $result['scenario']);
        $form->addrow(__('Scenario'), $content);
        // Сервис (disabled)
        $content  = $form->select_tag('edit[netid]', getServiceIdDesc(), $netid, 'disabled');
        $content .= $form->checkbox('edit[login]', '*', __('Foreach'), $result['login']);
        $form->addrow(__('Service'), $content);
        // Атрибут
        $content = $form->text_box('edit[Attribute]', $result['Attribute']);
        $form->addrow(__('Attribute'), $content);
        // Оператор
        $content = $form->select_tag('edit[op]', $operators, $result['op']);
        $form->addrow(__('op'), $content);
        // Значение
        $content = $form->text_box('edit[Value]', $result['Value']);
        $form->addrow(__('Value'), $content);
        // Добавляем в код страницы открытое модальное окно
        $html .= wf_modalOpened(__('Editing of RADIUS-attribute'), $form->show(1), 450, 275);
      }
      
      if ( wf_checkPost(array('reassignment')) ) {
        // Экранируем все введённые данные
        foreach ( $_POST['reassignment'] as &$value)
          $value = mysql_real_escape_string($value);
        extract($_POST['reassignment']);
        // Добавляем информацию о переназначении
        $query = "INSERT INTO `radius_reassigns` (`netid`, `value`) VALUES ($netid, '$value') ON DUPLICATE KEY UPDATE `value` = '$value'";
        if ( nr_query($query) )
          rcms_redirect("?module=freeradius&netid=$netid");
      }
      
      $query = "SELECT `id`, `login`, `scenario`, `Attribute`, `op`, `Value` FROM `radius_attributes` WHERE `netid` = '$netid'";
      $results = simple_queryall($query);
      $cells  = wf_TableCell(__('ID'));
      $cells .= wf_TableCell(__('Scenario'));
      $cells .= wf_TableCell(__('Attribute'));
      $cells .= wf_TableCell(__('op'));
      $cells .= wf_TableCell(__('Value'));
      $cells .= wf_TableCell(__('Foreach'));
      $cells .= wf_TableCell(__('Actions'));
      $rows   = wf_TableRow($cells, 'row1');
      if ( !empty($results) ) {
        foreach ( $results as $result ) {
          $cells  = wf_TableCell($result['id']);
          $cells .= wf_TableCell($result['scenario']);
          $cells .= wf_TableCell($result['Attribute']);
          $cells .= wf_TableCell($result['op']);
          $cells .= wf_TableCell($result['Value']);
          $content  = web_bool_led($result['login'] == '*');
          $cells .= wf_TableCell($content);
          $content  = wf_Link("?module=freeradius&netid=$netid&edit=" . $result['id'], web_edit_icon());
          $content .= wf_JSAlert("?module=freeradius&netid=$netid&delete=" . $result['id'], web_delete_icon(), 'Are you serious');
          $cells .= wf_TableCell($content);
          $rows .= wf_TableRow($cells, 'row3');
        }
      }
      /* Кнопка "Назад" */
      $html .= wf_BackLink("?module=multinet");
      // Форма добавления нового атрибута
      $form = new InputForm('', 'POST', __('Save'), '', '', '', 'add');
      //  - Сценарий
      $content = $form->radio_button('add[scenario]', $scenarios, 'check');
      $form->addrow(__('Scenario'), $content);
      //  - Сервис (disabled)
      $content  = $form->select_tag('add[netid]', getServiceIdDesc(), $netid, 'disabled');
      $content .= $form->checkbox('add[login]', '*', __('Foreach'), '');
      $form->addrow(__('Service'), $content);
      //  - Атрибут 
      $content = $form->text_box('add[Attribute]', '');
      $form->addrow(__('Attribute'), $content);
      //  - Оператор
      $content = $form->select_tag('add[op]', $operators, '');
      $form->addrow(__('op'), $content);
      //  - Значение
      $content = $form->text_box('add[Value]', '');
      $form->addrow(__('Value'), $content);
      /* Кнопка модального окна с формой добавления нового атрибута */
      $html .= wf_modal(__('Append'), __('Adding of RADIUS-attribute'), $form->show(1), 'ubButton', 450, 275);
      // Форма переопределения атрибута 'User-Name'
      $query  = "SELECT `value` FROM `radius_reassigns` WHERE `netid` = '$netid'";
      $result = simple_query($query);
      $result['value'] = !empty($result['value']) ? $result['value'] : '';
      $form = new InputForm('', 'POST', __('Save'), '', '', '', 'reassignment');
      //  - Значение 
      $content = $form->radio_button('reassignment[value]', array(
        ''    => __('Login'),
        'ip'  => __('IP'),
        'mac' => __('MAC')
      ), $result['value']);
      $form->addrow(__('Value'), $content);
      /* Кнопка модального окна с формой переназначения атрибута 'User-Name' */
      $html .= wf_modal(__('Reassign User-Name'), __('Reassignment of User-Name'), $form->show(1), 'ubButton', 450, 155);
      /* Атрибуты сети */
      $html .= wf_TableBody($rows, '100%', '0', 'sortable');
    }
    /* Показываем содержимое модуля */
    show_window($title, $html);
  } else show_window(__('Error'), __('This module is disabled'));
} else show_error(__('You cant control this module'));
