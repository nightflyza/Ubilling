<?php

/**
 * Can unicorns teleport? I hope so.
 */
class UnicornTeleport {
    
    /**
     * Ubinstaller download URL
     *
     * @var string
     */
    protected $ubinstallerUrl='http://snaps.ubilling.net.ua/';
    
    /**
     * Ubinstaller package filename
     *
     * @var string
     */
    protected $ubinstllerName='ubinstaller_current.tar.gz';
    
    /**
     * Apache web data directory path
     *
     * @var string
     */
    protected $saveApacheDataPath='/usr/local/www/apache24/data/';
    
    /**
     * Apache configuration directory path
     *
     * @var string
     */
    protected $saveApacheConfPath='/usr/local/etc/apache24/';
    
    /**
     * SQL backups directory path
     *
     * @var string
     */
    protected $backupsPath='content/backups/sql/';
    
    /**
     * RC configuration file path
     *
     * @var string
     */
    protected $saveRcConfPath='/etc/rc.conf';
    
    /**
     * Firewall configuration file path
     *
     * @var string
     */
    protected $saveFirewallConf='/etc/firewall.conf';
    
    /**
     * Stargazer configuration directory path
     *
     * @var string
     */
    protected $saveStargazerDirPath='/etc/stargazer/';
    
    /**
     * Stargazer configuration file path
     *
     * @var string
     */
    protected $saveStargazerConfPath='/etc/stargazer/stargazer.conf';
    
    /**
     * Temporary directory path for teleport operations
     *
     * @var string
     */
    protected $tmpPath='/tmp/unicornteleport/';
    
    /**
     * Export directory path for teleport packages
     *
     * @var string
     */
    protected $teleportExportPath='exports/';
    
    /**
     * Teleport package filename
     *
     * @var string
     */
    protected $teleportDownloadName='unicornteleport.tgz';
    
    /**
     * Database dump filename
     *
     * @var string
     */
    protected $teleportDumpName='unicornteleport.sql';
    
    /**
     * Sudo binary path
     *
     * @var string
     */
    protected $sudoPath='';
    
    /**
     * Tar binary path
     *
     * @var string
     */
    protected $tarPath='';
    
    /**
     * Gzip binary path
     *
     * @var string
     */
    protected $gzipPath='';
    
    /**
     * Grep binary path
     *
     * @var string
     */
    protected $grepPath='';
    
    /**
     * MySQL binary path
     *
     * @var string
     */
    protected $mysqlPath='';
    
    /**
     * Crontab binary path
     *
     * @var string
     */
    protected $crontabPath='/usr/bin/crontab';
    
    /**
     * Current Ubilling release version
     *
     * @var string
     */
    protected $currentRelease='';
    
    /**
     * Teleport migration data array
     *
     * @var array
     */
    protected $teleportData=array(
        'serial'=>'',
        'myspass'=>'',
        'stgpass'=>'',
        'rsdpass'=>'',
    );

    /**
     * Available packages array
     *
     * @var array
     */
    protected $packagesAvailable=array();
    
    /**
     * Alternative configuration array
     *
     * @var array
     */
    protected $altCfg = array();
    
    /**
     * Billing configuration array
     *
     * @var array
     */
    protected $billCfg = array();
    
    /**
     * MySQL configuration array
     *
     * @var array
     */
    protected $mySqlCfg = array();
    /**
     * StarDust object placeholder
     *
     * @var object
     */
    protected $startDust='';

    /**
     * Error messages array
     *
     * @var array
     */
    protected $errorMessages=array();

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages='';

    /**
     * Process ID for teleport operations
     */
    const PID_TELEPORT='UNICORNTELEPORT';
    
    /**
     * POST route parameter for architecture
     */
    const PROUTE_ARCH='defaultarch';
    
    /**
     * POST route parameter for internal interface
     */
    const PROUTE_INT_IF='defaultintif';
    
    /**
     * POST route parameter for external interface
     */
    const PROUTE_EXT_IF='defaultextif';
    
    /**
     * GET route parameter for download
     */
    const ROUTE_DOWNLOAD='download';
    
    /**
     * POST route parameter for database packing
     */
    const PROUTE_PACK_DATABASE='packdatabase';
    
