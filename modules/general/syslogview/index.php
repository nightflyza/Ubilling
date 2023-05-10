<?php

if (cfr('ROOT')) {

    if (!empty($_POST['showlogs']) && !empty($_POST['viewlog']) && is_array($_POST['viewlog'])) {
        $frm = new InputForm('', 'post', '&lt; ' . __('Back'));
        show_window('', $frm->show(true));
        $output = '';
        foreach ($_POST['viewlog'] as $logfile) {
            $logfile = basename($logfile);
            if (substr($logfile, -3) == '.gz') {
                $contents = gzfile_get_contents($system->logging . $logfile);
            } else {
                $contents = file_get_contents($system->logging . $logfile);
            }
            $output .= wf_tag('pre') . rcms_parse_text('---------------------------------' . $logfile . '' . $contents, true, false, true) . wf_tag('pre', true);
        }
        show_window(__('Showing') . ' ' . $logfile, $output);
    } elseif (!empty($_POST['showlogs_from_archive']) && !empty($_POST['archive']) && !empty($_POST['viewlog']) && is_array($_POST['viewlog'])) {
        $frm = new InputForm('', 'post', '&lt; ' . __('Back'));
        $frm->hidden('browse_archive', '1');
        $frm->hidden('browse', $_POST['archive']);
        show_window('', $frm->show(true));
        $_POST['archive'] = basename($_POST['archive']);
        if (@is_readable($system->logging . $_POST['archive'])) {
            $output = '';
            $archive = new tar();
            $archive->openTAR($system->logging . $_POST['archive']);
            foreach ($_POST['viewlog'] as $logfile) {
                $logfile = basename($logfile);
                if ($gz_contents = $archive->getFile($logfile)) {
                    $gz_contents = $gz_contents['file'];
                    if (substr($logfile, -3) == '.gz') {
                        file_write_contents($system->logging . $logfile, $gz_contents);
                        $contents = gzfile_get_contents($system->logging . $logfile);
                        rcms_delete_files($system->logging . $logfile);
                    } else {
                        $contents = &$gz_contents;
                    }
                    $output .= rcms_parse_text('[quote=' . $logfile . ']' . $contents . '[/quote]', true, false, true);
                }
            }
            unset($archive);
        }
        deb($output);
    } elseif (!empty($_POST['browse_archive']) && !empty($_POST['browse'])) {
        $frm = new InputForm('', 'post', '&lt; ' . __('Back'));
        show_window('', $frm->show(true));
        $_POST['browse'] = basename($_POST['browse']);
        if (is_readable($system->logging . $_POST['browse'])) {
            $archive = new tar();
            $archive->openTAR($system->logging . $_POST['browse']);
            $frm = new InputForm('', 'post', __('Show selected'));
            $frm->addbreak(__('Avaible logs in archive') . ' ' . $_POST['browse']);
            $frm->hidden('showlogs_from_archive', '1');
            $frm->hidden('archive', $_POST['browse']);
            foreach ($archive->files as $file_data) {
                if (preg_match("/^((.*?)-(.*?)-(.*?))\.log(|.gz)$/i", $file_data['name'], $matches)) {
                    $frm->addrow($matches[1], $frm->checkbox('viewlog[]', $file_data['name'], ''));
                }
            }
            deb($frm->show(true));
            unset($archive);
        }
    } else {
        if (!empty($_POST['build_monthly'])) {
            $system->logMergeByMonth();
            show_success(__('Archivation done'));
        }
        $frm = new InputForm('', 'post', __('Build monthly log archives (except current month)'));
        $frm->hidden('build_monthly', '1');
        show_window('', $frm->show(true));

        $frm = new InputForm('', 'post', __('Show selected'));
        $logs = rcms_scandir($system->logging);
        $frm->hidden('showlogs', '1');
        foreach ($logs as $log_entry) {
            if (preg_match("/^((.*?)-(.*?)-(.*?))\.log(|.gz)$/i", $log_entry, $matches)) {
                $frm->addrow($matches[1], $frm->checkbox('viewlog[]', $log_entry, ''));
            }
        }
        show_window(__('Avaible logs'), $frm->show(true));

        $frm = new InputForm('', 'post', __('Browse selected'));

        $frm->hidden('browse_archive', '1');
        foreach ($logs as $log_entry) {
            if (preg_match("/^((.*?)-(.*?))\.tar(|.gz)$/i", $log_entry, $matches)) {
                $frm->addrow($frm->radio_button('browse', array($log_entry => $log_entry), '-1'));
            }
        }
        show_window(__('Avaible monthly log archives'), $frm->show(true));
    }
} else {
    show_error(__('Access denied'));
}
