<?php
      if(cfr('MULTINET')) {
      if (isset($_POST['addnet'])) {
          $desc=$_POST['desc'];
          $firstip=$_POST['firstip'];
          $lastip=$_POST['lastip'];
          $nettype=$_POST['nettypesel'];
          multinet_add_network($desc, $firstip, $lastip, $nettype);
          rcms_redirect('?module=multinet');
      }
      if (isset($_POST['deletenet'])) {
          $network_id=$_POST['networkselect'];
          multinet_delete_network($network_id);
          rcms_redirect('?module=multinet');
      }

      if (isset ($_POST['serviceadd'])) {
      $net=$_POST['networkselect'];
      $desc=$_POST['sevicename'];
      multinet_add_service($net, $desc);
      rcms_redirect('?module=multinet');
      }
      if (isset ($_POST['servicedelete'])) {
          $service_id=$_POST['serviceselect'];
          multinet_delete_service($service_id);
          rcms_redirect('?module=multinet');
      }
      
      if ((!isset($_GET['editnet'])) AND (!isset($_GET['editservice'])))  {
      multinet_show_available_networks();
      multinet_show_network_delete_form();
      multinet_show_networks_form();
      multinet_show_available_services();
      multinet_show_service_delete_form();
      multinet_show_service_add_form();
      multinet_rebuild_all_handlers();
      } else {
          // editing network
          if (isset($_GET['editnet'])) {
              $editnet=vf($_GET['editnet']);
              if (isset($_POST['netedit'])) {
                  simple_update_field('networks', 'startip', $_POST['editstartip'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'endip', $_POST['editendip'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'desc', $_POST['editdesc'], "WHERE `id`='".$editnet."'");
                  simple_update_field('networks', 'nettype', $_POST['nettypesel'], "WHERE `id`='".$editnet."'");
                  rcms_redirect("?module=multinet");
              }
              multinet_show_neteditform($editnet);
          }
          
          if (isset($_GET['editservice'])) {
              $editservice=$_GET['editservice'];
              if (isset($_POST['serviceedit'])) {
                 simple_update_field('services', 'desc', $_POST['editservicename'], "WHERE `id`='".$editservice."'");
                 simple_update_field('services', 'netid', $_POST['networkselect'], "WHERE `id`='".$editservice."'");
                 rcms_redirect("?module=multinet");
              }
              multinet_show_serviceeditform($editservice);
          }
      }
      
       
      }
      else {
              show_error(__('Access denied'));
      }
 ?>
