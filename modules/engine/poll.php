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
define('RCMS_POLL_DATAFILE', DF_PATH . 'poll.dat');
define('RCMS_POLL_ARCHIVE_DATAFILE', DF_PATH . 'poll-archive.dat');

class polls{
    var $polls_file = RCMS_POLL_DATAFILE;
    var $old_polls_file = RCMS_POLL_ARCHIVE_DATAFILE;
    
    var $current = array();
    var $old = array();
    var $lasterror = '';
    
    var $copened = false;
    var $oopened = false;
    
    function openCurrentPolls(){
        if(file_exists($this->polls_file) && !is_readable($this->polls_file)) {
            $this->lasterror = $this->polls_file . ' ' . __('is not readable');
            return false;
        }
        if(file_exists($this->polls_file)){
            if(($file = unserialize(file_get_contents($this->polls_file))) === false) {
                $this->lasterror = __('Cannot parse') . ' ' . $this->polls_file;
                return false;
            }
        } else $file = array();
        
        $this->current = $file;
        $this->copened = true;
    }
    
    function openArchivedPolls(){
        if(file_exists($this->old_polls_file) && !is_readable($this->old_polls_file)) {
            $this->lasterror = $this->old_polls_file . ' ' . __('is not readable');
            return false;
        }
        if(file_exists($this->old_polls_file)){
            if(!($file = unserialize(file_get_contents($this->old_polls_file)))) {
                $this->lasterror = __('Cannot parse') . ' ' . $this->old_polls_file;
                return false;
            }
        } else $file = array();
        
        $this->old = $file;
        $this->oopened = true;
    }
    
    function close($uc = true, $uo = true){
        if($uc) {
        	if(!$this->copened) $this->openCurrentPolls();
        	$a = file_write_contents($this->polls_file, serialize($this->current));
        }
        if($uo) {
        	if(!$this->oopened) $this->openArchivedPolls();
        	$b = file_write_contents($this->old_polls_file, serialize($this->old));
        }
        if($uc && $uo) {
        	return $a && $b;
        } elseif ($uo) {
        	return $b;
        } elseif ($uc) {
        	return $a;
        } else {
        	return true;
        }
    }
    
    function getCurrentPolls(){
        if(!$this->copened) $this->openCurrentPolls();
        $return = array();
        foreach ($this->current as $id => $data){
            $data['t'] = 0;
            foreach ($data['c'] as $v_id => $v_total){
                $data['t'] += $v_total;
            }
            foreach ($data['c'] as $v_id => $v_total){
                if($data['t'] != 0) $data['p'][$v_id] = round(($v_total/$data['t'])*100); else $data['p'][$v_id] = 0;
            }
            $data['X'] = $data['c'];
            natsort($data['X']);
            $data['X'] = array_reverse($data['X'], true);
            $return[$id] = $data;
        }
        return $return;
    }
    
    function getArchivedPolls(){
        if(!$this->oopened) $this->openArchivedPolls();
        $return = array();
        foreach ($this->old as $id => $data){
            $data['t'] = 0;
            foreach ($data['c'] as $v_id => $v_total){
                $data['t'] += $v_total;
            }
            foreach ($data['c'] as $v_id => $v_total){
                if($data['t'] != 0) $data['p'][$v_id] = round(($v_total/$data['t'])*100); else $data['p'][$v_id] = 0;
            }
            $data['X'] = $data['c'];
            natsort($data['X']);
            $data['X'] = array_reverse($data['X'], true);
            $return[$id] = $data;
        }
        return $return;
    }
    
    function startPoll($question, $answers){
        if(empty($question) || empty($answers)) {
            $this->lasterror = __('Empty question or no variants');
            return false;
        }
        if(!$this->copened) $this->openCurrentPolls();
        $data['q'] = $question;
        foreach (explode("\n", preg_replace("/[\n\r]+/", "\n", $answers)) as $variant) {
            if(!empty($variant)) {
                $data['v'][] = $variant;
                $data['c'][] = 0;
            }
        }
        $this->current[rcms_random_string(8)] = $data;
        return true;
    }

    function removePoll($id, $archive = true){
        if(!$this->copened) $this->openCurrentPolls();
        if($archive) $this->archivePoll($id, $this->current[$id]);
        $new = array();
        foreach ($this->current as $key => $value) {
        	if($key != $id) $new[$key] = $value;
        }
        $this->current = $new;
        return true;
    }

    function removePollFromArchive($id){
        if(!$this->oopened) $this->openArchivedPolls();
        $new = array();
        foreach ($this->old as $key => $value) {
        	if($key != $id) $new[$key] = $value;
        }
        $this->old = $new;
        return true;
    }
    
    function archivePoll($id, $data){
        if(!$this->oopened) $this->openArchivedPolls();
        $this->old[$id] = $data;
        return true;
    }
    
    function voteInPoll($poll, $answer, $ip){
        if(!$this->copened) $this->openCurrentPolls();
        if(empty($this->current[$poll])) {
            $this->lasterror = __('No poll with this id');
            return false;
        }
        if(!isset($this->current[$poll]['c'][$answer])) {
            $this->lasterror = __('This answer does not exists in this poll');
            return false;
        }
        if($this->isVotedInPoll($poll, $ip)) {
            $this->lasterror = __('You already voted in this poll');
            return false;
        }
        $this->current[$poll]['c'][$answer]++;
        $this->current[$poll]['ips'][] = $ip;
        setcookie('reloadcms_poll[' . $poll . ']', $poll, FOREVER_COOKIE);
        return true;
    }
    
    function isVotedInPoll($poll, $ip){
        if(!$this->copened) $this->openCurrentPolls();
        if(empty($this->current[$poll])) {
        	return true;
        }
        if(@is_array($this->current[$poll]['ips']) && in_array($ip, $this->current[$poll]['ips'])) {
        	return true;
        }
        if(!empty($_COOKIE['reloadcms_poll']) && is_array($_COOKIE['reloadcms_poll']) && in_array($poll, $_COOKIE['reloadcms_poll'])) {
        	return true;
        }
        return false;
    }
}
?>