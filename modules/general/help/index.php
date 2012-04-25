<?php
if (cfr('HELP')) {
    
    if (wf_CheckGet(array('chapter'))) {
        $chapter=$_GET['chapter'];
        $chapter_content=web_HelpChapterGet($chapter);
        show_window(__('Context help'), $chapter_content);
    }
    
}  else {
      show_error(__('You cant control this module'));
}