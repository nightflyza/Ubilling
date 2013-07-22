<?php if (cfr('MTEXTCONF')) {
    
        function web_AddOptionsToDatabase($data) {
            if ( !empty($data) ) {
                // Crop, leave digits only:
                $nasid = vf($_GET['nasid'], 3);
                // Serialize and encode options:
                $options = base64_encode(serialize($data));
                // Put serialized and encoded string to database:
                $query = "UPDATE `nas` SET `options` = '" .  $options . "' WHERE `id` = '" . $nasid . "';";
                nr_query($query);
            }
        }
        
        function web_mikrotikExtConf() {
            // Crop, leave digits only:
            $nasid = vf($_GET['nasid'], 3);
            // Determine is the password hidden or not:
            $alter = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
            if ( $alter['PASSWORDSHIDE'] ) {
                $isPasswordHidden = TRUE;
            } else $isPasswordHidden = FALSE; 
            // Get nas data from database:
            $query  = "SELECT * FROM `nas` WHERE `id` = " . $nasid . ";";
            $result = simple_queryall($query);
            if ( !empty($result) ) {
                foreach ($result as $data) {
                    if ( $data['nastype'] == 'mikrotik' ) {
                        // Get options array from database:
                        $options = zb_mikrotikExtConfGetOptions($data['id']);
                        // OPTIONS FORM START <<
                        $form = new InputForm();
                        $form->InputForm('', 'post', __('Save'), NULL, NULL, NULL, 'options_form', NULL);
                        // Authorization data fields:
                        $form->addmessage(__('Authorization Data'));
                        $form->addrow(__('Username'), $form->text_box('options_form[username]', $options['username'], 0, 0, FALSE, NULL));
                        $form->addrow(__('Password'), $form->text_box('options_form[password]', $options['password'], 0, 0, $isPasswordHidden, NULL));
                        if ( !empty($options['username']) ) {
                            $mikrotik = new MikroTik();
                            if ( $mikrotik->connect($data['nasip'], $options['username'], $options['password']) ) {
                                // Get interface list from MikroTik:
                                $interfaces = array();
                                $interfaceList = $mikrotik->command('/interface/getall', array(
                                   ".proplist"=> "name"
                                ));
                                asort($interfaceList);
                                foreach ($interfaceList as $interface) {
                                    $interfaces[$interface['name']] = $interface['name'];
                                }
                                $form->addmessage(__('Interface settings'));
                                // Selector: `users_interface`:
                                if ( !empty($options['users_interface']) ) {
                                    $curUsersInterface = $options['users_interface'];
                                } else $curUsersInterface = NULL;
                                $form->addrow(__('Users Interface'), $form->select_tag('options_form[users_interface]', $interfaces, $curUsersInterface));
                                // Selector: `graph_interface`:
                                if ( !empty($options['graph_interface']) ) {
                                    $curGraphInterface = $options['graph_interface'];
                                } else $curGraphInterface = NULL;
                                $form->addrow(__('Graph Interface'), $form->select_tag('options_form[graph_interface]', $interfaces, $curGraphInterface));
                                // STG scripts behavior configuration:
                                $form->addmessage(__('Setting OnConnect/OnDisconnect scripts behavior for this NAS'));
                                // Selector: `manage_firewall`:
                                if ( !isset($options['manage_firewall']) ) $options['manage_firewall'] = FALSE;
                                $form->addrow(__('Manage FireWall'), $form->checkbox('options_form[manage_firewall]', TRUE, NULL, $options['manage_firewall'], NULL));
                                // Selector: `manage_arp`:
                                if ( !isset($options['manage_arp']) ) $options['manage_arp'] = FALSE;
                                $form->addrow(__('Manage ARP'), $form->checkbox('options_form[manage_arp]', TRUE, NULL, $options['manage_arp'], NULL));
                                // Selector: `manage_queue`:
                                if ( !isset($options['manage_queue']) ) $options['manage_queue'] = FALSE;
                                $form->addrow(__('Manage Queue'), $form->checkbox('options_form[manage_queue]', TRUE, NULL, $options['manage_queue'], NULL));
                                // Selector: `manage_dhcp`:
                                if ( !isset($options['manage_dhcp']) ) $options['manage_dhcp'] = FALSE;
                                $form->addrow(__('Manage DHCP'), $form->checkbox('options_form[manage_dhcp]', TRUE, NULL, $options['manage_dhcp'], NULL));
                                // Selector: `manage_ppp`:
                                if ( !isset($options['manage_ppp']) ) $options['manage_ppp'] = FALSE;
                                $form->addrow(__('Manage PPP'), $form->checkbox('options_form[manage_ppp]', TRUE, NULL, $options['manage_ppp'], ' disabled="disabled"'));
                                // MikroTik status display:
                                $form->addmessage(__('MikroTik General Information'));
                                $mikrotikStatus = $mikrotik->command('/system/resource/print');
                                foreach ( $mikrotikStatus[0] as $key => $value) {
                                    switch ( $key ) {
                                        case 'version':
                                            $form->addrow(__($key), $value);
                                            $version = explode('.', $value);
                                            $form->hidden('options_form[' . $key . ']', $version[0]);
                                            break;
                                        case 'free-memory':
                                        case 'total-memory':
                                        case 'free-hdd-space':
                                        case 'total-hdd-space':
                                            $form->addrow(__($key), stg_convert_size($value));
                                            break;
                                        case 'cpu-frequency':
                                            $form->addrow(__($key), $value . ' MHz');
                                            break;
                                        case 'cpu-load':
                                            $form->addrow(__($key), $value . ' %');
                                            break;
                                        case 'bad-blocks':
                                            if ( $value > 0 ) {
                                                $form->addrow(__($key), '<span style="color:red;">' . $value . ' %</span>');
                                            } else $form->addrow(__($key), '<span>' . $value . ' %</span>');
                                            break;
                                        default:
                                            if ( !empty($value) ) $form->addrow(__($key), $value);
                                            break;
                                    }
                                }
                            }
                        }
                        return $form->show(TRUE);
                        // >> END OPTIONS FORM
                    } else show_window(__('Error'), __('You can add options for MikroTik NAS only!'));
                }
            }
        }
        
        if ( wf_CheckGet(array('nasid')) ) {
            if ( !empty($_POST['options_form']) ) {
                web_AddOptionsToDatabase($_POST['options_form']);
            }
            show_window(__('MikroTik extended configuration'), web_mikrotikExtConf());
        } else show_window(__('Error'), __('No NAS was selected to add options!'));
    } else show_error(__('You cant control this module'));
?>