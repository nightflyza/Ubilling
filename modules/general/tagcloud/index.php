<?php

if (cfr('TAGCLOUD')) {

    /*
     * Controller & view section
     */

    $tagCloud = new TagCloud();

    //show cloud or grid tag view
    if (!wf_CheckGet(array('gridview'))) {
        if (wf_CheckGet(array('report'))) {
            $tagCloud->renderReport();
        } elseif (isset($_GET['notags']) and ( $_GET['notags']) == TRUE) {
            // show users which not have a tag
            $tagCloud->renderNoTagGrid();
        } else {
            //default tag cloud
            $tagCloud->renderTagCloud();
        }
    } else {
        //grid view
        $tagCloud->renderTagGrid();
    }

//show selected tag users
    if (isset($_GET['tagid'])) {
        $tagid = vf($_GET['tagid'], 3);
        $tagCloud->renderTagUsers($tagid);
    }
} else {
    show_error(__('You cant control this module'));
}
?>
