<?php

if (cfr('TAGS')) {

    class TagCloud {
        
        protected $alltags = array();
        protected $usertags = array();
        protected $allnames = array();
        protected $tagspower=array();


        public function __construct() {
            $this->loadTags();
            $this->loadTagNames();
            $this->loadUserTags();
            $this->tagPowerPreprocessing();
        }
        
        /*
         * loads all used tags into private data property
         * 
         * @return void
         */
        protected function loadTags() {
            $this->alltags=$this->getAllTagged();
        }
        
        
        /*
         * loads all tag names into private data property
         * 
         * @return void
         */
        protected function loadTagNames() {
            $this->allnames=$this->getAllTagNames();
        }
        
        /*
         * loads all users tags into private data property
         */
        protected function loadUserTags() {
            $query="SELECT `login`,`tagid`,`id` from `tags`";
            $all= simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io=>$each) {
                    $this->usertags[$each['id']]['login']=$each['login'];
                    $this->usertags[$each['id']]['tagid']=$each['tagid'];
                }
            }
        }

        
        /*
         * preprocessing of tagspower by alltags private property
         * 
         * @return void
         */
        protected function tagPowerPreprocessing() {
            if (!empty($this->usertags)) {
                foreach ($this->usertags as $io=>$each) {
                    if (isset($this->tagspower[$each['tagid']])) {
                        $this->tagspower[$each['tagid']]++;
                    } else {
                        $this->tagspower[$each['tagid']]=1;
                    }
                }
            }
        }


        /*
         * Gets all tagged users
         * 
         * @return array
         */

        protected function getAllTagged() {
            $query = 'SELECT DISTINCT `tagid` from `tags` ORDER BY `tagid` ASC';
            $alltags = simple_queryall($query);
            return ($alltags);
        }

        /*
         * Gets some tag power by its ID
         * 
         * @param $tagid - existing tag ID
         * 
         * @return int
         */

        protected function getTagPower($tagid) {
            if (!empty($this->tagspower)) {
                if (isset($this->tagspower[$tagid])) {
                    $result=$this->tagspower[$tagid];
                } else {
                    $result=0;
                }
            } else {
                $result=0;
            }
            return ($result);
        }

        /*
         * Gets all tags names as array tagid=>tagname
         * 
         * @return array
         */

        protected function getAllTagNames() {
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

        protected function panel() {
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
            $cells=  wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Tags'));
            $cells.= wf_TableCell(__('Users'));
            $rows= wf_TableRow($cells, 'row1');

            if (!empty($this->alltags)) {
                foreach ($this->alltags as $key => $eachtag) {
                     if (isset($this->allnames[$eachtag['tagid']])) {
                        $userCount = $this->getTagPower($eachtag['tagid']);
                        $cells=  wf_TableCell($eachtag['tagid']);
                        $cells.= wf_TableCell(wf_Link('?module=tagcloud&gridview=true&tagid=' . $eachtag['tagid'], $this->allnames[$eachtag['tagid']], false));
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
            $result = $this->panel();
            $result.= wf_tag('center');

            if (!empty($this->alltags)) {
                foreach ($this->alltags as $key => $eachtag) {
                    $power = $this->getTagPower($eachtag['tagid']);
                    $fsize = $power / 2;
                    if (isset($this->allnames[$eachtag['tagid']])) {
                        $sup = wf_tag('sup') . $power . wf_tag('sup', true);
                        $result.=wf_tag('font', false, '', 'size="' . $fsize . '"');
                        $result.=wf_Link('?module=tagcloud&tagid=' . $eachtag['tagid'], $this->allnames[$eachtag['tagid']] . $sup, false);
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
            $userarr = array();
            if (!empty($this->usertags)) {
                foreach ($this->usertags as $io => $each) {
                    if ($each['tagid']==$tagid) {
                        $userarr[] = $each['login'];
                    }
                }
            }
            $result = web_UserArrayShower($userarr);
            show_window($this->allnames[$tagid], $result);
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
