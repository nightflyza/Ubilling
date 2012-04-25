<?php

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
            $result='<a href="?module=help&chapter='.$modulename.'" target="_BLANK"><img src="skins/help.gif" title="'.__('Context help').'"></a>';
         }
       }
    }
    return ($result);
}


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
    



?>
