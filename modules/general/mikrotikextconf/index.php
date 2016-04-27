<?php
    if ( cfr('MTEXTCONF') ) {
        class MTExtConf {
            
            // Private variables:
            private $_id = null;
            private $_ip = null;
            private $_if = array();
            private $api = null;
            private $form = null;
            private $options = array();
            private $config = array();
            
            // Constants of class:
            const FORM_NAME = 'opts';
            
            public function __construct() {
                /* Filter NAS'es id: */
                $this->_id = filter_input(INPUT_GET, 'nasid', FILTER_SANITIZE_NUMBER_INT);
                $this->_ip = zb_NasGetIpById($this->_id);
                
                /* Load APIs: */
                $this->api = new RouterOS();
                $this->form = new InputForm();
                
                /* Get NAS current options: */
                $this->options = zb_NasOptionsGet($this->_id);
                
                /* Get configurtion: */
                $alter = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
                $this->config['PASSWORDSHIDE'] = ( !empty($alter['PASSWORDSHIDE']) ) ? true : false;
                unset($alter);
            }
            
            public function save() {
                $value = serialize($_POST[self::FORM_NAME]);
                $value = base64_encode($value);
                simple_update_field('nas', 'options', $value, "WHERE `id` = '" . $this->_id . "'");
                // Re-new options current values:
                $this->options = zb_NasOptionsGet($this->_id);
                // Return
                return true;
            }
            
            private function get_ifaces() {
                if ( $this->api->connected ) {
                    $result = $this->api->command('/interface/getall', array('.proplist' => 'name'));
                    foreach ($result as $value) {
                        $name = $value['name'];
                        $this->_if[$name] = $name;
                    }
                }
                return natsort($this->_if);
            }
            
            public function render() {
                $this->form->InputForm(null, 'POST', __('Save'), null, null, null, self::FORM_NAME, null);
                // Block 1: Authorization Data
                $this->form->addmessage(__('Authorization Data'));
                $inputs = array('username', 'password');
                foreach ( $inputs as $input ) {
                    $_hide = ( $input == 'password' ) ? $this->config['PASSWORDSHIDE'] : false;
                    $contents = $this->form->text_box(self::FORM_NAME . '[' . $input . ']', $this->options[$input], 0, 0, $_hide, null);
                    $this->form->addrow(__($input), $contents);
                }
                unset($inputs);
                // Connection-sensetive options:
                if ( $this->api->connect($this->_ip, $this->options['username'], $this->options['password']) ) {
                    // Block 2: Interface settings
                    $this->form->addmessage(__('Interface settings'));
                    $this->get_ifaces();
                    $selects = array('users', 'graph');
                    foreach ( $selects as $select ) {
                        $opt = $select . '_interface';
                        $name = self::FORM_NAME . '[' . $opt . ']';
                        $current = ( isset($this->options[$opt]) ) ? $this->options[$opt] : null;
                        $contents = $this->form->select_tag($name, $this->_if, $current);
                        $this->form->addrow(__(ucwords($select) . ' Interface'), $contents);
                    }
                    unset($selects);
                    // Block 3: Setting On* scripts behavior for this NAS
                    $this->form->addmessage(__('Setting On* scripts behavior for this NAS'));
                    $checkboxes = array('firewall', 'arp', 'queue', 'queue_tree', 'dhcp', 'ppp');
                    foreach ( $checkboxes as $checkbox ) {
                        $opt = 'manage_' . $checkbox;
                        $name = self::FORM_NAME . '[' . $opt . ']';
                        $current = ( isset($this->options[$opt]) ) ? true : false;
                        $contents = $this->form->checkbox($name, true, null, $current, null);
                        $this->form->addrow(__('Manage ' . $checkbox), $contents);
                    }
                    unset($checkboxes);
                    // Block 4: MikroTik General Information
                    $this->form->addmessage(__('MikroTik General Information'));
                    $status = $this->api->command('/system/resource/print');
                        foreach ($status[0] as $key => $value) {
                            switch ( $key ) {
                                case 'uptime':
                                    $parse = array(
                                        'w' => '&nbsp;' . __('w') . '&nbsp;',
                                        'd' => '&nbsp;' . __('d') . '&nbsp;'
                                    );
                                    $search = array_keys($parse);
                                    $replace = array_values($parse);
                                    $value = str_replace($search, $replace, $value);
                                    $this->form->addrow(__($key), $value);
                                    break;
                                case 'version':
                                    $this->form->addrow(__($key), $value);
                                    list($value) = explode('.', $value);
                                    $this->form->hidden(self::FORM_NAME . '[' . $key . ']', $value);
                                    break;
                                case 'free-memory':
                                case 'total-memory':
                                case 'free-hdd-space':
                                case 'total-hdd-space':
                                    $value = stg_convert_size($value);
                                    $this->form->addrow(__($key), $value);
                                    break;
                                case 'cpu-frequency':
                                    $this->form->addrow(__($key), $value . ' MHz');
                                    break;
                                case 'cpu-load':
                                    $this->form->addrow(__($key), $value . ' %');
                                    break;
                                case 'bad-blocks':
                                    $style = ( $value > 0 ) ? 'color:red;' : null;
                                    $this->form->addrow(__($key), '<span style="' . $style . '">' . $value . ' %</span>');
                                    break;
                                default:
                                    if ( !empty($value) ) {
                                        $this->form->addrow(__($key), $value);
                                    }
                                    break;
                            }
                        }
                }
                /* Uncomment for debug window show:
                 * if ( !is_null($this->api->debug_str) ) deb($this->api->debug_str);
                 */
                return $this->form->show(true);
            }
        }
        if ( isset($_GET['nasid']) ) {
            $obj = new MTExtConf();
            if ( isset($_POST[$obj::FORM_NAME]) ) {
                $obj->save();
            }
            show_window(__('MikroTik extended configuration'), $obj->render());
        } else show_window(__('Error'), __('No NAS was selected to add options!'));
    } else show_error(__('You cant control this module'));