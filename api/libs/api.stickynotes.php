<?php

class StickyNotes {

    protected $allnotes = array();
    protected $activenotes = array();
    protected $myLogin = '';

    /**
     * Preloads all needed data for sticky notes entity
     * 
     * @param boolean $onlyActive
     */
    public function __construct($onlyActive) {
        $this->setLogin();
        if ($onlyActive) {
            $this->loadActiveNotes();
        } else {
            $this->loadAllNotes();
        }
    }

    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads notes from database into private property
     * 
     * @return void
     */
    protected function loadAllNotes() {
        $query = "SELECT * from `stickynotes` WHERE `owner`= '" . $this->myLogin . "' ORDER BY `id` DESC";
        $tmpArr = simple_queryall($query);
        //map id=>id
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allnotes[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads active/remind notes from database into private property
     * 
     * @return void
     */
    protected function loadActiveNotes() {
        $query = "SELECT * from `stickynotes` WHERE `owner`= '" . $this->myLogin . "' AND `active`='1' ORDER BY `id` ASC";
        $tmpArr = simple_queryall($query);
        //map id=>id
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->activenotes[$each['id']] = $each;
            }
        }
    }

    /**
     * Returns note data extracted from private allnotes property
     * 
     * @param int $noteId
     * 
     * @return array
     */
    protected function getNoteData($noteId) {
        $result = array();
        if (!empty($this->allnotes)) {
            if (isset($this->allnotes[$noteId])) {
                $result = $this->allnotes[$noteId];
            }
        }
        return ($result);
    }

    /**
     * Returns cutted string if needed
     * 
     * @param string $string
     * @param int $size
     * @return string
     */
    protected function cutString($string, $size) {
        if ((mb_strlen($string, 'UTF-8') > $size)) {
            $string = mb_substr($string, 0, $size, 'utf-8') . '...';
        }
        return ($string);
    }

