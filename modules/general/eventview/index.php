<?php
if(cfr('EVENTVIEW')) {
    function zb_GetAllEvents($limit=0) {
        $limit=vf($limit,3);
        $query="SELECT * from `weblogs` ORDER BY ID DESC LIMIT ".$limit;
        $allevents=simple_queryall($query);
        return ($allevents);
    }

    function zb_GetAllEventsByDate($date) {
         $date=mysql_real_escape_string($date);
         $query="SELECT * from `weblogs` WHERE `date` LIKE '%".$date."%'";
         $allevents=simple_queryall($query);
        return ($allevents);
    }
    
      function zb_GetAllEventsByPattern($searchpattern) {
         $query="SELECT * from `weblogs` WHERE `event` LIKE '%".$searchpattern."%'";
         $allevents=simple_queryall($query);
        return ($allevents);
    }

    
function web_EventsShowStats() {
$cmonth=date("Y-m-");
$cday=date("d");
$reg_q="SELECT COUNT(`id`) from `userreg` WHERE `date` LIKE '".$cmonth."%'";
$regc=simple_query($reg_q);
$regc=$regc['COUNT(`id`)'];
$mac_q="SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '".$cmonth."%' AND `event` LIKE 'CHANGE MultiNetHostMac%'";
$macc=simple_query($mac_q);
$macc=$macc['COUNT(`id`)'];
$events_q="SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '".$cmonth."%'";
$eventsc=simple_query($events_q);
$eventsc=$eventsc['COUNT(`id`)'];
$switch_q="SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '".$cmonth."%' AND `event` LIKE 'SWITCH ADD%'";
$switchc=simple_query($switch_q);
$switchc=$switchc['COUNT(`id`)'];
$credit_q="SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '".$cmonth."%' AND `event` LIKE 'CHANGE Credit%'";
$creditc=simple_query($credit_q);
$creditc=$creditc['COUNT(`id`)'];
$pay_q="SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '".$cmonth."%' AND `summ`>0";
$payc=simple_query($pay_q);
$payc=$payc['COUNT(`id`)'];
$tarch_q="SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '".$cmonth."%' AND `event` LIKE 'CHANGE TariffNM%'";
$tarchc=simple_query($tarch_q);
$tarchc=$tarchc['COUNT(`id`)'];
$stg_q="SELECT COUNT(`unid`) from `logs_".date("m")."_".date("Y")."`";
$stgc=simple_query($stg_q);
$stgc=$stgc['COUNT(`unid`)'];
// workdays fix
$weeks=($cday/7);
$weeks=intval($weeks);

if ($weeks>=1) {
    $cday=$cday-(2*$weeks);
}

$tablecells=wf_TableCell(__('What done').'?');
$tablecells.=wf_TableCell(__('Current month'));
$tablecells.=wf_TableCell(__('Average per day'));
$tablerows=wf_TableRow($tablecells, 'row1');

$tablecells=wf_TableCell(__('Current month signups'));
$tablecells.=wf_TableCell($regc);
$tablecells.=wf_TableCell(round($regc/$cday,2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('MAC changes'));
$tablecells.=wf_TableCell(($macc-$regc));
$tablecells.=wf_TableCell(round( (($macc-$regc)/$cday) ,2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('Switches added'));
$tablecells.=wf_TableCell(($switchc));
$tablecells.=wf_TableCell(round( ($switchc/$cday),2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('Credits set'));
$tablecells.=wf_TableCell($creditc);
$tablecells.=wf_TableCell(round( ($creditc/$cday) ,2));
$tablerows.=wf_TableRow($tablecells, 'row3');


$tablecells=wf_TableCell(__('Payments processed'));
$tablecells.=wf_TableCell($payc);
$tablecells.=wf_TableCell(round( ($payc/$cday),2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('Planned changes to tariffs'));
$tablecells.=wf_TableCell($tarchc);
$tablecells.=wf_TableCell(round( ($tarchc/$cday),2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('External billing events'));
$tablecells.=wf_TableCell($eventsc);
$tablecells.=wf_TableCell( round( ($eventsc/$cday) ,2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$tablecells=wf_TableCell(__('Internal billing events'));
$tablecells.=wf_TableCell($stgc);
$tablecells.=wf_TableCell(round( ($stgc/$cday),2));
$tablerows.=wf_TableRow($tablecells, 'row3');

$template=wf_TableBody($tablerows, '50%', '0');
show_window(__('Month actions stats'),$template);
}



    function web_EventsLister($limit,$searchevent='') {
      if (!isset($_POST['eventdate'])) {
      $allevents=zb_GetAllEvents($limit);
      } else {
      $allevents=zb_GetAllEventsByDate($_POST['eventdate']);
      }
      
      if ($searchevent!='') {
       $allevents=zb_GetAllEventsByPattern($searchevent);
      }
       
      $result='
          '.__('On page').':
          '.  wf_Link('?module=eventview&onpage=50', '50', false ).'
          '.  wf_Link('?module=eventview&onpage=100', '100', false).'
          '.  wf_Link('?module=eventview&onpage=200', '200', false).'
          '.  wf_Link('?module=eventview&onpage=500', '500', false).'
          '.  wf_Link('?module=eventview&onpage=800', '800', true).'
          <br>';
      
      $dateinputs=__('By date') .': ';
      $dateinputs.=web_CalendarControl('eventdate');
      $dateinputs.=wf_Submit(__('Show'));
      $dateform=wf_Form('', 'POST', $dateinputs, 'glamour');
      
      
      $eventsearchinputs=wf_TextInput('eventsearch', 'Event', '', false, '30');
      $eventsearchinputs.=wf_Submit('Find');
      $eventsearchform=wf_Form('', 'POST', $eventsearchinputs, 'glamour');
      
      
      $searchcells=wf_TableCell($dateform);
      $searchcells.=wf_TableCell($eventsearchform);
      $searchrow=wf_TableRow($searchcells);
      $searchtable=wf_TableBody($searchrow, '100%', '0');
      $result.=$searchtable;
      
      $tablecells=wf_TableCell(__('ID'));
      $tablecells.=wf_TableCell(__('Date'));
      $tablecells.=wf_TableCell(__('Admin'));
      $tablecells.=wf_TableCell(__('IP'));
      $tablecells.=wf_TableCell(__('Event'));
      $tablerows=wf_TableRow($tablecells, 'row1');

      if (!empty ($allevents)) {
          foreach ($allevents as $io=>$eachevent) {
          $event=$eachevent['event'];
          $tablecells=wf_TableCell($eachevent['id']);
          $tablecells.=wf_TableCell($eachevent['date']);
          $tablecells.=wf_TableCell($eachevent['admin']);
          $tablecells.=wf_TableCell($eachevent['ip']);
          $tablecells.=wf_TableCell($event);
          $tablerows.=wf_TableRow($tablecells, 'row3');
          }

      }
      $result.=wf_TableBody($tablerows, '100%', 0, 'sortable');
      return($result);
    }

    //page lister
    if (isset($_GET['onpage'])) {
        $limit=$_GET['onpage'];
    } else {
        $limit=50;
    }
    
    //event search
    if (isset ($_POST['eventsearch'])) {
        if (strlen($_POST['eventsearch'])>=3) {
            $searchevent=mysql_real_escape_string($_POST['eventsearch']);
        } else {
            $searchevent='';
        }
    } else {
        $searchevent='';
    }
    
    web_EventsShowStats();
    show_window(__('Last events'),web_EventsLister($limit,$searchevent));
     
}
?>
