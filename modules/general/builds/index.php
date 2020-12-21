<?php
// check for right of current admin on this module
if (cfr('BUILDS')) {
    if (!isset ($_GET['action'])) {
        if ( wf_CheckGet(array('ajax')) ) {
            renderBuildsEditJSON();
        }

        show_window(__('Builds editor'),  web_StreetListerBuildsEdit());
    } else {
        if (isset($_GET['streetid'])) {
           $streetid=vf($_GET['streetid']);

           if ($_GET['action']=='edit') {
               if (isset($_POST['newbuildnum'])) {
                   if (!empty($_POST['newbuildnum'])) {
                       $FoundBuildID = checkBuildOnStreetExists($_POST['newbuildnum'], $streetid);

                       if (empty($FoundBuildID)) {
                           zb_AddressCreateBuild($streetid, trim($_POST['newbuildnum']));
                           die();
                       } else {
                           $messages = new UbillingMessageHelper();
                           $errormes = $messages->getStyledMessage(__('Build with such number already exists on this street with ID: ') . $FoundBuildID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                           die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                       }

                   }
               }

               if ( wf_CheckGet(array('ajax')) ) {
                   renderBuildsLiserJSON($streetid);
               }

               $streetname = zb_AddressGetStreetData($streetid);
               $streetname = $streetname['streetname'];

               show_window(__('Available buildings on street').' '.$streetname, web_BuildLister($streetid));
           }

           if ($_GET['action']=='delete') {
               if (!zb_AddressBuildProtected($_GET['buildid'])) {
                    zb_AddressDeleteBuild($_GET['buildid']);
                    die();
               } else {
                   $messages = new UbillingMessageHelper();
                   $errormes = $messages->getStyledMessage(__('You can not delete a building if there are users of the apartment'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                   die(wf_modalAutoForm(__('Error'), $errormes, $_GET['errfrmid'], '', true));
                }

           }

           if ($_GET['action']=='editbuild') {
               $buildid=vf($_GET['buildid']);
               $streetid=vf($_GET['streetid']);

               if ( wf_CheckGet(array('ajax')) ) {
                   renderBuildsLiserJSON($streetid, $buildid);
               }

               //build edit subroutine
               if (isset($_POST['editbuildnum'])) {
                   if (!empty($_POST['editbuildnum'])) {
                       $FoundBuildID = checkBuildOnStreetExists($_POST['editbuildnum'], $streetid, $buildid);

                       if (empty($FoundBuildID)) {
                           simple_update_field('build', 'buildnum', trim($_POST['editbuildnum']), "WHERE `id`='" . $buildid . "'");
                           simple_update_field('build', 'geo', preg_replace('/[^-?0-9\.,]/i', '', $_POST['editbuildgeo']), "WHERE `id`='" . $buildid . "'");

                           log_register("CHANGE AddressBuild [" . $buildid . "] " .  mysql_real_escape_string(trim($_POST['editbuildnum'])));
                           die();
                       } else {
                           $messages = new UbillingMessageHelper();
                           $errormes = $messages->getStyledMessage(__('Build with such number already exists on this street with ID: ') . $FoundBuildID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                           die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                       }
                   }
               }

               //construct edit form
               if ( wf_CheckGet(array('frommaps'))) {
                   $streetname = zb_AddressGetStreetData($streetid);
                   $streetname = $streetname['streetname'];

                   show_window(__('Available buildings on street').' '.$streetname, web_BuildLister($streetid, $buildid));
               } else {
                   die(wf_modalAutoForm(__('Edit') . ' ' . __('Build'), web_BuildEditForm($buildid, $streetid, $_GET['ModalWID']), $_GET['ModalWID'], $_GET['ModalWBID'], true));
               }
           }
        }
    }
} else {
  show_error(__('Access denied'));
}

?>
