<?php

if ($system->checkForRight('STREETEPORT')) {

    /*
     * streets report base class
     */

    class ReportStreets {

        protected $cities = array();
        protected $streets = array();
        protected $builds = array();
        protected $apts = array();
        protected $totalusercount=0;

        public function __construct() {
            $this->loadCities();
            $this->loadStreets();
            $this->loadBuilds();
            $this->loadApts();
            
            $this->countApts();
            $this->countBuilds();
        }

        /*
         * loads available cities from database into private data property
         * 
         * @return void
         */

        protected function loadCities() {
            $query = "SELECT * from `city`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->cities[$each['id']] = $each['cityname'];
                }
            }
        }

        /*
         * loads available streets from database into private data property
         * 
         * @return void
         */

        protected function loadStreets() {
            $query = "SELECT * from `street`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->streets[$each['id']]['streetname'] = $each['streetname'];
                    $this->streets[$each['id']]['cityid'] = $each['cityid'];
                    $this->streets[$each['id']]['buildcount'] = 0;
                    $this->streets[$each['id']]['usercount'] = 0;
                }
            }
        }

        /*
         * loads available builds from database into private data property
         * 
         * @return void
         */

        protected function loadBuilds() {
            $query = "SELECT * from `build`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->builds[$each['id']]['buildnum'] = $each['buildnum'];
                    $this->builds[$each['id']]['streetid'] = $each['streetid'];
                    $this->builds[$each['id']]['aptcount'] = 0;
                }
            }
        }

        /*
         * loads available apts from database into private data property
         * 
         * @return void
         */

        protected function loadApts() {
            $query = "SELECT * from `apt`";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->apts[$each['id']]['apt'] = $each['apt'];
                    $this->apts[$each['id']]['buildid'] = $each['buildid'];
                }
            }
        }

        /*
         * prepares builds data for render report
         * 
         * @return void
         */

        protected function countApts() {
            if (!empty($this->builds)) {
                if (!empty($this->apts)) {
                    foreach ($this->apts as $io=>$eachapt) {
                        if (isset($this->builds[$eachapt['buildid']])) {
                            $this->builds[$eachapt['buildid']]['aptcount']++;
                            $this->totalusercount++;
                        }
                    }
                }
            }
        }

        /*
         * prepares streets data for render report
         * 
         * @return void
         */

        protected function countBuilds() {
            if (!empty($this->streets)) {
                if (!empty($this->builds)) {
                    foreach ($this->builds as $io => $eachbuild) {
                        if (isset($this->streets[$eachbuild['streetid']])) {
                            $this->streets[$eachbuild['streetid']]['buildcount']++;
                            $this->streets[$eachbuild['streetid']]['usercount']=$this->streets[$eachbuild['streetid']]['usercount']+$eachbuild['aptcount'];
                        }
                    }
                }
            }
        }
        
        /*
         * returns colorized register level for street
         * 
         * @param int $usercount  Registered apts (users) count on the street
         * @param int $buildcount Builds count on the street
         * 
         * @return string
         */
        protected function getLevel($usercount,$buildcount) {
           if (($usercount != 0) AND ( $buildcount != 0)) {
                $level = $usercount / $buildcount;
            } else {
                $level = 0;
            }
            $level = round($level, 2);
            $color = 'black';
            if ($level < 2) {
                $color = 'red';
            }
            if ($level >= 3) {
                $color = 'green';
            }
            $result=  wf_tag('font', false, '', 'color="'.$color.'"').$level.  wf_tag('font', true);
            return ($result);
        }
        
        /*
         * renders report by prepeared data
         * 
         * @return string
         */
        public function render() {
            if (!empty($this->streets)) {
                
                $cells=  wf_TableCell(__('ID'));
                $cells.=  wf_TableCell(__('City'));
                $cells.=  wf_TableCell(__('Street'));
                $cells.=  wf_TableCell(__('Builds'));
                $cells.=  wf_TableCell(__('Registered'));
                $cells.=  wf_TableCell(__('Visual'));
                $cells.=  wf_TableCell(__('Level'));
                $rows=  wf_TableRow($cells, 'row1');
                
                foreach ($this->streets as $streetid=>$each) {
                        $cells=  wf_TableCell($streetid);
                        $cells.=  wf_TableCell(@$this->cities[$each['cityid']]);
                        $cells.=  wf_TableCell($each['streetname']);
                        $cells.=  wf_TableCell($each['buildcount']);
                        $cells.=  wf_TableCell($each['usercount']);
                        $cells.=wf_TableCell(web_bar($each['usercount'], $this->totalusercount), '50%', '', 'sorttable_customkey="' . $each['usercount'] . '"');
                        $cells.=  wf_TableCell($this->getLevel($each['usercount'], $each['buildcount']));
                        $rows.=  wf_TableRow($cells, 'row3');
                }
                
                $result=  wf_TableBody($rows, '100%', '0', 'sortable');
                
            } else {
                $result=__('Nothing found');
            }
            return ($result);
        }

    }

    
    $streetReport = new ReportStreets();
    show_window(__('Streets report'),$streetReport->render());

    
} else {
    show_error(__('Access denied'));
}
?>
