<?php

class ExtNets {
    
    private $networks=array();
    private $pools=array();
    private $masklimits=array('upper'=>30,'lower'=>24);
    private $cidrs=array();
    private $cidrToMask=array();
    private $cidrOffsets=array();
                        
    
    
    const EX_NOEXNET='NOT_EXISTING_NET_ID';
    const EX_NOEXPOOL='NOT_EXISTING_POOL_ID';
    
    public function __construct() {
        $this->preprocessCidrMasks();
        $this->loadNetworks();
        $this->loadPools();
        
    }
    
    
    /*
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
    
    /*
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




    /*
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
    
    /*
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
    
    /*
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
    
    /*
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
    
    /*
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
                $cells.= wf_TableCell($each['clientip']);
                $cells.= wf_TableCell($each['broadcast']);
                $cells.= wf_TableCell($each['vlan']);
                if (!empty($each['login'])) {
                    $loginlink=  wf_Link('?module=userprofile&username='.$each['login'], web_profile_icon().' '.$each['login'], 'fasle');
                } else {
                    $loginlink='';
                }
                $cells.= wf_TableCell($loginlink);
                $actlinks=  wf_JSAlert('?module=extnets&showpoolbynetid='.$netid.'&deletepoolid='.$each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $cells.= wf_TableCell($actlinks);
                $rows.=  wf_TableRow($cells, 'row3');
            }
            
            $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        }
        
        return ($result);
    }
    
    
    /*
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
                        $curNetPools[$each['id']]=ip2int($each['broadcast'])+1;
                    }
                } 
            } else {
                 //network start IP
                $curNetPools[0]=ip2int($this->networks[$netid]['startip']);
            }
            
            if (empty($curNetPools)) {
                //network start IP
                $curNetPools[0]=ip2int($this->networks[$netid]['startip']);
            }
            $result=max($curNetPools);
            $result=  int2ip($result);
        }
        return ($result);
    }
    
    /*
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
    
    /*
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
        $newGw=  int2ip(ip2int($pool)+1);
        $newIp=  int2ip(ip2int($pool)+2);
        $newBroadcast=int2ip(ip2int($pool)+($this->cidrOffsets[$netmask]-1));
        simple_update_field('netextpools', 'gw', $newGw, "WHERE `id`='".$newPoolId."';");
        simple_update_field('netextpools', 'clientip', $newIp, "WHERE `id`='".$newPoolId."';");
        simple_update_field('netextpools', 'broadcast', $newBroadcast, "WHERE `id`='".$newPoolId."';");
    }
    
    /*
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
        } else {
            throw new Exception(self::EX_NOEXPOOL);
        }
    }
    
}


?>