    /**
     * POST route parameter for www data packing
     */
    const PROUTE_PACK_WWWDATA='packwwwdata';
    
    /**
     * POST route parameter for Apache configuration packing
     */
    const PROUTE_PACK_APACHE_CONF='packapacheconf';
    
    /**
     * POST route parameter for RC configuration packing
     */
    const PROUTE_PACK_RC_CONF='packrcconf';
    
    /**
     * POST route parameter for firewall configuration packing
     */
    const PROUTE_PACK_FIREWALL_CONF='packfirewallconf';
    
    /**
     * POST route parameter for Stargazer configuration packing
     */
    const PROUTE_PACK_STGCONFIG='packstgconfig';
    
    /**
     * POST route parameter for crontab packing
     */
    const PROUTE_PACK_CRONTAB='packcrontab';

    /**
     * Module URL
     */
    const URL_ME='?module=unicornteleport';
    

    /**
     * Creates new UnicornTeleport instance
     */
    public function __construct() {
        $this->initMessages();
        $this->initConfigs();
        $this->loadPackagesAvailable();
        $this->loadMigrationData();
        $this->initStartDust();
    }

    /**
     * Initializes system messages helper
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages=new UbillingMessageHelper();
    }

    /**
     * Initializes StarDust process manager
     *
     * @return void
     */
    protected function initStartDust() {
        $this->startDust=new StarDust(self::PID_TELEPORT, false, true);
    }

