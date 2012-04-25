<?php
if (cfr('PLDETAILS')) {
    
  
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        $userdata=zb_UserGetStargazerData($login);
        $cyear=curyear();
        $cmonth=date("m");
        $tablename='detailstat_'.$cmonth.'_'.$cyear.'';
        
        function ds_CheckTable() {
            global $tablename;
            $query="SELECT CASE WHEN (SELECT COUNT(*) AS STATUS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME = '".$tablename."') = 1 THEN (SELECT 1)  ELSE (SELECT 0) END AS result;";
            $result=simple_query($query);
            return ($result['result']);
        }
        
        
        function ds_GetDays() {
            global $tablename;
            $query="SELECT DISTINCT `day` from `".$tablename."`";
            $alldays=simple_queryall($query);
            return($alldays);
        }
        
        function ds_GetDayStats($login,$day,$page=0) {
            global $tablename;
            $pagelimit=100;
            $page=vf($page);
            $login=mysql_real_escape_string($login);
            $dey=vf($day);
            $query="SELECT * from `".$tablename."` WHERE `login`='".$login."' AND `day`='".$day."' ORDER by `starttime` DESC";
            $daystats=simple_queryall($query);
            return($daystats);
        }
            
             
        function ds_GetDownSumm($login,$day) {
            global $tablename;
            $login=vf($login);
            $day=vf($day);
            $query="SELECT SUM(`down`) from `".$tablename."` WHERE `login`='".$login."'  AND `day`='".$day."'";
            $summ=simple_query($query);
            return($summ['SUM(`down`)']);
        }
        
         function ds_GetUpSumm($login,$day) {
            global $tablename;
            $login=vf($login);
            $day=vf($day);
            $query="SELECT SUM(`up`) from `".$tablename."` WHERE `login`='".$login."'  AND `day`='".$day."'";
            $summ=simple_query($query);
            return($summ['SUM(`up`)']);
        }
        
          function ds_GetCashSumm($login,$day) {
            global $tablename;
            $login=vf($login);
            $day=vf($day);
            $query="SELECT SUM(`cash`) from `".$tablename."` WHERE `login`='".$login."'  AND `day`='".$day."'";
            $summ=simple_query($query);
            return($summ['SUM(`cash`)']);
            }
        
        function web_DSShow($login) {
     
            $login=vf($login);
            $days=ds_GetDays();
            $result='<table width="100%" class="sortable">';
              $result.='
                       <tr class="row1">
                       <td>'.__('Day').'</td>
                       <td>'.__('Downloaded').'</td>
                       <td>'.__('Uploaded').'</td>
                       <td>'.__('Cash').'</td>
                       </tr>
                       ';
            if (!empty ($days)) {
                foreach ($days as $io=>$eachday) {
                   $downsumm=ds_GetDownSumm($login, $eachday['day']);
                   $upsumm=ds_GetUpSumm($login, $eachday['day']);
                   $cashsumm=ds_GetCashSumm($login, $eachday['day']);
                   if (!$downsumm)  {
                       $downsumm=0;
                   }
                   if(!$upsumm) {
                       $upsumm=0;
                   }
                   $result.='
                       <tr class="row3">
                       <td><a href="?module=pl_traffdetails&username='.$login.'&day='.$eachday['day'].'">'.$eachday['day'].'</a></td>
                       <td sorttable_customkey="'.$downsumm.'">'.stg_convert_size($downsumm).'</td>
                       <td sorttable_customkey="'.$upsumm.'">'.stg_convert_size($upsumm).'</td>
                       <td>'.round($cashsumm,2).'</td>
                       </tr>
                       ';
                }
            }
            $result.='</table>';
            
           return($result);
        }
        
        function web_DSShowDayStats($login,$day,$page=0) {
            $traffclasse_raw=zb_DirectionsGetAll();
            $tc=array();
            if (!empty ($traffclasse_raw)) {
                foreach ($traffclasse_raw as $io=>$eachtc) {
                   $tc[$eachtc['rulenumber']]=$eachtc['rulename'];
                }
            }
            $login=mysql_real_escape_string($login);
            $page=vf($page);
            $day=vf($day);
            $daystats=ds_GetDayStats($login, $day, $page);
            $result='<table width="100%" border="0" class="sortable">';
            $result.='
                        <tr class="row1">
                        <td>'.__('Session start').'</td>
                        <td>'.__('Session end').'</td>
                        <td>'.__('IP').'</td>
                        <td>'.__('Traffic classes').'</td>
                        <td>'.__('Downloaded').'/'.__('Uploaded').'</td>
                        <td>'.__('Cash').'</td>
                        </tr>
                        ';
            if (!empty ($daystats)) {
                foreach ($daystats as $io=>$eachtraff) {
                    $result.='
                        <tr class="row3">
                        <td>'.$eachtraff['startTime'].'</td>
                        <td>'.$eachtraff['endTime'].'</td>
                        <td><a href="https://apps.db.ripe.net/dbweb/search/query.html?searchtext='.$eachtraff['IP'].'" target="_BLANK">
                       <img src="skins/icon_whois_small.jpg"></a>
                       <a href="http://'.$eachtraff['IP'].'">'.$eachtraff['IP'].'</td>
                        <td>'.@$tc[$eachtraff['dir']].'</td>
                        <td sorttable_customkey="'.($eachtraff['down']+$eachtraff['up']).'">'.stg_convert_size($eachtraff['down']).' / '.stg_convert_size($eachtraff['up']).'</td>
                        <td>'.round($eachtraff['cash'],3).'</td>
                        </tr>
                        ';
                }
            }
            $result.='</table>';
            return ($result);
        }
        
        if (ds_CheckTable()) {
        show_window(__('Traffic detailed stats'),web_DSShow($login));
        } else {
                show_window(__('Error'),__('No detailstats database exists'));
            }
            
        if (isset ($_GET['day'])) {
            $day=$_GET['day'];
            show_window(__('Detailed stats by day'),web_DSShowDayStats($login, $day));
        }
        
        show_window('',  web_UserControls($login));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
