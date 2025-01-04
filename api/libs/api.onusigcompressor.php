 <?php
    /**
     * This class is responsible for compressing ONU signal history logs by filtering and trimming the data.
     * It processes signal history files to retain only the first and last entries of each day, 
     * while keeping the current month's logs unchanged.
     **/
    class ONUSigCompressor {
        /**
         * contains OLTattractor ONU signal history path
         *
         * @var string
         */
        protected $onuSigPath = '';
        /**
         * Contains list of all available ONU signal history files as fileName=>filePath
         *
         * @var array
         */
        protected $allOnuSigFiles = array();
        /**
         * Contains current month in YYYY-MM- format
         *
         * @var string
         */
        protected $curMonth = '';
        /**
         * Total skipped records count
         *
         * @var int
         */
        protected $recordsSkipped = 0;

        /**
         * Some other predefined stuff
         */
        const PID = 'ONUSIGCOMPRESSOR';

        public function __construct() {
            $this->setOptions();
            $this->loadOnuSigDataList();
        }

        /**
         * Sets some options for the ONU signal compressor.
         *
         * @return void
         */
        protected function setOptions() {
            $this->onuSigPath = OLTAttractor::ONUSIG_PATH;
            $this->curMonth = curmonth() . '-';
        }

        /**
         * Loads the list of ONU signal data files into the `allOnuSigFiles` property.
         *
         * @return void
         */
        protected function loadOnuSigDataList() {
            $tmp = rcms_scandir($this->onuSigPath);
            if (!empty($tmp)) {
                foreach ($tmp as $io => $each) {
                    if (strlen($each) == 32) { // hash-named data
                        $this->allOnuSigFiles[$each] = $this->onuSigPath . $each;
                    }
                }
            }
        }

        /**
         * Filters signal history data from obsolete data
         *
         * @param array $lines An array of log lines to be filtered.
         * 
         * @return array
         */
        protected function filterDailyLogs($lines) {
            $logsByDate = array();
            $currentMonthLogs = array();
            $filteredLogs = array();

            foreach ($lines as $line) {
                if (ispos($line, $this->curMonth)) {
                    $currentMonthLogs[] = $line;
                } else {
                    list($timestamp, $value) = explode(',', $line);

                    $date = substr($timestamp, 0, 10); // YYYY-MM-DD date

                    if (!isset($logsByDate[$date])) {
                        $logsByDate[$date] = ["first" => $line, "last" => $line];
                    } else {
                        $logsByDate[$date]["last"] = $line;
                    }
                }
            }

            foreach ($logsByDate as $entries) {
                $filteredLogs[] = $entries["first"];
                if ($entries["first"] !== $entries["last"]) {
                    $filteredLogs[] = $entries["last"];
                }
            }

            //appending current month logs unchanged
            $filteredLogs = array_merge($filteredLogs, $currentMonthLogs);
            return ($filteredLogs);
        }

        /**
         * Trims signal data from the specified file by filtering out unnecessary logs.
         *
         * This method reads the content of the specified file, filters the logs based on the current month,
         * and overwrites the file with the filtered content if any records were removed.
         *
         * @param string $filePath The path to the file containing the signal data to be trimmed.
         *
         * @return void
         */
        protected function trimSignalData($filePath) {
            if (file_exists($filePath)) {
                $fileContent = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (!empty($fileContent)) {
                    $newFileContent = $this->filterDailyLogs($fileContent);
                    $recsBefore = sizeof($fileContent);
                    $recsAfter = sizeof($newFileContent);
                    if ($recsAfter < $recsBefore) {
                        //compression successful, overwriting source
                        $this->recordsSkipped += $recsBefore - $recsAfter;
                        file_put_contents($filePath, implode(PHP_EOL, $newFileContent) . PHP_EOL);
                    }
                }
            }
        }
        /**
         * Runs the ONUSIG compressor process.
         *
         * @return void
         */
        public function run() {
            set_time_limit(0);
            $process = new StarDust(self::PID);
            if ($process->notRunning()) {
                $process->start();
                log_register('ONUSIGCOMPRESSOR STARTED');
                if (!empty($this->allOnuSigFiles)) {
                    foreach ($this->allOnuSigFiles as $io => $eachSigHistory) {
                        $this->trimSignalData($eachSigHistory);
                    }
                }
                log_register('ONUSIGCOMPRESSOR `' . $this->recordsSkipped . '` RECORDS CLEANED IN `' . sizeof($this->allOnuSigFiles) . '` FILES');
                log_register('ONUSIGCOMPRESSOR FINISHED');
                $process->stop();
            } else {
                log_register('ONUSIGCOMPRESSOR ALREADY RUNNING SKIPPED');
            }
        }
    }
