<?php

if (cfr('PHONEBOOK')) {

    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PHONEBOOK_ENABLED']) {

        $phonebook = new PhoneBook();

        if (wf_CheckGet(array('ajax'))) {
            $phonebook->renderAjaxContacts();
        }

        //yep, edit rights check here
        if (cfr('PHONEBOOKEDIT')) {
            //creatin new contact
            if (wf_CheckPost(array('newcontactphone', 'newcontactname'))) {
                $phonebook->createContact($_POST['newcontactphone'], $_POST['newcontactname']);
                rcms_redirect($phonebook::URL_ME);
            }

            //contact deletion
            if (wf_CheckGet(array('deletecontactid'))) {
                $phonebook->deleteContact($_GET['deletecontactid']);
                rcms_redirect($phonebook::URL_ME);
            }

            //contact editing 
            if (wf_CheckPost(array('editcontactid'))) {
                $phonebook->saveContact();
                rcms_redirect($phonebook::URL_ME);
            }
        }

        show_window(__('Phonebook'), $phonebook->renderContactsContainer());

        if (cfr('PHONEBOOKEDIT')) {
            show_window(__('Create new contact'), $phonebook->createForm());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}

