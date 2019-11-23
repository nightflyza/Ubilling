<?php

class InputForm {

    var $options = array();
    var $_rows;
    var $_hiddens;
    var $_elements = array(
        'hidden' => '<input type="hidden" name="%1" value="%2" />',
        'file' => '<input type="file" name="%1" value="" />',
    );

    public function __construct($action = '', $method = 'get', $submit = 'Submit', $reset = '', $target = '', $enctype = '', $name = '', $events = '') {
        $this->InputForm($action, $method, $submit, $reset, $target, $enctype, $name, $events);
    }

    public function InputForm($action = '', $method = 'get', $submit = 'Submit', $reset = '', $target = '', $enctype = '', $name = '', $events = '') {
        $this->options = array(
            'action' => $action,
            'method' => $method,
            'target' => $target,
            'enctype' => $enctype,
            'events' => $events,
            'submit' => $submit,
            'name' => $name,
            'reset' => $reset,
        );
    }

    function addrow($title, $contents = '', $valign = 'middle', $align = 'left') {
        list($talign, $calign) = array_pad(explode(',', $align, 2), -2, null);
        if (empty($calign)) {
            $calign = $talign;
        }
        list($tvalign, $cvalign) = array_pad(explode(',', $valign, 2), -2, null);
        if (empty($cvalign)) {
            $cvalign = $tvalign;
        }
        $this->_rows[] = array(
            'title' => $title,
            'contents' => $contents,
            'title_valign' => $tvalign,
            'content_valign' => $cvalign,
            'title_align' => $talign,
            'content_align' => $calign
        );
        end($this->_rows);
        return key($this->_rows);
    }

    function hidden($name, $value) {
        $this->_hiddens[$name] = $value;
    }

    function addbreak($break = "&nbsp;") {
        $this->_rows[] = array('break' => $break);
        end($this->_rows);
        return key($this->_rows);
    }

    function addmessage($message) {
        $this->_rows[] = array("message" => $message);
    }

    function show($return = false) {
        $result = '<form action="' . $this->options['action'] . '" method="' . $this->options['method'] . '" name="' . $this->options['name'] . '"';
        if (!empty($this->options['target'])) {
            $result .= ' target="' . $this->options['target'] . '"';
        }
        if (!empty($this->options['enctype'])) {
            $result .= ' enctype="' . $this->options['enctype'] . '"';
        }
        if (!empty($this->options['events'])) {
            $result .= ' ' . $this->options['events'];
        }
        $result .= '>' . "\n";

        if (is_array($this->_hiddens)) {
            foreach ($this->_hiddens as $name => $value) {
                $result .= str_replace(array('%1', '%2'), array($name, $value), $this->_elements['hidden']) . "\n";
            }
        }

        $result .= '<table border="0" cellspacing="2" cellpadding="2" width="100%">' . "\n";

        if (is_array($this->_rows)) {
            foreach ($this->_rows as $row) {
                if (!empty($row['break'])) {
                    $result .= '<tr>' . "\n";
                    $result .= '  <th colspan="2">' . $row['break'] . '</td>' . "\n";
                    $result .= '</tr>' . "\n";
                } elseif (!empty($row['message'])) {
                    $result .= '<tr>' . "\n";
                    $result .= '  <td colspan="2" class="row1">' . $row['message'] . '</td>' . "\n";
                    $result .= '</tr>' . "\n";
                } else {
                    $result .= '<tr>' . "\n";
                    $result .= '  <td valign="' . $row['title_valign'] . '" align="' . $row['title_align'] . '" class="row2" ' . ((empty($row['contents'])) ? ' colspan="2"' : '') . '>' . $row['title'] . '</td>' . "\n";
                    if (!empty($row['contents'])) {
                        $result .= '  <td valign="' . $row['title_valign'] . '" align="' . $row['title_align'] . '" class="row3">' . $row['contents'] . '</td>' . "\n";
                    }
                    $result .= '</tr>' . "\n";
                }
            }
        }
        $result .= '<tr>' . "\n";
        $result .= '  <td align="center" colspan="2"><input type="submit" value="' . $this->options['submit'] . '" class="ubButton">';
        if (!empty($this->options['reset'])) {
            $result .= '<input type="reset" value="' . $this->options['reset'] . '" class="ubButton">';
        }
        $result .= '</td>' . "\n";
        $result .= '</tr>' . "\n";
        $result .= '</table>' . "\n";
        $result .= '</form>' . "\n";
        if ($return) {
            return $result;
        } else {
            echo $result;
            return true;
        }
    }

    function text_box($name, $value, $size = 0, $maxlength = 0, $password = false, $extra = '') {
        return '<input type="' . (($password) ? 'password' : 'text') . '" class="text" name="' . $name . '"' . (($size > 0) ? ' size="' . $size . '"' : '') . (($maxlength > 0) ? ' maxlength="' . $maxlength . '"' : '') . ' value="' . htmlspecialchars($value) . '" ' . $extra . '>';
    }

    function textarea($name, $value, $cols = 30, $rows = 5, $extra = '') {
        return '<textarea name="' . $name . '" cols="' . $cols . '" rows="' . $rows . '" ' . $extra . '>' . htmlspecialchars($value) . '</textarea>';
    }

    function select_tag($name, $values, $selected = '', $extra = '') {
        $data = '<select name="' . $name . '" ' . $extra . '>' . "\n";
        foreach ($values as $value => $text) {
            $data .= '<option value="' . $value . '" ' . (($selected == $value) ? 'selected' : '') . '>' . __($text) . '</option>' . "\n";
        }
        $data .= '</select> ' . "\n";
        return $data;
    }

    function radio_button($name, $values, $selected = '', $separator = ' ', $extra = '') {
        $data = '';
        foreach ($values as $value => $text) {
            $id = rcms_random_string(5);
            $data .= '<input type="radio" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . (($selected == $value) ? 'checked' : '') . ' ' . $extra . '><label for="' . $id . '">' . $text . '</label>' . $separator;
        }
        return $data;
    }

    function radio_button_single($name, $value, $selected = '', $caption = ' ', $extra = '') {
        $id = rcms_random_string(5);
        return '<input type="radio" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . (($selected) ? 'checked' : '') . ' ' . $extra . '><label for="' . $id . '">' . $caption . '</label>';
    }

    function checkbox($name, $value, $caption, $checked = 0, $extra = '') {
        $id = rcms_random_string(5);
        return '<input type="checkbox" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . ((!empty($checked)) ? 'checked' : '') . ' ' . $extra . ' /><label for="' . $id . '">' . $caption . '</label>';
    }

    function file($name) {
        return '<input type="file" name="' . $name . '" value="" />';
    }

}
