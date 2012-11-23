<?php


/*
 *  Returns help chapter in current locale
 *  @param   $chapter Help chapter name
 *  @return  string
 */
    
   function web_HelpChapterGet($chapter) {
       $lang=curlang();
       $chapter=vf($chapter);
       $result='';
       if (file_exists(DATA_PATH."help/".$lang."/".$chapter)) {
            $result.=file_get_contents(DATA_PATH."help/".$lang."/".$chapter);
            $result=nl2br($result);
         }
         return ($result);
   }
    


/*
 *  Shows help icon if context chapter 
 *  available for current language
 * 
 *  @return  string
 */

function web_HelpIconShow() {
    $lang=curlang();
    $result='';
    if (cfr('HELP')) {
    if (isset($_GET['module'])) {
        $modulename=vf($_GET['module']);
        if (file_exists(DATA_PATH."help/".$lang."/".$modulename)) {
          $help_chapter=  web_HelpChapterGet($modulename);  
          $result=  wf_modal(wf_img("skins/help.gif", __('Context help')), __('Context help'), $help_chapter, '', '600','300');
         }
       }
    }
    return ($result);
}




?>
