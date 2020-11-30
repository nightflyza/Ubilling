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
        } elseif (ubRouting::checkGet(array('notags'))) {
            // show users which not have a tag
            $tagCloud->renderNoTagGrid();
        } elseif (ubRouting::checkGet(array('noemployeetags'))) {
            // show users which not have a tag
            $tagCloud->renderNoEmployeeTags();
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
