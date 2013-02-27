<?php
if (cfr('RADIUS')) {
    
     if (isset($_GET['username'])) {
        $login=$_GET['username'];
        
        ra_UserRebuild($login,true);
        
        show_window('', web_UserControls($login));
     }
    
    
} else {
    
}
    

?>
