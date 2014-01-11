<?php

if (cfr('TAGS')) {

    class TagCloud {
        /*
         * Gets all tagged users
         * 
         * @return array
         */

        private function getAllTagged() {
            $query = 'SELECT DISTINCT `tagid` from `tags` ORDER BY `tagid` ASC';
            $alltags = simple_queryall($query);
            return ($alltags);
        }

        /*
         * Gets some tag power by its ID
         * 
         * @param $tagid - existing tag ID
         */

        private function getTagPower($tagid) {
            $tag = vf($tagid, 3);
            $query = 'SELECT COUNT(`tagid`) FROM `tags` where `tagid`="' . $tagid . '"';
            $result = simple_query($query);
            return($result['COUNT(`tagid`)']);
        }

        /*
         * Gets all tags names as array tagid=>tagname
         * 
         * @return array
         */

        private function getAllTagNames() {
            $query = "SELECT `id`,`tagname` from `tagtypes`";
            $result = array();
            $alltags = simple_queryall($query);
            if (!empty($alltags)) {
                foreach ($alltags as $io => $eachtag) {
                    $result[$eachtag['id']] = $eachtag['tagname'];
                }
            }
            return($result);
        }

        /*
         * returns control panel for tagcloud
         * 
         * @return string
         */

        private function panel() {
            $result = wf_Link('?module=tagcloud', __('Tag cloud'), false, 'ubButton');
            $result.= wf_Link('?module=tagcloud&gridview=true', __('Grid view'), true, 'ubButton');
            return ($result);
        }

        /*
         * Renders tag grid for tagged users
         * 
         * @return void
         */

        public function renderTagGrid() {
            $alltags = $this->getAllTagged();
            $allnames = $this->getAllTagNames();

            
            $cells=  wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Tags'));
            $cells.= wf_TableCell(__('Users'));
            $rows= wf_TableRow($cells, 'row1');

            if (!empty($alltags)) {
                foreach ($alltags as $key => $eachtag) {
                     if (isset($allnames[$eachtag['tagid']])) {
                        $userCount = $this->getTagPower($eachtag['tagid']);
                        $cells=  wf_TableCell($eachtag['tagid']);
                        $cells.= wf_TableCell(wf_Link('?module=tagcloud&gridview=true&tagid=' . $eachtag['tagid'], $allnames[$eachtag['tagid']], false));
                        $cells.= wf_TableCell($userCount);
                        $rows.= wf_TableRow($cells, 'row3');
                    }
                }
            }
            
            $result = $this->panel();
            $result.=wf_TableBody($rows, '100%', '0', 'sortable');
            show_window(__('Tags'), $result);
            
        }

        /*
         * Renders tag cloud for tagged users
         * 
         * @return void
         */

        public function renderTagCloud() {
            $alltags = $this->getAllTagged();
            $allnames = $this->getAllTagNames();

            $result = $this->panel();
            $result.= wf_tag('center');

            if (!empty($alltags)) {
                foreach ($alltags as $key => $eachtag) {
                    $power = $this->getTagPower($eachtag['tagid']);
                    $fsize = $power / 2;
                    if (isset($allnames[$eachtag['tagid']])) {
                        $sup = wf_tag('sup') . $power . wf_tag('sup', true);
                        $result.=wf_tag('font', false, '', 'size="' . $fsize . '"');
                        $result.=wf_Link('?module=tagcloud&tagid=' . $eachtag['tagid'], $allnames[$eachtag['tagid']] . $sup, false);
                        $result.=wf_tag('font', true);
                    }
                }
            }
            $result.=wf_tag('center', true);
            show_window(__('Tags'), $result);
        }

        /*
         * Renders tagged users by tag ID
         * 
         * @param $tagid - existing tag ID
         * 
         * @return void
         */

        public function renderTagUsers($tagid) {
            $alltagnames = $this->getAllTagNames();
            $query = "SELECT DISTINCT `login` from `tags` where `tagid`='" . $tagid . "'";
            $allusers = simple_queryall($query);
            $userarr = array();
            if (!empty($allusers)) {
                foreach ($allusers as $io => $eachuser) {
                    $userarr[] = $eachuser['login'];
                }
            }
            $result = web_UserArrayShower($userarr);
            show_window($alltagnames[$tagid], $result);
        }

    }

    /*
     * Controller & view section
     */



    $tagCloud = new TagCloud();

//show cloud or grid tag view
    if (!wf_CheckGet(array('gridview'))) {
        $tagCloud->renderTagCloud();
    } else {
        $tagCloud->renderTagGrid();
    }

//show selected tag users
    if (isset($_GET['tagid'])) {
        $tagid = vf($_GET['tagid'], 3);
        $tagCloud->renderTagUsers($tagid);
    }
} else {
    show_window(__('Error'), __('You cant control this module'));
}
?>
