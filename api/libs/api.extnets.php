<?php

class ExtNets {
    
    protected $networks=array();
    protected $pools=array();
    protected $ips=array();
    protected $switches=array();
    protected $masklimits=array('upper'=>30,'lower'=>24);
    protected $cidrs=array();
    protected $cidrToMask=array();
    protected $cidrOffsets=array();
                        
    
    
    const EX_NOEXNET='NOT_EXISTING_NET_ID';
    const EX_NOEXPOOL='NOT_EXISTING_POOL_ID';
    const EX_NOEXIP='NOT_EXISTING_IP_ID';
    
    public function __construct() {
        $this->preprocessCidrMasks();
        $this->loadNetworks();
        $this->loadPools();
        $this->loadIps();
    }
    
    
    /**
     * transform net/CIDR notation to netmask
     * 
     * @param $cidr string - network/CIDR
     * 
     * @return array
     */
    protected function v4CIDRtoMask($cidr) {
    $cidr = explode('/', $cidr);
    return array($cidr[0], long2ip(-1 << (32 - (int)$cidr[1])));
    } 
    
    /**
     * prepare private CIDR mask data for following usage
     * 
     * @return void
     */
    protected function preprocessCidrMasks() {
        $startOffset=2;
        if (!empty($this->masklimits)) {
            for ($i=$this->masklimits['upper'];$i>=$this->masklimits['lower'];$i--) {
                $this->cidrs[$i]=$i;
            }
            
            foreach ($this->cidrs as $each=>$cidr) {
                $curMask=$this->v4CIDRtoMask('/'.$each);
                $this->cidrToMask[$each]=$curMask[1];
                $startOffset=$startOffset*2;
                $this->cidrOffsets[$each]=$startOffset;
                
            }
        }
    }