    /**
     * Returns list of available sticky notes with it controls as grid
     * 
     * @return string
     */
    public function renderListGrid() {
        $cells = wf_TableCell(__('Creation date'));
        $cells.= wf_TableCell(__('Remind date'));
        $cells.= wf_TableCell(__('Time'));
        $cells.= wf_TableCell(__('Status'));
        $cells.= wf_TableCell(__('Text'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allnotes)) {
            foreach ($this->allnotes as $io => $each) {
                $cells = wf_TableCell($each['createdate']);
                $cells.= wf_TableCell($each['reminddate']);
                $cells.= wf_TableCell($each['remindtime']);
                $cells.= wf_TableCell(web_bool_led($each['active']), '', '', 'sorttable_customkey="' . $each['active'] . '"');
                $viewLink = wf_Link('?module=stickynotes&shownote=' . $each['id'], $this->cutString($each['text'], 100), false, '');
                $cells.= wf_TableCell($viewLink);
                $actLinks = wf_JSAlert('?module=stickynotes&delete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->editForm($each['id']), '') . ' ';
                $actLinks.= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), nl2br(strip_tags($each['text'])), '', '640', '480');
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Returns available sticky notes list as full calendar
     * 
     * @return string
     */
    public function renderListCalendar() {
        $calendarData = '';
        if (!empty($this->allnotes)) {
            foreach ($this->allnotes as $io => $each) {
                $timestamp = strtotime($each['createdate']);
                $date = date("Y, n-1, j", $timestamp);
                $rawTime = date("H:i:s", $timestamp);

                if ($each['active'] == 1) {
                    $coloring = "className : 'undone',";
                } else {
                    $coloring = '';
                }

                if (!empty($each['reminddate'])) {
                    $remindtimestamp = strtotime($each['reminddate']);
                    $reminddate = date("Y, n-1, j", $remindtimestamp);
                    $textLenght = 48;
                } else {
                    $reminddate = $date;
                    $textLenght = 24;
                }

                $shortText = $each['text'];
                $shortText = str_replace("\n", '', $shortText);
                $shortText = str_replace("\r", '', $shortText);
                $shortText = str_replace("'", '`', $shortText);
                $shortText = strip_tags($shortText);
                $shortText = $this->cutString($shortText, $textLenght);

                $calendarData.="
                      {
                        title: '" . $rawTime . " " . $shortText . " ',
                        url: '?module=stickynotes&shownote=" . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $reminddate . "),
                       " . $coloring . "     
                   },
                    ";
            }
        }

        $result = wf_FullCalendar($calendarData);
        return ($result);
    }

    /**
     * 
     * @param string  $owner
     * @param string  $createDate
     * @param string  $remindDate
     * @param int     $activity
     * @param string  $text
     */
    protected function createNote($owner, $createDate, $remindDate, $remindTime, $activity, $text) {
        $text = strip_tags($text);
        $text = mysql_real_escape_string($text);
        $activity = vf($activity, 3);
        $createDate = mysql_real_escape_string($createDate);
        if (!empty($remindDate)) {
            $remindDate = "'" . mysql_real_escape_string($remindDate) . "'";
        } else {
            $remindDate = 'NULL';
        }

        if (!empty($remindTime)) {
            $remindTime = "'" . mysql_real_escape_string($remindTime) . "'";
        } else {
            $remindTime = 'NULL';
        }
        $query = "INSERT INTO `stickynotes` (`id`, `owner`, `createdate`, `reminddate`,`remindtime`, `active`, `text`) "
                . "VALUES (NULL, '" . $this->myLogin . "', '" . $createDate . "', " . $remindDate . ", " . $remindTime . " , '" . $activity . "', '" . $text . "');";
        nr_query($query);
    }

    /**
     * Renders notify container with some text inside
     * 
     * @param string $text
     * @return string
     */
    protected function renderStickyNote($text, $offsetLeft = 0) {
        $result = '';
        if (!empty($text)) {
            if ($offsetLeft) {
                $offsetLeft = 35 + $offsetLeft . 'px';
                $offsetTop = 25 + round($offsetLeft / 5) . 'px';
            } else {
                $offsetLeft = '35px';
                $offsetTop = '30px';
            }

            $result.= wf_tag('div', false, 'stickynote', 'style="margin:' . $offsetTop . ' ' . $offsetLeft . ' 20px 20px;"');
            $result.= wf_Link('?module=stickynotes', wf_img('skins/pushpin.png'), false, '') . wf_tag('br');
            $result.= $text;
            $result.= wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Returns control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result.= wf_modalAuto(wf_img('skins/pushpin.png') . ' ' . __('Create new personal note'), __('Create new personal note'), $this->createForm(), 'ubButton');
        $result.= wf_Link('?module=stickynotes', wf_img('skins/icon_table.png') . ' ' . __('Grid view'), false, 'ubButton');
        $result.= wf_Link('?module=stickynotes&calendarview=true', wf_img('skins/icon_calendar.gif') . ' ' . __('As calendar'), false, 'ubButton');

        return ($result);
    }

    /**
     * Returns create form
     * 
     * @return string
     */
    protected function createForm() {
        $inputs = wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
        $inputs.= wf_TextArea('newtext', '', '', true, '50x15');
        $inputs.= wf_CheckInput('newactive', __('Create note as active'), true, true);
        $inputs.= wf_DatePickerPreset('newreminddate', '');
        $inputs.= wf_tag('label') . __('Remind only after this date') . wf_tag('label', true);
        $inputs.=wf_tag('br');
        $inputs.= wf_TimePickerPreset('newremindtime', '', __('Remind time'), false);
        $inputs.= wf_tag('br');
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Returns edit form
     * 
     * @return string
     */
    protected function editForm($noteId) {
        $noteData = $this->getNoteData($noteId);
        if (!empty($noteData)) {
            $inputs = wf_HiddenInput('editnoteid', $noteId);
            $inputs.= wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
            $inputs.= wf_TextArea('edittext', '', $noteData['text'], true, '50x15');
            $checkState = ($noteData['active'] == 1) ? true : false;
            $inputs.= wf_CheckInput('editactive', __('Personal note active'), true, $checkState);
            $inputs.= wf_DatePickerPreset('editreminddate', $noteData['reminddate']);
            $inputs.= wf_tag('label') . __('Remind only after this date') . wf_tag('label', true);
            $inputs.= wf_tag('br');
            $inputs.= wf_TimePickerPreset('editremindtime', $noteData['remindtime'], __('Remind time'), true);
            $inputs.= wf_tag('br');
            $inputs.= wf_tag('br');
            $inputs.= wf_Submit(__('Save'));

            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('Strange exeption');
        }
        return ($result);
    }

    /**
     * Creates new personal note in database
     * 
     * @return void
     */
    public function addMyNote() {
        if (wf_CheckPost(array('newtext'))) {
            $owner = $this->myLogin;
            $createDate = curdatetime();
            $remindDate = (!empty($_POST['newreminddate'])) ? $_POST['newreminddate'] : '';
            $remindTime = (!empty($_POST['newremindtime'])) ? $_POST['newremindtime'] : '';
            $activity = (isset($_POST['newactive'])) ? 1 : 0;
            $text = $_POST['newtext'];
            $this->createNote($owner, $createDate, $remindDate, $remindTime, $activity, $text);

            $newId = simple_get_lastid('stickynotes');
            log_register("STICKY CREATE [" . $newId . "]");
        }
    }

    /**
     * Saves changes of note in database
     * 
     * @return void
     */
    public function saveMyNote() {
        if (wf_CheckPost(array('edittext', 'editnoteid'))) {
            $noteId = vf($_POST['editnoteid'], 3);
            $noteData = $this->getNoteData($noteId);
            if (!empty($noteData)) {
                $where = "WHERE `id`='" . $noteId . "';";
                //text
                $text = strip_tags($_POST['edittext']);
                $oldText = $noteData['text'];
                if ($text != $oldText) {
                    simple_update_field('stickynotes', 'text', $text, $where);
                    log_register("STICKY CHANGED TEXT [" . $noteId . "]");
                }
                //remind date
                $remindDate = $_POST['editreminddate'];
                $oldRemindDate = $noteData['reminddate'];
                if ($remindDate != $oldRemindDate) {
                    if (!empty($remindDate)) {
                        $remindDate = "'" . mysql_real_escape_string($remindDate) . "'";
                    } else {
                        $remindDate = 'NULL';
                    }
                    $query = "UPDATE `stickynotes` SET `reminddate` = " . $remindDate . " " . $where; // ugly hack, i know
                    nr_query($query);
                    log_register("STICKY CHANGED REMINDDATE [" . $noteId . "] ON " . $remindDate . "");
                }
                
                //remind time 
                $remindTime = $_POST['editremindtime'];
                $oldRemindTime = $noteData['remindtime'];
                if ($remindTime != $oldRemindTime) {
                    if (!empty($remindTime)) {
                        $remindTime = "'" . mysql_real_escape_string($remindTime) . "'";
                    } else {
                        $remindTime = 'NULL';
                    }
                    $query = "UPDATE `stickynotes` SET `remindtime` = " . $remindTime . " " . $where;
                    nr_query($query);
                    log_register("STICKY CHANGED REMINDTIME [" . $noteId . "] ON " . $remindTime . "");
                }
                
                //activity flag
                $activity = (isset($_POST['editactive'])) ? 1 : 0;
                $oldActivity = $noteData['active'];
                if ($activity != $oldActivity) {
                    simple_update_field('stickynotes', 'active', $activity, $where);
                    log_register("STICKY CHANGED ACTIVE [" . $noteId . "] ON " . $activity . "");
                }
            }
        }
    }

    /**
     * Deletes some note by its ID
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteNote($id) {
        $id = vf($id, 3);
        if (!empty($id)) {
            $query = "DELETE FROM `stickynotes` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("STICKY DELETE [" . $id . "]");
        }
    }

    /**
     * Returns full text of sticky note with check of owner
     * 
     * @param int $noteId
     * 
     * @return string
     */
    public function renderNote($noteId) {
        $noteId = vf($noteId, 3);
        $result = '';
        $noteData = $this->getNoteData($noteId);

        if (!empty($noteData)) {
            if ($noteData['owner'] == $this->myLogin) {
                $result = strip_tags($noteData['text']);
                $result = nl2br($result);
                $result.= wf_delimiter(2);
                $result.= wf_BackLink('?module=stickynotes');
                $result.= wf_modalAuto(web_edit_icon().' '.__('Edit'), __('Edit'), $this->editForm($noteId), 'ubButton') . ' ';
            } else {
                $result = __('Access denied');
            }
        } else {
            $result = __('Strange exeption');
        }
        return ($result);
    }

    /**
     * Returns available taskbar notifications as floating widget
     * 
     * @return string
     */
    public function renderTaskbarNotify() {
        $result = '';
        $output = '';
        $delimiterId = '{' . zb_rand_string(8) . '}';
        $delimiterCode = '';
        $offsetLeft = 0;

        if (!empty($this->activenotes)) {
            foreach ($this->activenotes as $io => $each) {
                $noteDate = $each['reminddate'];
                $noteTime = $each['remindtime'];
                if (!empty($noteTime)) {
                    if (!empty($noteDate)) {
                        $noteDate = $noteDate . ' ' . $noteTime;
                    } else {
                        $noteDate = curdate() . ' ' . $noteTime;
                    }
                }
                if (empty($noteDate) OR ( strtotime($noteDate) <= time())) {
                    $tmpText = $each['text'];
                    $tmpText = strip_tags($tmpText);
                    $output = $tmpText;
                    $output.=$delimiterId;

                    $output = str_replace($delimiterId, $delimiterCode, $output);
                    $output = $this->cutString($output, 190);
                    $output = nl2br($output);


                    $result.=$this->renderStickyNote($output, $offsetLeft);
                    $offsetLeft = $offsetLeft + 10;
                }
            }
        }
        return ($result);
    }

}

?>