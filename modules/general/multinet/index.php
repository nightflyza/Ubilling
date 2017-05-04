<?php
      if(cfr('MULTINET')) {
      $altcfg=  rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
          
      //adding new network
      if (isset($_POST['addnet'])) {
          $netadd_req=array('firstip','lastip','desc');
          if (wf_CheckPost($netadd_req)) {
              $desc=$_POST['desc'];
              $firstip=$_POST['firstip'];
              $lastip=$_POST['lastip'];
              $nettype=$_POST['nettypesel'];
              if ($altcfg['FREERADIUS_ENABLED']) {
                $use_radius=$_POST['use_radius'];
              } else {
                $use_radius=0;
              }
              multinet_add_network($desc, $firstip, $lastip, $nettype, $use_radius);
              rcms_redirect('?module=multinet');
          } else {
               show_window(__('Error'), __('No all of required fields is filled'));
          }
          
      }
      
      //deleting network
      if (isset($_GET['deletenet'])) {
          
          $network_id=$_GET['deletenet'];
          
          // check have this network any users inside?
          if (!multinet_network_is_used($network_id)) {
              multinet_delete_network($network_id);
              rcms_redirect('?module=multinet');
          } else {
              //if here users - go back
              show_error(__('The network that you are trying to remove - contains live users. We can not afford to do so with them.'));
              show_window('', wf_BackLink('?module=multinet', 'Back', true));
          }
          
      }

      //service adding
      if (isset ($_POST['serviceadd'])) {
       $servadd_req=array('networkselect','servicename');
       if (wf_CheckPost($servadd_req)) {
        $net=$_POST['networkselect'];
        $desc=$_POST['servicename'];
        multinet_add_service($net, $desc);
        rcms_redirect('?module=multinet');
        } else {
          show_window(__('Error'), __('No all of required fields is filled'));
       }
      } 
      
      //service deletion
      if (isset ($_GET['deleteservice'])) {
          $service_id=$_GET['deleteservice'];
          multinet_delete_service($service_id);
          rcms_redirect('?module=multinet');
      }
      
      //network and services display
      if ((!isset($_GET['editnet'])) AND (!isset($_GET['editservice'])))  {
      multinet_show_available_networks();
      multinet_show_networks_form();

      multinet_show_available_services();
      multinet_show_service_add_form();
      multinet_rebuild_all_handlers();
      } else {
          // editing network
          if (isset($_GET['editnet'])) {
              $editnet=vf($_GET['editnet']);
              if (isset($_POST['netedit'])) {
                  
                  $neted_req=array('editstartip','editendip','editdesc');
                  if (wf_CheckPost($neted_req)) {
                  simple_update_field('networks', 'startip', $_POST['editstartip'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'endip', $_POST['editendip'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'desc', $_POST['editdesc'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'nettype', $_POST['nettypesel'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'use_radius', $_POST['edituse_radius'], "WHERE `id`='".$editnet."'");
                  log_register('MODIFY MultiNetNet ['.$editnet.']');
                  rcms_redirect("?module=multinet"); 
                  } else {
                      show_window(__('Error'), __('No all of required fields is filled'));
                  }
              }
              multinet_show_neteditform($editnet);
          }
          
          //editing service
          if (isset($_GET['editservice'])) {
              $editservice=$_GET['editservice'];
              if (isset($_POST['serviceedit'])) {
                 $served_req=array('editservicename');
                 if (wf_CheckPost($served_req)) {
                 simple_update_field('services', 'desc', $_POST['editservicename'], "WHERE `id`='".$editservice."'");
                 simple_update_field('services', 'netid', $_POST['networkselect'], "WHERE `id`='".$editservice."'");
                 log_register('MODIFY MultiNetService ['.$editservice.']');
                 rcms_redirect("?module=multinet");
                 } else {
                      show_window(__('Error'), __('No all of required fields is filled'));
                 }
              }
              multinet_show_serviceeditform($editservice);
          }
      }
       
      }
      else {
              show_error(__('Access denied'));
      }
 ?>