    /**
     * loads actual `other` networks array from database
     * 
     * @return void
     */
    protected function loadNetworks() {
        $query="SELECT * from `networks` WHERE `nettype`='other';";
        $all=  simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $this->networks[$each['id']]=$each;
            }
        }
    }
    
    /**
     * loads existing extpools from database into private pools property
     * 
     * @return void
     */
    protected function loadPools() {
        $query="SELECT * from `netextpools` ORDER by `id` ASC";
        $all=  simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $this->pools[$each['id']]=$each;
            }
        }
    }
    
    /**
     * renders existing networks list accessible for pools assign
     * 
     * @return string
     */
    public function renderNetworks() {
             //active row hlight
             if (wf_CheckGet(array('showpoolbynetid'))) {
                 $hlightNetId=vf($_GET['showpoolbynetid'],3);
             } else {
                 $hlightNetId='NONE';
             }
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('First IP'));
        $cells.= wf_TableCell(__('Last IP'));
        $cells.= wf_TableCell(__('Network/CIDR'));
        $rows=  wf_TableRow($cells, 'row1');
        
        if (!empty($this->networks)) {
            foreach ($this->networks as $io=>$each) {
                $cells=  wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['startip']);
                $cells.= wf_TableCell($each['endip']);
                $actLink=  wf_Link('?module=extnets&showpoolbynetid='.$each['id'], $each['desc'], false, '');
                $cells.= wf_TableCell($actLink);
                if ($each['id']!=$hlightNetId) {
                    $rowClass='row3';
                } else {
                    $rowClass='row2';
                }
                $rows.=  wf_TableRow($cells, $rowClass);
              }
        }
        $result= wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }
    
    /**
     * returns network CIDR by id 
     * 
     * @param $netid
     * 
     * @return string
     */
    public function getNetworkCidr($netid) {
        $netid=vf($netid,3);
        if (isset($this->networks[$netid])) {
            $result=$this->networks[$netid]['desc'];
        } else {
            $result='';
            throw new Exception(self::EX_NOEXNET);
        }
        
        return ($result);
    }
    
    /**
     * renders available pools assigned by some network
     * 
     * @param $netid int existing network ID
     * 
     * @return string
     */
    public function  renderPools($netid) {
        $netid=vf($netid,3);
        $result=__('Nothing found');
        $netpools=array();
        if (isset($this->networks[$netid])) {
            if (!empty($this->pools)) {
                foreach ($this->pools as $io=>$each) {
                    if ($each['netid']==$netid) {
                        $netpools[$each['id']]=$each;
                    }
                }
            }
        }
        
        if (!empty($netpools)) {
            $cells=  wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Pool'));
            $cells.= wf_TableCell(__('Netmask'));
            $cells.= wf_TableCell(__('Gateway'));
            $cells.= wf_TableCell(__('IP'));
            $cells.= wf_TableCell(__('Broadcast'));
            $cells.= wf_TableCell(__('VLAN'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Actions'));
            $rows=  wf_TableRow($cells, 'row1');
            
            foreach ($netpools as $io=>$each) {
                $cells=  wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['pool']);
                $cells.= wf_TableCell($this->cidrToMask[$each['netmask']]. ' (/'.$each['netmask'].')');
                $cells.= wf_TableCell($each['gw']);
                $cells.= wf_TableCell(wf_Link('?module=extnets&showipsbypoolid='.$each['id'], $this->ipsGetAssociated($each['id']), false),'40%');
                $cells.= wf_TableCell($each['broadcast']);
                $cells.= wf_TableCell($each['vlan']);
                if (!empty($each['login'])) {
                    $loginlink=  wf_Link('?module=userprofile&username='.$each['login'], web_profile_icon().' '.$each['login'], 'fasle');
                } else {
                    $loginlink='';
                }
                $cells.= wf_TableCell($loginlink);
                $actlinks=  wf_JSAlert('?module=extnets&showpoolbynetid='.$netid.'&deletepoolid='.$each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actlinks.= wf_modal(web_edit_icon(), __('Edit').' '.$each['pool'].'/'.$each['netmask'], $this->poolEditForm($each['id']), '', '300', '200');
                $cells.= wf_TableCell($actlinks);
                $rows.=  wf_TableRow($cells, 'row3');
            }
            
            $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        }
        
        return ($result);
    }
    
    
    /**
     * returns last unused network address for new pool for some netid
     * 
     * @param $netid existing network ID
     * 
     * @return int
     */
    protected function getFreePoolNet($netid) {
        $netid=vf($netid,3);
        $curNetPools=array();
        $result=false;
        if (!empty($this->networks[$netid])) {
            if (!empty($this->pools)) {
                //select last broadcast +1
                foreach ($this->pools as $io=>$each) {
                    if ($each['netid']==$netid) {
                        $curNetPools[$each['id']]=ip2long($each['broadcast'])+1;
                    }
                } 
            } else {
                 //network start IP
                $curNetPools[0]=ip2long($this->networks[$netid]['startip']);
            }
            
            if (empty($curNetPools)) {
                //network start IP
                $curNetPools[0]=ip2long($this->networks[$netid]['startip']);
            }
            $result=max($curNetPools);
            $result=  long2ip($result);
        }
        return ($result);
    }
    
    /**
     * returns new pool creation form
     * 
     * @param $netid int existing network ID
     * 
     * @return string
     */
    public function poolCreateForm($netid) {
        $netid=vf($netid,3);
        $poolProposal=$this->getFreePoolNet($netid);
        $inputs= wf_TextInput('newpool', __('Network'), $poolProposal, false, '15');
        $inputs .= wf_HiddenInput('newpoolnetid', $netid);
        $inputs.=  wf_Selector('newpoolnetmask', $this->cidrs, __('Netmask'));
        $inputs.= wf_tag('br');
        $inputs.= wf_TextInput('newpoolvlan', __('VLAN'), '', true, '6');
        $inputs.= wf_Submit(__('Create'));
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }
    
    /**
     * Creates new address pool in database
     * 
     * @param ...
     * 
     * 
     * @return void
     */
    public function poolCreate($netid,$pool,$netmask,$vlan) {
        $netid=vf($netid,3);
        $pool=  mysql_real_escape_string($pool);
        $netmask=vf($netmask);
        $vlan=vf($vlan,3);
        
        $query="INSERT INTO `netextpools` (`id`, `netid`, `pool`, `netmask`, `gw`, `clientip`, `broadcast`, `vlan`, `login`) "
             . "VALUES (NULL, '".$netid."', '".$pool."', '".$netmask."', NULL, NULL, NULL, '".$vlan."', NULL);";
        nr_query($query);
        $newPoolId=  simple_get_lastid('netextpools');
        log_register("POOL CREATE [".$newPoolId."] `".$pool."/".$netmask."`");
        $newGw=  long2ip(ip2long($pool)+1);
        $newBroadcast=long2ip(ip2long($pool)+($this->cidrOffsets[$netmask]-1));
        simple_update_field('netextpools', 'gw', $newGw, "WHERE `id`='".$newPoolId."';");
        simple_update_field('netextpools', 'broadcast', $newBroadcast, "WHERE `id`='".$newPoolId."';");
        //creating ips list for pool
        $newIpsStart=  long2ip(ip2long($newGw)+1);
        $newIpsEnd=  long2ip(ip2long($newBroadcast)-1);
        $this->ipsCreate($newPoolId, $newIpsStart, $newIpsEnd);
        
    }
    
    /**
     * deletes existing pool by ID from database
     * 
     * @param $poolid int existing pool ID
     * 
     * @return void
     */
    public function poolDelete($poolid) {
        $poolid=vf($poolid,3);
        if (isset($this->pools[$poolid])) {
            $query="DELETE from `netextpools` WHERE `id`='".$poolid."'";
            nr_query($query);
            log_register("POOL DELETE [".$poolid."]");
            //delete associated ips
            $this->ipsDeleteByPool($poolid);
        } else {
            throw new Exception(self::EX_NOEXPOOL);
        }
    }
    
    
     /**
     * returns full list of associated IPs for all pools
     * 
     * @return void
     */
    protected function loadIps() {
        $query="SELECT * from `netextips` ORDER BY `id` ASC";
        $all=  simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
              $this->ips[$each['id']]=$each;
            }
        }
    }
    
    
    
    /**
     * returns full list of associated IPs for some pool
     * 
     * @param $poolid int existing pool ID
     * 
     * @return array
     */
    protected function ipsGetByPool($poolid) {
        $poolid=vf($poolid,3);
        $result=array();
        $query="SELECT * from `netextips` WHERE `poolid`='".$poolid."';";
        $all=  simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $result[$each['id']]=$each;
            }
        }
        return ($result);
    }
    
     /**
     * Deletes ips for some pool by ID
     * 
     * @param $poolid int existing pool ID
     * 
     * @return void
     */
    protected function ipsDeleteByPool($poolid) {
        $poolid=vf($poolid,3);
        $query="DELETE from `netextips` WHERE `poolid`='".$poolid."';";
        nr_query($query);
        log_register("POOL [".$poolid."] IPS DELETED");
    }
    
    /**
     * creates some ips range for newly created pool
     * 
     * @return void
     */
    protected function ipsCreate($poolid,$begin,$end) {
        $poolid=vf($poolid,3);
        $begin=  ip2long($begin);
        $end= ip2long($end);
            //valid ips ugly check
            if ($begin<=$end) {
                for ($i=$begin;$i<=$end;$i++) {
                    $newIp=  long2ip($i);
                    $query="INSERT INTO `netextips` "
                         . "(`id`, `poolid`, `ip`, `nas`, `iface`, `mac`, `switchid`, `port`, `vlan`) "
                         . "VALUES (NULL, '".$poolid."', '".$newIp."', NULL, NULL, NULL, NULL, NULL, NULL); ";
                 nr_query($query);
                }
               
            }
        
       log_register("POOL [".$poolid."] IPS CREATE RANGE `".long2ip($begin)."-".long2ip($end)."` ");
    }
    
    
    /**
     * returns raw list of ips associated with some pool
     * 
     * @param int $poolid Existing pool ID
     * 
     * @return string
     */
    protected function ipsGetAssociated($poolid) {        
        $poolid=vf($poolid,3);
        $tmpArr=array();
        $result='';
        if (!empty($this->pools)) {
            if (isset($this->pools[$poolid])) {
                if (!empty($this->ips)) {
                    foreach ($this->ips as $io=>$each) {
                        if ($each['poolid']==$poolid) {
                            $tmpArr[$each['ip']]=$each['ip'];
                        }
                    }
                }
            }
        }
        
        if (!empty($tmpArr)) {
            $result=  implode(', ', $tmpArr);
        }
        return ($result);
    }
    
    
    /**
     * Returns pool editing form
     * 
     * @param int $poolid
     * 
     * @return string
     */
    protected function poolEditForm($poolid) {
        $poolid=vf($poolid,3);
        $inputs=  wf_HiddenInput('editpoolid', $poolid);
        $inputs.= wf_HiddenInput('editpoolnetid', $this->pools[$poolid]['netid']);
        $inputs.= wf_TextInput('editpoollogin', __('Login'), $this->pools[$poolid]['login'], true, 10);
        $inputs.= wf_TextInput('editpoolvlan', __('VLAN'), $this->pools[$poolid]['vlan'], true, 5);
        $inputs.= wf_Submit(__('Save'));
       
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
                
        return ($result);
    }
    
    /**
     * Updates pool data into database
     * 
     * @param int $poolid $
     * @param int $vlan vlan id of the pool
     * @param string $login existing ubilling user login
     * 
     * @return void
     */
    public function poolEdit($poolid,$vlan,$login) {
        $poolid=vf($poolid,3);
        $vlan=vf($vlan,3);
        $login=trim($login);
        if (isset($this->pools[$poolid])) {
          simple_update_field('netextpools', 'vlan', $vlan, "WHERE `id`='".$poolid."';");
          simple_update_field('netextpools', 'login', $login, "WHERE `id`='".$poolid."';");
          log_register("POOL EDIT [".$poolid."] VLAN `".$vlan."` LOGIN `".$login."`");
          
        } else {
             throw new Exception(self::EX_NOEXPOOL);
        }
    }
    
    /**
     * renders control links for pools associated with some login
     * 
     * @param string $login Existing ubilling user login
     * 
     * @return string
     */
    public function poolsExtractByLogin($login) {
        $login=  mysql_real_escape_string($login);
        $result='';
        $tmpArr=array();
        if (!empty($this->pools)) {
            foreach ($this->pools as $io=>$each) {
                if ($each['login']==$login) {
                    $tmpArr[$each['id']]=$each['pool'].'/'.$each['netmask'];
                }
            }
            
            if (!empty($tmpArr)) {
                $result.=' + ';
                foreach ($tmpArr as $poolid=>$pool) {
                    $result.=wf_Link('?module=extnets&showipsbypoolid='.$poolid, $pool, false, '');
                }
            }
        }
        return ($result);
    }
    
    /**
     * loads available switches array into private switches property
     * 
     * @return void
     */
    protected function loadSwitches() {
        $query="SELECT * from `switches`";
        $all=  simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $this->switches[$each['id']]=$each['ip'].' - '.$each['location'];
            }
        }
    }
    
    /**
     * returns IP editin control 
     * 
     * @param int $ipid Existing IP database ID
     * 
     * @return string
     */
    protected function ipsEditForm($ipid) {
        $ipid=vf($ipid,3);
        $switchesSelector=array();
        $result='';
        if (isset($this->ips[$ipid])) {
            if (empty($this->switches)) {
                $this->loadSwitches();
            }
            $switchesSelector=$this->switches;
            $switchesSelector['NULL']='-';
            natsort($switchesSelector);

            $inputs=  wf_HiddenInput('editipid', $ipid);
            $inputs.= wf_TextInput('editipnas', __('NAS'), $this->ips[$ipid]['nas'], true, 15);
            $inputs.= wf_TextInput('editipiface', __('Interface'), $this->ips[$ipid]['iface'], true, 15);
            $inputs.= wf_TextInput('editipmac', __('MAC'), $this->ips[$ipid]['mac'], true, 15);
            $inputs.= wf_Selector('editipswitchid', $switchesSelector, __('Switch'), $this->ips[$ipid]['switchid'], true);
            $inputs.= wf_TextInput('editipport', __('Port'), $this->ips[$ipid]['port'], true,5);
            $inputs.= wf_Submit(__('Save'));
            
            $result=  wf_Form("", 'POST', $inputs, 'glamour');
            
        } else {
            throw new Exception(self::EX_NOEXIP);
        }
        return ($result);
    }
    
    /**
     * edits some ip in database
     * 
     * @param 
     * 
     * @return void
     */
    public function ipsEdit($ipid,$nas,$iface,$mac,$switchid,$port) {
        simple_update_field('netextips', 'nas', $nas, "WHERE `id`='".$ipid."'");
        simple_update_field('netextips', 'iface', $iface, "WHERE `id`='".$ipid."'");
        simple_update_field('netextips', 'mac', $mac, "WHERE `id`='".$ipid."'");
        simple_update_field('netextips', 'switchid', $switchid, "WHERE `id`='".$ipid."'");
        simple_update_field('netextips', 'port', $port, "WHERE `id`='".$ipid."'");
        log_register("POOL IP [".$ipid."] EDIT `".$this->ips[$ipid]['ip']."`");
    }

    /**
     * Renders ips associated with some poolid
     * 
     * @param int $poolid Existing pool ID
     * 
     * @return string
     */
    public function renderIps($poolid) {
        $poolid=vf($poolid,3);
        $result='';
        if (empty($this->switches)) {
            $this->loadSwitches();
        }
        if (isset($this->pools[$poolid])) {
            if (!empty($this->ips)) {
                $cells=  wf_TableCell(__('ID'));
                $cells.=  wf_TableCell(__('IP'));
                $cells.=  wf_TableCell(__('Gateway'));
                $cells.=  wf_TableCell(__('Netmask'));
                $cells.=  wf_TableCell(__('NAS'));
                $cells.=  wf_TableCell(__('Interface'));
                $cells.=  wf_TableCell(__('MAC'));
                $cells.=  wf_TableCell(__('Switch'));
                $cells.=  wf_TableCell(__('Port'));
                $cells.=  wf_TableCell(__('VLAN'));
                $cells.=  wf_TableCell(__('Actions'));
                $rows= wf_TableRow($cells, 'row1');
                
                foreach ($this->ips as $io=>$eachip) {
                   if ($eachip['poolid']==$poolid) {
                    $cells=  wf_TableCell($eachip['id']);
                    $cells.=  wf_TableCell($eachip['ip']);
                    $cells.=  wf_TableCell($this->pools[$poolid]['gw']);
                    $cells.=  wf_TableCell($this->cidrToMask[$this->pools[$poolid]['netmask']]);
                    $cells.=  wf_TableCell($eachip['nas']);
                    $cells.=  wf_TableCell($eachip['iface']);
                    $cells.=  wf_TableCell($eachip['mac']);
                    $cells.=  wf_TableCell(@$this->switches[$eachip['switchid']]);
                    $cells.=  wf_TableCell($eachip['port']);
                    $cells.=  wf_TableCell($this->pools[$poolid]['vlan']);
                    $actionsLink= wf_modal(web_edit_icon(), __('Edit').' '.$eachip['ip'], $this->ipsEditForm($eachip['id']), '', '400', '300');
                    $cells.=  wf_TableCell($actionsLink);
                    $rows.= wf_TableRow($cells, 'row3');
                   }
                }
             $result=  wf_TableBody($rows, '100%', '0', 'sortable');   
             //back links controls
             if (!empty($this->pools[$poolid]['login'])) {
              $result.= wf_Link("?module=userprofile&username=".$this->pools[$poolid]['login'], web_profile_icon().' '.__('Back to user profile'), false, 'ubButton');
              
             }
             
             $result.= wf_BackLink('?module=extnets&showpoolbynetid='.$this->pools[$poolid]['netid'], __('Back').' '.$this->pools[$poolid]['pool'].'/'.$this->pools[$poolid]['netmask'], true);
             
            }
            
        } else {
            throw new Exception(self::EX_NOEXPOOL);
        }
        return ($result);
    }
    
    /**
     * returns user attach pool control
     * 
     * @param string $login existing ubilling user login
     * 
     * @return string
     */
    public function poolLinkingForm($login) {
        $poolsArr=array();
        if (!empty($this->pools)) {
            foreach ($this->pools as $io=>$each) {
                if (empty($each['login'])) {
                    $poolsArr[$each['id']]=$each['pool'].'/'.$each['netmask'];
                }
            }
        }
        $inputs=  wf_Selector('extnetspoollinkid', $poolsArr, __('IP associated with pool'), '', false);
        $inputs.= wf_HiddenInput('extnetspoollinklogin', $login);
        $inputs.= wf_Submit(__('Save'));
                
        $result= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }
    
    /**
     * changes pool login
     * 
     * @param int $poolid Existing poolID
     * @param string $login Existin ubilling user login
     * 
     * @return void
     */
    public function poolLinkLogin($poolid,$login) {
        $poolid=vf($poolid,3);
        $login=trim($login);
        if (isset($this->pools[$poolid])) {
            simple_update_field('netextpools', 'login', $login, "WHERE `id`='".$poolid."'");
            log_register("POOL LINK USER `".$login."`");
        } else {
            throw new Exception(self::EX_NOEXPOOL);
        }
    }
    
    /**
     * returns data for docx templatizer for login and associated pools
     * 
     * @param string $login Existing ubilling user login
     * 
     * @return string
     */
    public function poolTemplateData($login) {
        $result='';
        $poolArr=array();
        if (!empty($this->pools)) {
            foreach ($this->pools as $io=>$each) {
                if ($each['login']==$login) {
                    $poolArr[$each['id']]['gw']=$each['gw'];
                    $poolArr[$each['id']]['netmask']=$this->cidrToMask[$each['netmask']];
                    $poolArr[$each['id']]['ips']=$this->ipsGetAssociated($each['id']);
                    
                }
            }
            
            
            if (!empty($poolArr)) {
                foreach ($poolArr as $ia=>$eachpool) {
                    $result.=__('Gateway').': '.$eachpool['gw'].' '.__('Netmask').': '.$eachpool['netmask'].' '.__('IP').': '.$eachpool['ips'].' ';
                }
            }
        }
        return ($result);
    }
}


?>