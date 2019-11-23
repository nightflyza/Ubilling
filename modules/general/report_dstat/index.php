<?php
if (cfr('REPORTDSTAT')) {

   class ReportDstat {
        private $data =array();
        
        
        public function __construct() {
            //load actual data by users with enabled detailed stats
            $this->loadData();
        }
        
        /*
         * get all users with  with enabled detailed stats and push it into private data prop
         * 
         * @return void
         */
        private function loadData() {
            $query="SELECT `login`,`Tariff`,`IP`, (`D0`+`D1`+`D2`+`D3`+`D4`+`D5`+`D6`+`D7`+`D8`+`D9`+`U0`+`U1`+`U2`+`U3`+`U4`+`U5`+`U6`+`U7`+`U8`+`U9`) AS `traffic` from `users` WHERE `DisabledDetailStat`!='1'";
            $alldata=  simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io=>$each) {
                    $this->data[$each['login']]['login']=$each['login'];
                    $this->data[$each['login']]['Tariff']=$each['Tariff'];
                    $this->data[$each['login']]['IP']=$each['IP'];
                    $this->data[$each['login']]['traffic']=$each['traffic'];
                }
            }
        }
        
         /*
         * returns private propert data
         * 
         * @return array
         */
        public function getData () {
            $result=  $this->data;
            return ($result);
        }
        
         /*
         * renders report by existing private data prop
         * 
         * @return string
         */
        public function render() {
           $cells=   wf_TableCell(__('Login'));
           $cells.=  wf_TableCell(__('Address'));
           $cells.=  wf_TableCell(__('Real Name'));
           $cells.=  wf_TableCell(__('IP'));
           $cells.=  wf_TableCell(__('Tariff'));
           $cells.=  wf_TableCell(__('Traffic'));
           $cells.=  wf_TableCell(__('Actions'));
           $rows=  wf_TableRow($cells, 'row1');
           
           if (!empty($this->data)) {
                $allrealnames=zb_UserGetAllRealnames();
                $alladdress=zb_AddressGetFulladdresslist();
                foreach ($this->data as $io=>$each) {
                   $loginLink=  wf_Link("?module=userprofile&username=".$each['login'], web_profile_icon().' '.$each['login'], false, '');
                   $cells=   wf_TableCell($loginLink);
                   $cells.=  wf_TableCell(empty($alladdress[$each['login']]) ? '' : $alladdress[$each['login']]);
                   $cells.=  wf_TableCell(empty($allrealnames[$each['login']]) ? '' : $allrealnames[$each['login']]);
                   $cells.=  wf_TableCell($each['IP']);
                   $cells.=  wf_TableCell($each['Tariff']);
                   $cells.=  wf_TableCell(stg_convert_size($each['traffic']), '', '', 'sorttable_customkey="'.$each['traffic'].'"');
                   $actionLinks=  wf_Link('?module=pl_traffdetails&username='.$each['login'], wf_img('skins/icon_stats.gif', __('Detailed stats')), false, '');
                   $actionLinks.= wf_link('?module=dstatedit&username='.$each['login'], web_edit_icon(), false, '');
                   $cells.=  wf_TableCell($actionLinks);
                   $rows.=  wf_TableRow($cells, 'row3');
                }
           }
           $result= wf_TableBody($rows, '100%', '0', 'sortable');
           return ($result);
        }
        
        
   }
   
    
   /*
    * controller and view section
    */
   
   $dstatReport = new ReportDstat();
   show_window(__('Users for which detailed statistics enabled'), $dstatReport->render());
   

} else {
      show_error(__('You cant control this module'));
}

?>
