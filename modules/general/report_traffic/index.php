<?php
if (cfr('REPORTTRAFFIC')) {
    
    function web_TstatsShow() {
        $allclasses=zb_DirectionsGetAll();
        $classtraff=array();
        $result='<table width="100%" border="0" class="sortable">';
        $result.='      <tr class="row1">
                        <td width="20%">'.__('Traffic classes').'</td>
                        <td width="20%">'.__('Traffic').'</td>
                        <td>'.__('Visual').'</td>
                        </tr>
                        ';
        if (!empty ($allclasses)) {
            foreach ($allclasses as $io=>$eachclass) {
                $d_name='D'.$eachclass['rulenumber'];
                $u_name='U'.$eachclass['rulenumber'];
                $query_d="SELECT SUM(`".$d_name."`) from `users`";
                $query_u="SELECT SUM(`".$u_name."`) from `users`";
                $classdown=simple_query($query_d);
                $classdown=$classdown['SUM(`'.$d_name.'`)'];
                $classup=simple_query($query_u);
                $classup=$classup['SUM(`'.$u_name.'`)'];
                $classtraff[$eachclass['rulename']]=$classdown+$classup;
            }
            
            if (!empty ($classtraff)) {
                $total=max($classtraff);
                foreach ($classtraff as $eachname=>$count) {
                    $result.='
                        <tr class="row3">
                        <td>'.$eachname.'</td>
                        <td sorttable_customkey="'.$count.'">'.stg_convert_size($count).'</td>
                        <td sorttable_customkey="'.$count.'">'.web_bar($count, $total).'</td>
                        </tr>
                        ';
                }
                
            }
            
            
        }
        $result.='</table>';
        show_window(__('Traffic report'), $result);
     }
    
    function web_TstatsNas() {
        $query="SELECT * from `nas` WHERE `bandw`!='' GROUP by `bandw`";
        $allnas=simple_queryall($query);
        if (!empty ($allnas)) {
            $result='<table width="100%" border="0">';
            foreach ($allnas as $io=>$eachnas){
                $bwd=$eachnas['bandw'];
                $d_day=$bwd.'Total-1-R.png';
                $d_week=$bwd.'Total-2-R.png';
                $d_month=$bwd.'Total-3-R.png';
                $d_year=$bwd.'Total-4-R.png';
                $u_day=$bwd.'Total-1-S.png';
                $u_week=$bwd.'Total-2-S.png';
                $u_month=$bwd.'Total-3-S.png';
                $u_year=$bwd.'Total-4-S.png';
// old overlay style
//                $gday=web_Overlay(__('Graph by day'), __('Downloaded').'<br><img src="'.$d_day.'"><br>'.__('Uploaded').'<br><img src="'.$u_day.'">','0.90');
//                $gweek=web_Overlay(__('Graph by week'), __('Downloaded').'<br><img src="'.$d_week.'"><br>'.__('Uploaded').'<br><img src="'.$u_week.'">','0.90');
//                $gmonth=web_Overlay(__('Graph by month'), __('Downloaded').'<br><img src="'.$d_month.'"><br>'.__('Uploaded').'<br><img src="'.$u_month.'">','0.90');
//                $gyear=web_Overlay(__('Graph by year'), __('Downloaded').'<br><img src="'.$d_year.'"><br>'.__('Uploaded').'<br><img src="'.$u_year.'">','0.90');
                // jq modal dialog
                $daygraph=  wf_img($d_day).'<br>'.__('Uploaded').'<br>'.  wf_img($u_day);
                $weekgraph=  wf_img($d_week).'<br>'.__('Uploaded').'<br>'.  wf_img($u_week);
                $monthgraph=  wf_img($d_month).'<br>'.__('Uploaded').'<br>'.  wf_img($u_month);
                $yeargraph=  wf_img($d_year).'<br>'.__('Uploaded').'<br>'.  wf_img($u_year);
                
                $gday=   wf_modal(__('Graph by day'), __('Graph by day'), $daygraph, '', 920, 600);
                $gweek=  wf_modal(__('Graph by week'), __('Graph by week'), $weekgraph, '', 920, 600);
                $gmonth= wf_modal(__('Graph by month'), __('Graph by month'), $monthgraph, '', 920, 600);
                $gyear=  wf_modal(__('Graph by year'), __('Graph by year'), $yeargraph, '', 920, 600);
                
                $result.='
                    <tr class="row3">
                    <td class="row2">'.$eachnas['nasname'].'</td>
                    <td>'.$gday.'</td>
                    <td>'.$gweek.'</td>
                    <td>'.$gmonth.'</td>
                    <td>'.$gyear.'</td>
                    </tr>
                    ';
            }
            $result.='</table>';
            show_window(__('Network Access Servers'), $result);
        }
    }
     
    
    web_TstatsShow();
    web_TstatsNas();
    
	
} else {
      show_error(__('You cant control this module'));
}

?>
