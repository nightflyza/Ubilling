<?php
    if ( cfr('FREERADIUS') ) {
        $alter = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        if ( $alter['FREERADIUS_ENABLED'] ) {
            if ( wf_CheckGet(array('netid')) ) {
                
                // Define data array:
                $data = array(
                    'id'    => NULL,
                    'netid' => vf($_GET['netid'], 3),
                    'attr'  => NULL,
                    'op'    => NULL,
                    'value' => NULL
                );
                
                // Check the network presence in database:
                $query = "SELECT * FROM `networks` WHERE `id` = " . $data['netid'];
                $result = simple_query($query);
                
                // If network is present:
                if ( !empty($result) ) {
                    if ( wf_CheckPost(array('attr_add_form')) ) {
                        foreach ($_POST['attr_add_form'] as $key => $value) {
                            $data[$key] = $value;
                        }
                        if ( !empty($data['attr']) && !empty($data['op']) && !empty($data['value']) ) {
                            $query = "INSERT INTO `radius_custom_attributes` (`netid`, `Attribute`, `op`, `Value`) VALUES ('" . $data['netid'] . "', '" . $data['attr'] . "', '" . $data['op'] . "', '" . $data['value'] . "')";
                            nr_query($query);
                        } else show_window(__('Error'), __('No all of required fields is filled'));
                    } elseif ( wf_CheckGet(array('delete')) ) {
                        $data['id'] = vf($_GET['delete'], 3);
                        $query = "DELETE FROM `radius_custom_attributes` WHERE `id` = " . $data['id'];
                        nr_query($query);
                        rcms_redirect('?module=freeradius&netid=' . $data['netid']);
                    }

                    // Attribute's list table's header:
                    $columns  = wf_TableCell(__('Attribute'));
                    $columns .= wf_TableCell(__('op'));
                    $columns .= wf_TableCell(__('Value'));
                    $columns .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($columns, 'row1');
                    
                    // Get attribute's list for network:
                    $query = "SELECT * FROM `radius_custom_attributes` WHERE `netid` = " . $data['netid'];
                    $result = simple_queryall($query);

                    if ( !empty($result) ) {
                        foreach ($result as $value) {
                            $columns  = wf_TableCell($value['Attribute']);
                            $columns .= wf_TableCell($value['op']);
                            $columns .= wf_TableCell($value['Value']);
                            $columns .= wf_TableCell(wf_JSAlert('?module=freeradius&netid=' . $data['netid'] . '&delete=' . $value['id'], web_delete_icon(), 'Are you serious'));
                            $rows .= wf_TableRow($columns, 'row3');
                        }
                    } else {
                        $columns = wf_TableCell(__('There are no defined attributes for network'), NULL, NULL, 'colspan=5');
                        $rows .= wf_TableRow($columns, NULL);
                    }

                    // Show attribute's table:
                    $return = wf_TableBody($rows, '100%', '0', 'sortable');
                    show_window(__('Attributes, defined for network'), $return);

                    // Attribute add form:
                    $form = new InputForm();
                    $form->InputForm('?module=freeradius&netid=' . $data['netid'], 'post', __('Save'), NULL, NULL, NULL, 'attr_add_form', NULL);
                    $form->addrow(__('Attribute'), $form->text_box('attr_add_form[attr]', NULL, 0, 32, FALSE, NULL));
                    $form->addrow(__('op'), $form->text_box('attr_add_form[op]', NULL, 0, 2, FALSE, NULL));
                    $form->addrow(__('Value'), $form->text_box('attr_add_form[value]', NULL, 0, 253, FALSE, NULL));
                    show_window(__('New attribute add'), $form->show(TRUE));
                // If network is absent - show error
                } else show_window(__('Error'), __('Selected network is absent in database!'));
                
            } elseif (  wf_CheckGet(array('username')) ) {
                
                // Define data array:
                $data = array(
                    'id'    => NULL,
                    'login' => vf($_GET['username'], 5),
                    'attr'  => NULL,
                    'op'    => NULL,
                    'value' => NULL
                );
                
                $query = "SELECT * FROM `users` WHERE `login` = '" . $data['login'] . "'";
                $result = simple_query($query);

                if ( !empty($result) ) {
                    if ( wf_CheckPost(array('attr_add_form')) ) {
                        foreach ($_POST['attr_add_form'] as $key => $value) {
                            $data[$key] = $value;
                        }
                        if ( !empty($data['attr']) && !empty($data['op']) && !empty($data['value']) ) {
                            $query = "INSERT INTO `radius_custom_attributes` (`login`, `Attribute`, `op`, `Value`) VALUES ('" . $data['login'] . "', '" . $data['attr'] . "', '" . $data['op'] . "', '" . $data['value'] . "')";
                            nr_query($query);
                        } else show_window(__('Error'), __('No all of required fields is filled'));
                    } elseif ( wf_CheckGet(array('delete')) ) {
                        $data['id'] = vf($_GET['delete'], 3);
                        $query = "DELETE FROM `radius_custom_attributes` WHERE `id` = " . $data['id'];
                        nr_query($query);
                        rcms_redirect('?module=freeradius&username=' . $data['login']);
                    }

                    // Attribute's list table's header:
                    $columns  = wf_TableCell(__('Attribute'));
                    $columns .= wf_TableCell(__('op'));
                    $columns .= wf_TableCell(__('Value'));
                    $columns .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($columns, 'row1');

                    $query = "SELECT * FROM `radius_custom_attributes` WHERE `login` = '" . $data['login'] . "'";
                    $result = simple_queryall($query);

                    if ( !empty($result) ) {
                        foreach ($result as $value) {
                            $columns  = wf_TableCell($value['Attribute']);
                            $columns .= wf_TableCell($value['op']);
                            $columns .= wf_TableCell($value['Value']);
                            $columns .= wf_TableCell(wf_JSAlert('?module=freeradius&username=' . $data['login'] . '&delete=' . $value['id'], web_delete_icon(), 'Are you serious'));
                            $rows .= wf_TableRow($columns, 'row3');
                        }
                    } else {
                        $columns = wf_TableCell(__('There are no defined attributes for user'), NULL, NULL, 'colspan=5');
                        $rows .= wf_TableRow($columns, NULL);
                    }

                    $return = wf_TableBody($rows, '100%', '0', 'sortable');
                    show_window(__('Attributes, defined for user'), $return);

                    // Attribute add form:
                    $form = new InputForm();
                    $form->InputForm('?module=freeradius&username=' . $data['login'], 'post', __('Save'), NULL, NULL, NULL, 'attr_add_form', NULL);
                    $form->addrow(__('Attribute'), $form->text_box('attr_add_form[attr]', NULL, 0, 32, FALSE, NULL));
                    $form->addrow(__('op'), $form->text_box('attr_add_form[op]', NULL, 0, 2, FALSE, NULL));
                    $form->addrow(__('Value'), $form->text_box('attr_add_form[value]', NULL, 0, 253, FALSE, NULL));
                    show_window(__('New attribute add'), $form->show(TRUE));

                } else show_window(__('Error'), __('Selected user is absent in database!'));
            } else show_window(__('Error'), __('Module startup error'));
        } else show_window(__('Error'), __('This module is disabled'));
    } else show_error(__('You cant control this module'));
?>