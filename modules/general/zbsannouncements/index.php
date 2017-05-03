<?php

if (cfr('ZBSANN')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['ANNOUNCEMENTS']) {

        /*
         * Userstats announcements base class
         */

        class ZbsAnnouncements {

            protected $data = array();

            const EX_ID_NO_EXIST = 'NO_EXISTING_ID_RECEIVED';

            public function __construct() {
                $this->loadData();
            }

            /**
             * loads all existing announcements into private data property
             * 
             * @return void
             */
            protected function loadData() {
                $query = "SELECT * from `zbsannouncements` ORDER by `id` DESC;";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->data[$each['id']] = $each;
                    }
                }
            }

            /**
             * deletes announcement from database
             * 
             * @param int $id existing announcement ID
             * 
             * @return void
             */
            public function delete($id) {
                $id = vf($id, 3);
                if (isset($this->data[$id])) {
                    $query = "DELETE from `zbsannouncements` WHERE `id`='" . $id . "';";
                    nr_query($query);
                    log_register("ANNOUNCEMENT DELETE [" . $id . "]");
                } else {
                    throw new Exception(self::EX_ID_NO_EXIST);
                }
            }

            /**
             * creates new announcement in database
             * @param int       $public
             * @param string    $type
             * @param string    $title
             * @param string    $text
             * 
             * @return int
             */
            public function create($public, $type, $title, $text) {
                $public = vf($public, 3);
                $type = vf($type);
                $title = mysql_real_escape_string($title);
                $text = mysql_real_escape_string($text);
                $query = "INSERT INTO `zbsannouncements` (`id`,`public`,`type`,`title`,`text`) VALUES
                (NULL, '" . $public . "', '" . $type . "', '" . $title . "', '" . $text . "'); ";
                nr_query($query);
                $newId = simple_get_lastid('zbsannouncements');
                log_register("ANNOUNCEMENT CREATE [" . $newId . "]");
                return ($newId);
            }

            /**
             * updates some existing announcement in database
             * 
             * @param int  $id   existing announcement ID
             * 
             * @return void
             */
            public function save($id) {
                $id = vf($id, 3);
                if (isset($this->data[$id])) {
                    simple_update_field('zbsannouncements', 'public', $_POST['editpublic'], "WHERE `id`='" . $id . "'");
                    simple_update_field('zbsannouncements', 'type', $_POST['edittype'], "WHERE `id`='" . $id . "'");
                    simple_update_field('zbsannouncements', 'title', $_POST['edittitle'], "WHERE `id`='" . $id . "'");
                    simple_update_field('zbsannouncements', 'text', $_POST['edittext'], "WHERE `id`='" . $id . "'");
                    log_register("ANNOUNCEMENT EDIT [" . $id . "]");
                } else {
                    throw new Exception(self::EX_ID_NO_EXIST);
                }
            }

            /**
             * returns announcement preview
             * 
             * @param int $id existing announcement ID
             * 
             * @return string
             */
            protected function preview($id) {
                $id = vf($id, 3);
                if (isset($this->data[$id])) {
                    $result = wf_tag('h3', false, 'row2', '') . $this->data[$id]['title'] . '&nbsp;' . wf_tag('h3', true);
                    $result.= wf_delimiter();
                    if ($this->data[$id]['type'] == 'text') {
                        $previewtext = strip_tags($this->data[$id]['text']);
                        $result.= nl2br($previewtext);
                    }

                    if ($this->data[$id]['type'] == 'html') {
                        $result.=$this->data[$id]['text'];
                    }
                    $result.=wf_delimiter();
                    return ($result);
                } else {
                    throw new Exception(self::EX_ID_NO_EXIST);
                }
            }

            /**
             * renders list of existing announcements by private data prop
             * 
             * @return string
             */
            public function render() {
                $cells = wf_TableCell(__('ID'));
                $cells.= wf_TableCell(__('Public'));
                $cells.= wf_TableCell(__('Type'));
                $cells.= wf_TableCell(__('Title'));
                $cells.= wf_TableCell(__('Text'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                if (!empty($this->data)) {
                    foreach ($this->data as $io => $each) {
                        $cells = wf_TableCell($each['id']);
                        $cells.= wf_TableCell(web_bool_led($each['public']));
                        $cells.= wf_TableCell($each['type']);
                        $cells.= wf_TableCell(strip_tags($each['title']));
                        if (strlen($each['text']) > 100) {
                            $textPreview = mb_substr(strip_tags($each['text']), 0, 100, 'utf-8') . '...';
                        } else {
                            $textPreview = strip_tags($each['text']);
                        }
                        $cells.= wf_TableCell($textPreview);
                        $actionLinks = wf_JSAlert('?module=zbsannouncements&delete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                        $actionLinks.= wf_JSAlert('?module=zbsannouncements&edit=' . $each['id'], web_edit_icon(), __('Are you serious'));
                        $actionLinks.= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->preview($each['id']), '', '600', '400');
                        $cells.= wf_TableCell($actionLinks);
                        $rows.= wf_TableRow($cells, 'row3');
                    }
                }
                $result = wf_TableBody($rows, '100%', 0, 'sortable');
                return ($result);
            }

            /**
             * returns announcement create form
             * 
             * @return string
             */
            public function createForm() {
                $states = array("1" => __('Yes'), "0" => __('No'));
                $types = array("text" => __('Text'), "html" => __('HTML'));

                $inputs = wf_TextInput('newtitle', __('Title'), '', true, 40);
                $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
                $inputs.= __('Text') . $sup . wf_tag('br');
                $inputs.= wf_TextArea('newtext', '', '', true, '60x10');
                $inputs.= wf_Selector('newpublic', $states, __('Public'), '', false);
                $inputs.= wf_Selector('newtype', $types, __('Type'), '', false);
                $inputs.= wf_delimiter();
                $inputs.= wf_Submit(__('Create'));
                $result = wf_Form("", 'POST', $inputs, 'glamour');
                return ($result);
            }

            /**
             * returns announcement edit form
             * 
             * @param int $id existing announcement ID
             *  
             * @return string
             */
            public function editForm($id) {
                $id = vf($id, 3);
                $states = array("1" => __('Yes'), "0" => __('No'));
                $types = array("text" => __('Text'), "html" => __('HTML'));
                $result = wf_BackLink('?module=zbsannouncements');
                $result.=wf_modal(web_icon_search().' '.__('Preview'), __('Preview'), $this->preview($id), 'ubButton', '600', '400');
                $result.=wf_delimiter();
                if (isset($this->data[$id])) {
                    $inputs = wf_TextInput('edittitle', __('Title'), $this->data[$id]['title'], true, 40);
                    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
                    $inputs.= __('Text') . $sup . wf_tag('br');
                    $inputs.= wf_TextArea('edittext', '', $this->data[$id]['text'], true, '60x10');
                    $inputs.= wf_Selector('editpublic', $states, __('Public'), $this->data[$id]['public'], false);
                    $inputs.= wf_Selector('edittype', $types, __('Type'), $this->data[$id]['type'], false);
                    $inputs.= wf_delimiter();
                    $inputs.= wf_Submit(__('Save'));
                    $result.= wf_Form("", 'POST', $inputs, 'glamour');
                    return ($result);
                } else {
                    throw new Exception(self::EX_ID_NO_EXIST);
                }
            }

        }

        /*
         * module code part
         */
        $announcements = new ZbsAnnouncements();


        //creating new one
        if (wf_CheckPost(array('newtext', 'newtype'))) {
            $announcements->create($_POST['newpublic'], $_POST['newtype'], $_POST['newtitle'], $_POST['newtext']);
            rcms_redirect('?module=zbsannouncements');
        }

        //deleting announcement
        if (wf_CheckGet(array('delete'))) {
            $announcements->delete($_GET['delete']);
            rcms_redirect('?module=zbsannouncements');
        }

        if (isset($_GET['edit'])) {
            if (wf_CheckPost(array('edittext', 'edittype'))) {
                $announcements->save($_GET['edit']);
                rcms_redirect('?module=zbsannouncements&edit=' . $_GET['edit']);
            }
            show_window(__('Edit'), $announcements->editForm($_GET['edit']));
        } else {
            //show announcements list and create form
            show_window(__('Userstats announcements'), $announcements->render());
            show_window('', wf_modal(web_icon_create().' '.__('Create'), __('Create'), $announcements->createForm(), 'ubButton', '600', '400'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>