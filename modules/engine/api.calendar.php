<?php
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

class calendar{
    var $_temp = array();
    var $_events = array();
    var $_highlight = array();
    
    function __construct($month, $year){
        global $system;
        $this->_temp['first_day_stamp'] = mktime(0, 0, 0, $month, 1, $year);
        $this->_temp['first_day_week_pos'] = date('w', $this->_temp['first_day_stamp']);
        $this->_temp['number_of_days'] = date('t', $this->_temp['first_day_stamp']);
    }
    
    function assignEvent($day, $link){
        $this->_events[(int)$day] = $link;
    }
    
    function highlightDay($day, $style = '!'){
        $this->_highlight[(int)$day] = $style;
    }
    
    function returnCalendar(){
        global $system;
        $return = '<table width="100%" border="0" cellspacing="1" cellpadding="0">';
        $return .= '<tr>';
        $return .= '<th align="center" colspan="7">' . rcms_date_localise(date('F Y', $this->_temp['first_day_stamp'])) . '</th>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= rcms_date_localise('<th align="center">Mon</th><th align="center">Tue</th><th align="center">Wed</th><th align="center">Thu</th><th align="center">Fri</th><th align="center">Sat</th><th align="center">Sun</th>');
        $return .= '</tr>';
        $days_showed = 1;
        $cwpos = $this->_temp['first_day_week_pos'];
        if($cwpos == 0) $cwpos = 7;
        while($days_showed <= $this->_temp['number_of_days']){
            $return .= '<tr>';
            if($cwpos > 1) {
                $return .= '<td colspan="' . ($cwpos-1) . '">&nbsp;</td>';
            }
            $inc = 0;
            for ($i = $days_showed; $i < $days_showed + 7 && $i <= $this->_temp['number_of_days'] && $cwpos <= 7; $i++){
                $class = '';
                if(!empty($this->_highlight[$i])) {
                    $class = 'special ';
                }
                if(empty($this->_events[$i])) {
                    $class .= 'row2';
                } else {
                    $class .= 'row3';
                }
                if(empty($this->_events[$i])) {
                    $return .= '<td align="center" class="' . $class . '">' . $i . '</td>';
                } else {
                    $return .= '<td align="center" class="' . $class . '"><a href="' . $this->_events[$i] . '"  class="' . $class . '">' . $i . '</a></td>';
                }
                $cwpos++;
                $inc++;
            }
            $days_showed = $days_showed + $inc;
            $cwpos = 0;
            $return .= '</tr>';
        }
        
        $return .= '</table>';
        return $return;
    }
    
}
?>