    /**
     * Initializes configuration arrays and system paths
     *
     * @return void
     */
    protected function initConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
        $this->mySqlCfg = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
        $this->sudoPath=$this->billCfg['SUDO'];
        $this->tarPath=$this->billCfg['TAR_PATH'];
        $this->gzipPath=$this->billCfg['GZIP_PATH'];
        $this->grepPath=$this->billCfg['GREP'];
        $this->mysqlPath=$this->altCfg['MYSQL_PATH'];
    }


    /**
     * Checks system binary paths availability
     *
     * @return bool
     */
    protected function checkSysPaths() {
        $this->errorMessages = array();
        $result = true;
        
        if (empty($this->sudoPath) or !file_exists($this->sudoPath)) {
            $this->errorMessages['SUDO'] = $this->sudoPath;
            $result = false;
        }
        
        if (empty($this->tarPath) or !file_exists($this->tarPath)) {
            $this->errorMessages['TAR_PATH'] = $this->tarPath;
            $result = false;
        }
        
        if (empty($this->gzipPath) or !file_exists($this->gzipPath)) {
            $this->errorMessages['GZIP_PATH'] = $this->gzipPath;
            $result = false;
        }
        
        if (empty($this->grepPath) or !file_exists($this->grepPath)) {
            $this->errorMessages['GREP'] = $this->grepPath;
            $result = false;
        }
        
        if (empty($this->mysqlPath) or !file_exists($this->mysqlPath)) {
            $this->errorMessages['MYSQL_PATH'] = $this->mysqlPath;
            $result = false;
        }
        
        return ($result);
    }

    

    /**
     * Loads available packages from remote API
     *
     * @return void
     */
    protected function loadPackagesAvailable() {
        $packagesApiUrl='http://ubilling.net.ua/packages/fbsdavail.php';
        $remotePackages=new OmaeUrl($packagesApiUrl);
        $response=$remotePackages->response();
        if (!empty($response) and $remotePackages->httpCode()==200) {
            if (json_validate($response)) {
                $this->packagesAvailable=json_decode($response,true);
            }
        }
    }

    /**
     * Loads migration data including serial and passwords
     *
     * @return void
     */
    protected function loadMigrationData() {
        $avarice = new Avarice();
        $this->teleportData['serial']=$avarice->getSerial();
        $this->teleportData['myspass']=$this->mySqlCfg['password'];
        $this->teleportData['stgpass']=$this->billCfg['STG_PASSWD'];
        $rsdCommand=$this->sudoPath.' '.$this->grepPath.' "Password" '.$this->saveStargazerConfPath;
        $rsdOutput=shell_exec($rsdCommand);
        if (!empty($rsdOutput)) {
            $this->teleportData['rsdpass']=trim(explode('=', $rsdOutput)[1]);
        }
        $this->currentRelease=trim(file_get_contents('RELEASE'));
    }

    /**
     * Validates teleport migration data
     *
     * @return bool
     */
    protected function checkTeleportData() {
        $this->errorMessages = array();
        $result = true;
        
        if (empty($this->teleportData['serial'])) {
            $this->errorMessages['serial'] = $this->teleportData['serial'];
            $result = false;
        }
        
        if (empty($this->teleportData['myspass'])) {
            $this->errorMessages['myspass'] = $this->teleportData['myspass'];
            $result = false;
        }
        
        if (empty($this->teleportData['stgpass'])) {
            $this->errorMessages['stgpass'] = $this->teleportData['stgpass'];
            $result = false;
        }
        
        if (empty($this->teleportData['rsdpass'])) {
            $this->errorMessages['rsdpass'] = $this->teleportData['rsdpass'];
            $result = false;
        }
        
        return ($result);
    }

    /**
     * Prepares temporary directory for teleport operations
     *
     * @return void
     */
    protected function prepareTempDir() {
        if (file_exists($this->tmpPath)) {
            rcms_delete_files($this->tmpPath,true);
        } 

        rcms_mkdir($this->tmpPath);
    }

    /**
     * Removes temporary directory and its contents
     *
     * @return void
     */
    protected function flushTempDir() {
        if (file_exists($this->tmpPath)) {
            rcms_delete_files($this->tmpPath,true);
        } 
    }

    /**
     * Creates database backup
     *
     * @return string
     */
    protected function backupDatabase() {
      $result='';
      $result=zb_BackupDatabase(true);
      return ($result);
    }

    /**
     * Archives specified path to target archive
     *
     * @param string $sourcePath
     * @param string $targetAchive
     *
     * @return bool
     */
    protected function archivePath($sourcePath, $targetAchive) {
        if (file_exists($sourcePath)) {
            $parentDir = dirname($sourcePath);
            $baseName = basename($sourcePath);
            $command=$this->sudoPath.' '.$this->tarPath.' -c -C '.$parentDir.' -f - '.$baseName.' | '.$this->gzipPath.' > '.$targetAchive;
            shell_exec($command);
        } else {
            $this->errorMessages[$sourcePath]=__('Source path is not available');
            return false;
        }
    }

    /**
     * Handles file download request
     *
     * @return void
     */
    public function catchFileDownload() {
        if (ubRouting::checkGet(self::ROUTE_DOWNLOAD)) {
            zb_DownloadFile($this->teleportExportPath.$this->teleportDownloadName);
        }
    }

    /**
     * Renders teleport README content
     *
     * @return string
     */
    protected function renderTeleportReadme() {
        $result='';
        $arch=(ubRouting::checkPost(self::PROUTE_ARCH)) ? ubRouting::post(self::PROUTE_ARCH) : '';
        $intIf=(ubRouting::checkPost(self::PROUTE_INT_IF)) ? ubRouting::post(self::PROUTE_INT_IF) : '';
        $extIf=(ubRouting::checkPost(self::PROUTE_EXT_IF)) ? ubRouting::post(self::PROUTE_EXT_IF) : '';
        $currentDateTime=curdatetime();
        $serial=$this->teleportData['serial'];

        $result .= '# Unicorn Teleport Guide'.PHP_EOL;
        $result .= PHP_EOL;
        if (empty($arch)) {
            if (!empty($this->packagesAvailable)) {
                $result .= '## Available architectures [TARGET_ARCH]:'.PHP_EOL;
                foreach ($this->packagesAvailable as $archCode => $archDescription) {
                    $result .= '- '.$archCode.' - '.$archDescription.PHP_EOL;
                }
                $result .= PHP_EOL;
            }
        }

        $result .= 'To migrate your Ubilling '.$this->currentRelease.' with serial '.$serial.' as it was by '.$currentDateTime.' to another host, make following actions:'.PHP_EOL;
        $result .= PHP_EOL;
        $result .= '## Step 1: Extract teleport package'.PHP_EOL;
        $result .= 'tar zxvf '.$this->teleportDownloadName.' -C /usr/local/'.PHP_EOL;
        $result .= 'cd /usr/local/unicornteleport'.PHP_EOL;
        $result .= PHP_EOL;
        $result .= '## Step 2: Install base system'.PHP_EOL;
        $result .= 'fetch '.$this->ubinstallerUrl.$this->ubinstllerName.PHP_EOL;
        $result .= 'tar zxvf '.$this->ubinstllerName.PHP_EOL;
        $result .= 'cd ubinstaller'.PHP_EOL;
    
        // <type> <arch> <channel> <internal_interface> [external_interface] [mysql_pass] [stargazer_pass] [rscriptd_pass] [ubilling_serial]
        $batchInstallerCmd='sh Batchinstaller.sh MIG';
        if (!empty($arch)) {
            $batchInstallerCmd.=' '.$arch;
        } else {
            $batchInstallerCmd.=' [TARGET_ARCH]';
        }
        $batchInstallerCmd.=' CURRENT';
        if (!empty($intIf)) {
            $batchInstallerCmd.=' '.$intIf;
        } else {
            $batchInstallerCmd.=' [internal_interface]';
        }
        if (!empty($extIf)) {
            $batchInstallerCmd.=' '.$extIf;
        } else {
            $batchInstallerCmd.='';
        }

        $batchInstallerCmd.=' '.$this->teleportData['myspass'].' '.$this->teleportData['stgpass'].' '.$this->teleportData['rsdpass'].' '.$this->teleportData['serial'];
        $result .= $batchInstallerCmd.PHP_EOL;
        $result .= PHP_EOL;
        $result .= '## Step 3: Restore database data and configurations'.PHP_EOL;
        $result .= 'cd /usr/local/unicornteleport'.PHP_EOL;
        if (ubRouting::checkPost(self::PROUTE_PACK_DATABASE)) {
            $result .= 'mysql --host localhost -u root -p'.$this->teleportData['myspass'].' stg < '.$this->teleportDumpName.PHP_EOL.PHP_EOL;
        }
        
        if (ubRouting::checkPost(self::PROUTE_PACK_WWWDATA)) {
            $result .= 'tar zxvf wwwdata.tgz -C '.dirname($this->saveApacheDataPath).PHP_EOL;
        }
        if (ubRouting::checkPost(self::PROUTE_PACK_APACHE_CONF)) {
            $result .= 'tar zxvf apache_conf.tgz -C '.dirname($this->saveApacheConfPath).PHP_EOL;
        }
        if (ubRouting::checkPost(self::PROUTE_PACK_RC_CONF)) {
            $result .= 'tar zxvf rcconf.tgz -C '.dirname($this->saveRcConfPath).PHP_EOL;
        }
        if (ubRouting::checkPost(self::PROUTE_PACK_FIREWALL_CONF)) {
            $result .= 'tar zxvf firewallconf.tgz -C '.dirname($this->saveFirewallConf).PHP_EOL;
        }
        if (ubRouting::checkPost(self::PROUTE_PACK_STGCONFIG)) {
            $result .= 'tar zxvf stgconfig.tgz -C '.dirname($this->saveStargazerDirPath).PHP_EOL;
        }
        if (ubRouting::checkPost(self::PROUTE_PACK_CRONTAB)) {
            $result .= '/usr/bin/crontab crontab'.PHP_EOL;
        }
        return ($result);
    }

    /**
     * Executes teleport export process
     *
     * @return string
     */
    protected function runTeleportExport() {
        $result='';
            $this->startDust->start();
            log_register('UNICORNTELEPORT EXPORT STARTED');
            $this->prepareTempDir();

            if (ubRouting::checkPost(self::PROUTE_PACK_DATABASE)) {
                $dbBackupName=$this->backupDatabase();
                if ($dbBackupName) {
                    rcms_rename_file($dbBackupName, $this->tmpPath.$this->teleportDumpName);
                    $result .= $this->messages->getStyledMessage(__('Database backup').' '.__('saved'), 'success');
                } else {
                    $this->errorMessages['database']=__('Database backup').' '.__('failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Database backup').' '.__('skipped'), 'warning');
            }

            if (ubRouting::checkPost(self::PROUTE_PACK_WWWDATA)) {
            $this->archivePath($this->saveApacheDataPath, $this->tmpPath.'wwwdata.tgz');
            if (file_exists($this->tmpPath.'wwwdata.tgz')) {
                $result .= $this->messages->getStyledMessage(__('Apache data backup').' '.__('saved'), 'success');
            } else {
                $this->errorMessages['wwwdata']=__('Apache data backup').' '.__('failed');
            }
            } else {
                $result .= $this->messages->getStyledMessage(__('Apache data backup').' '.__('skipped'), 'info');
            }

            if (ubRouting::checkPost(self::PROUTE_PACK_APACHE_CONF)) {
                $this->archivePath($this->saveApacheConfPath, $this->tmpPath.'apache_conf.tgz');
                if (file_exists($this->tmpPath.'apache_conf.tgz')) {
                    $result .= $this->messages->getStyledMessage(__('Apache configuration backup').' '.__('saved'), 'success');
                } else {
                    $this->errorMessages['apache_conf']=__('Apache configuration backup').' '.__('failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Apache configuration backup').' '.__('skipped'), 'info');
            }

            
            if (ubRouting::checkPost(self::PROUTE_PACK_RC_CONF)) {
                $this->archivePath($this->saveRcConfPath, $this->tmpPath.'rcconf.tgz');
                if (file_exists($this->tmpPath.'rcconf.tgz')) {
                    $result .= $this->messages->getStyledMessage(__('RC configuration backup').' '.__('saved'), 'success');
                } else {
                    $this->errorMessages['rcconf']=__('RC configuration backup').' '.__('failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('RC configuration backup').' '.__('skipped'), 'info');
            }

            if (ubRouting::checkPost(self::PROUTE_PACK_FIREWALL_CONF)) {
                $this->archivePath($this->saveFirewallConf, $this->tmpPath.'firewallconf.tgz');
                if (file_exists($this->tmpPath.'firewallconf.tgz')) {
                    $result .= $this->messages->getStyledMessage(__('Firewall configuration backup').' '.__('saved'), 'success');
                } else {
                    $this->errorMessages['firewallconf']=__('Firewall configuration backup').' '.__('failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Firewall configuration backup').' '.__('skipped'), 'info');
            }

            if (ubRouting::checkPost(self::PROUTE_PACK_STGCONFIG)) {
                $this->archivePath($this->saveStargazerDirPath, $this->tmpPath.'stgconfig.tgz');
                if (file_exists($this->tmpPath.'stgconfig.tgz')) {
                    $result .= $this->messages->getStyledMessage(__('Stargazer configuration backup').' '.__('saved'), 'success');
                } else {
                    $this->errorMessages['stgconfig']=__('Stargazer configuration backup').' '.__('failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Stargazer configuration backup').' '.__('skipped'), 'info');
            }

            //getting crontab file
            if (ubRouting::checkPost(self::PROUTE_PACK_CRONTAB)) {      
            $contabCommand=$this->sudoPath.' '.$this->crontabPath.' -l';
            $crontabOutput=shell_exec($contabCommand);
            $crontabOutput.=PHP_EOL;
                file_put_contents($this->tmpPath.'crontab', $crontabOutput);
                if (file_exists($this->tmpPath.'crontab')) {
                    $result .= $this->messages->getStyledMessage(__('Crontab file').' '.__('saved to temp directory'), 'success');
                } else {
                    $this->errorMessages['crontab_saving']=__('Crontab file').' '.__('saving failed');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Crontab backup').' '.__('skipped'), 'info');
            }

            //saving readme file to temp directory
            $readmeContent=$this->renderTeleportReadme();
            file_put_contents($this->tmpPath.'README', $readmeContent);
            if (file_exists($this->tmpPath.'README')) {
                $result .= $this->messages->getStyledMessage(__('Readme file').' '.__('saved to temp directory'), 'success');
                $result .= wf_delimiter();
                $result .= wf_tag('textarea', false, 'fileeditorarea', 'name="readmepreview" cols="145" rows="30" spellcheck="false"');
                $result .= $readmeContent;
                $result .= wf_tag('textarea', true);
            } else {
                $this->errorMessages['readme_saving']=__('Readme file').' '.__('saving failed');
            }

            //packing whole teleport package
            $this->archivePath($this->tmpPath, $this->teleportExportPath.$this->teleportDownloadName);
            if (file_exists($this->teleportExportPath.$this->teleportDownloadName)) {
                $result .= $this->messages->getStyledMessage(__('Unicorn Teleport').' '.__('exported').': '.$this->teleportExportPath.$this->teleportDownloadName, 'success');
            } else {
                $this->errorMessages['teleport_export']=__('Unicorn Teleport').' '.__('export failed');
            }

            if (empty($this->errorMessages)) {
            $teleportExportLink=wf_Link(self::URL_ME.'&'.self::ROUTE_DOWNLOAD.'=true', wf_img('skins/icon_download.png').' '.__('Download Teleport package'), false, 'ubButton');
            $result .= wf_delimiter() . $teleportExportLink;
            $exportedFileSize=filesize($this->teleportExportPath.$this->teleportDownloadName);
            $exportedFsizeLabel=round($exportedFileSize/1024/1024, 2).' Mb'; 
            log_register('UNICORNTELEPORT EXPORTED: `'.$this->teleportExportPath.$this->teleportDownloadName.'` size: '.$exportedFsizeLabel);
            } else {
                $result=$this->messages->getStyledMessage(__('Unicorn Teleport').' '.__('export failed'), 'error');
                foreach ($this->errorMessages as $key=>$value) {
                    $result .= $this->messages->getStyledMessage($key.': '.$value, 'error');
                }
                log_register('UNICORNTELEPORT EXPORT FAILED');
            }

            //cleanup temp directory
            $this->flushTempDir();
            $this->startDust->stop();
            log_register('UNICORNTELEPORT EXPORT FINISHED');
        
        return ($result);
    }

    /**
     * Renders teleport configuration form
     *
     * @return string
     */
    protected function getTeleportForm() {
        $result='';
        $archParams=array('' => '-');
        $archParams += $this->packagesAvailable;
       
        $inputs = wf_Selector(self::PROUTE_ARCH, $archParams, __('Target host architecture'),'',true);
        $inputs .= wf_TextInput(self::PROUTE_INT_IF, __('Internal interface').' '.__('on target host'),'',true,5,'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_EXT_IF, __('External interface').' '.__('on target host'),'',true,5,'alphanumeric');
        $inputs .= wf_tag('hr');
        $inputs .= wf_CheckInput(self::PROUTE_PACK_DATABASE, __('Database'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_WWWDATA, __('All www data'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_APACHE_CONF, __('Apache configuration'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_RC_CONF, __('rc.conf'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_FIREWALL_CONF, __('firewall.conf'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_STGCONFIG, __('Stargazer configuration'), true, true);
        $inputs .= wf_CheckInput(self::PROUTE_PACK_CRONTAB, __('Crontab'), true, true);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Build Teleport'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_FormDisabler();
        
        return ($result);
    }


    /**
     * Renders main teleport form with validation
     *
     * @return string
     */
    public function renderTeleportForm() { 
        $result='';
        if ($this->startDust->notRunning()) {
        if ($this->checkSysPaths()) {
            if ($this->checkTeleportData()) {
                $formSubmitted=ubRouting::checkPost(self::PROUTE_PACK_DATABASE) or ubRouting::checkPost(self::PROUTE_PACK_WWWDATA) or ubRouting::checkPost(self::PROUTE_PACK_APACHE_CONF) or ubRouting::checkPost(self::PROUTE_PACK_RC_CONF) or ubRouting::checkPost(self::PROUTE_PACK_FIREWALL_CONF) or ubRouting::checkPost(self::PROUTE_PACK_STGCONFIG) or ubRouting::checkPost(self::PROUTE_PACK_CRONTAB);
                if ($formSubmitted) {
                    $result=$this->runTeleportExport();
                } else {
                    $result=$this->getTeleportForm();
                }
                
            } else {
                $result=$this->messages->getStyledMessage(__('Teleport data is not valid'), 'error');
                foreach ($this->errorMessages as $key=>$value) {
                    $result .= $this->messages->getStyledMessage($key.': '.$value, 'error');
                }
            }
        } else {
            $result=$this->messages->getStyledMessage(__('Important system paths are not available'), 'error');
            foreach ($this->errorMessages as $path=>$value) {
                $result .= $this->messages->getStyledMessage($path.': '.$value, 'error');
            }
        }
    } else {
        $result=$this->messages->getStyledMessage(__('Teleport process is already running'), 'error');
    }
        return ($result);
    }


}