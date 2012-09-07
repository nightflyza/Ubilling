<?php
if (cfr('HELP')) {
    
    if (wf_CheckGet(array('chapter'))) {
        $chapter=$_GET['chapter'];
        $chapter_content=web_HelpChapterGet($chapter);
        show_window(__('Context help'), $chapter_content);
        show_window('', wf_Link('javascript: history.go(-1);', 'Back', false, 'ubButton'));
    }
    
}  else {
      show_error(__('You cant control this module'));
}