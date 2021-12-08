<?php

if (cfr('TAGCLOUD')) {

    /*
     * Controller & view section
     */

    $tagCloud = new TagCloud();

    //show cloud or grid tag view
    if (!ubRouting::checkGet('gridview')) {
        if (ubRouting::checkGet('report')) {
            $tagCloud->renderReport();
        } elseif (ubRouting::checkGet('notags')) {
            // show users which not have a tag
            $tagCloud->renderNoTagGrid();
        } elseif (ubRouting::checkGet('noemployeetags')) {
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
    if (ubRouting::get('tagid')) {
        $tagid = ubRouting::get('tagid', 'int');
        $tagCloud->renderTagUsers($tagid);
    }
} else {
    show_error(__('You cant control this module'));
}
