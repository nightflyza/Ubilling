<?php

/**
 * Allows to attach files to random items in some scope
 */
class FileStorage {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of available files in database as id=>filedata
     *
     * @var array
     */
    protected $allFiles = array();

    /**
     * Contains loaded files count for each item in some scope as scope=>itemid=>count
     *
     * @var array
     */
    protected $filesCount = array();

    /**
     * Contains current filestorage items scope
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Contains current instance item ID in the current scope
     *
     * @var string
     */
    protected $itemId = '';

    /**
     * Contains current administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Flag for preventing multiple database requests when building files count map
     *
     * @var bool
     */
    protected $filesLoadedFlag = false;

    /**
     * Current instance database abstraction layer placeholder
     *
     * @var object
     */
    protected $storageDb = '';

    /**
     * Contains default file preview container size in px
     *
     * @var int
     */
    protected $filePreviewSize = 128;

    /**
     * Contains allowed file extensions. May be configurable in future.
     *
     * @var array
     */
    protected $allowedExtensions = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains files storage path. May be specified in FILESTORAGE_DIRECTORY option.
     *
     * @var string
     */
    protected $storagePath = 'content/documents/filestorage/';

    /**
     * Some predefined paths and URLs
     */
    const TABLE_STORAGE = 'filestorage';
    const URL_ME = '?module=filestorage';
    const URL_UPLOAD_FILE = '?module=filestorage&uploadfile=true';
    const EX_NOSCOPE = 'NO_OBJECT_SCOPE_SET';
    const EX_WRONG_EXT = 'WRONG_FILE_EXTENSION';

    /**
     * Initializes filestorage engine for some scope/item id
     * 
     * @param string $scope
     * @param string $itemid
     * 
     * @return void
     */
    public function __construct($scope = '', $itemid = '') {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->setAllowedExtenstions();
        $this->setScope($scope);
        $this->setItemid($itemid);
        $this->setLogin();
        $this->initDatabase();
    }

    /**
     * Inits system message helper for further usage
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads system alter config into private prop
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some current instance specific options.
     *
     * @return void
     */
    protected function setOptions() {
        if (@$this->altCfg['FILESTORAGE_DIRECTORY']) {
            $this->storagePath = $this->altCfg['FILESTORAGE_DIRECTORY'];
        }
    }

    /**
     * Returns configured files storage directory path 
     *
     * @return string
     */
    public function getStoragePath() {
        $result = $this->storagePath;
        return ($result);
    }

    /**
     * Sets allowed file extensions for this instance
     * 
     * @return void
     */
    protected function setAllowedExtenstions() {
        $this->allowedExtensions = array(
            'jpg',
            'gif',
            'png',
            'jpeg',
            'dia',
            'xls',
            'xlsx',
            'doc',
            'odt',
            'ods',
            'docx',
            'pdf',
            'txt',
            'mp3',
            'gsm',
            'conf',
            'mp4',
            'mpg',
            'mpeg',
            'avi',
            'ogg',
            'zip',
            'rar',
            'tar',
            'gz',
            'tgz',
            'bz2',
            '7z',
            'sql',
            'dbf',
            'csv',
        );
        $this->allowedExtensions = array_flip($this->allowedExtensions); //extension string => index
    }

    /**
     * Object scope setter
     * 
     * @param string $scope Object actual scope
     * 
     * @return void
     */
    protected function setScope($scope) {
        $this->scope = ubRouting::filters($scope, 'mres');
    }

    /**
     * Object scope item Id setter
     * 
     * @param string $itemid Object actual id in current scope
     * 
     * @return void
     */
    public function setItemid($itemid) {
        $this->itemId = ubRouting::filters($itemid, 'mres');
    }

    /**
     * Administrator login setter
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Inits protected database absctaction layer for current instance
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->storageDb = new NyanORM(self::TABLE_STORAGE);
    }

    /**
     * Loads files list from database into private prop
     * 
     * @return void
     */
    protected function loadAllFiles() {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $this->allFiles = $this->storageDb->getAll('id');
            $this->filesLoadedFlag = true;
            if (!empty($this->allFiles)) {
                foreach ($this->allFiles as $io => $eachFile) {
                    if (isset($this->filesCount[$eachFile['scope']][$eachFile['item']])) {
                        $this->filesCount[$eachFile['scope']][$eachFile['item']]++;
                    } else {
                        $this->filesCount[$eachFile['scope']][$eachFile['item']] = 1;
                    }
                }
            }
        }
    }

    /**
     * Registers uploaded file in database
     * 
     * @param string $filename
     * @param string $origname optional original client file name for download display
     * 
     * @return void
     */
    public function registerFile($filename, $origname = '') {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $filename = ubRouting::filters($filename, 'mres');
            $orignameStored = '';
            if (!empty($origname)) {
                $orignameStored = ubRouting::filters($origname, 'safe');
                $orignameStored = ubRouting::filters($orignameStored, 'mres');
            }
            $date = curdatetime();

            $this->storageDb->data('scope', $this->scope);
            $this->storageDb->data('item', $this->itemId);
            $this->storageDb->data('date', $date);
            $this->storageDb->data('admin', $this->myLogin);
            $this->storageDb->data('filename', $filename);
            $this->storageDb->data('origname', $orignameStored);
            $this->storageDb->create();

            log_register('FILESTORAGE CREATE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

    /**
     * Deletes uploaded file from database
     * 
     * @param int $fileId
     * 
     * @return void
     */
    protected function unregisterFile($fileId) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $fileId = ubRouting::filters($fileId, 'int');
            $date = curdatetime();

            $this->storageDb->where('id', '=', $fileId);
            $this->storageDb->delete();

            log_register('FILESTORAGE DELETE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }

    /**
     * Returns basic file controls
     * 
     * @param int $fileId existing file ID
     * 
     * @return string
     */
    protected function fileControls($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');
        $result = wf_tag('br');
        $downloadUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&download=' . $fileId;

        $result .= wf_Link($downloadUrl, wf_img('skins/icon_download.png') . ' ' . __('Download'), false, 'ubButton') . ' ';
        if (cfr('FILESTORAGEDELETE')) {
            $deleteUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&delete=' . $fileId;
            $result .= wf_AjaxLink($deleteUrl, web_delete_icon() . ' ' . __('Delete'), 'ajRefCont_' . $fileId, false, 'ubButton') . ' ';
        }
        return ($result);
    }

    /**
     * Returns file upload controls
     * 
     * @return string
     */
    public function uploadControlsPanel() {
        $result = '';
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $callBackUrl = '';
            if (ubRouting::checkGet('callback')) {
                $callBackUrl = '&callback=' . ubRouting::get('callback');
            }
            $controlUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader' . $callBackUrl;
            $result .= wf_Link($controlUrl, wf_img('skins/photostorage_upload.png') . ' ' . __('Upload file from HDD'), false, 'ubButton');
        }

        return ($result);
    }

    /**
     * Returns custom module backlinks for some scopes
     * 
     * @param string $customBackLinkURL
     * 
     * @return string
     */
    protected function backUrlHelper($customBackLinkURL = '') {
        $result = '';
        if ($this->scope == 'USERPROFILE') {
            $result = web_UserControls($this->itemId);
        }

        if ($this->scope == 'USERCONTRACT') {
            if (ubRouting::checkGet('callback')) {
                $result = wf_BackLink('?module=swcash&renderswusers=' . ubRouting::get('callback', 'int'));
            } else {
                $result = wf_BackLink('?module=contractedit&username=' . $this->itemId);
            }
        }

        if ($this->scope == SwitchCash::FILESTORAGE_SCOPE) {
            $switchId = ubRouting::filters($this->itemId, 'int');
            $result = wf_BackLink(SwitchCash::URL_ME . '&' . SwitchCash::ROUTE_EDIT . '=' . $switchId);
        }

        if ($this->scope == 'WAREHOUSEINCOME') {
            $incomeId = ubRouting::filters($this->itemId, 'int');
            $result = wf_BackLink(Warehouse::URL_ME . '&' . Warehouse::URL_VIEWERS . '&showinid=' . $incomeId);
        }

        if ($this->scope == 'CFITEMS') {
            $cleanLogin = explode(CustomFields::FILESTORAGE_ITEMID_DELIMITER, $this->itemId);
            $cleanLogin = $cleanLogin[0];
            $result = wf_BackLink(CustomFields::URL_EDIT_BACK . $cleanLogin);
        }

        if (ubRouting::checkGet('callback') and empty($result)) {
            $result = wf_BackLink(base64_decode(ubRouting::get('callback')));
        }

        if (!empty($customBackLinkURL)) {
            $result = wf_BackLink($customBackLinkURL);
        }

        return ($result);
    }

    /**
     * Returns count of loaded files for some itemid in current scope
     *
     * @param string $itemId
     *
     * @return int
     */
    public function getFilesCount($itemId) {
        $result = 0;
        $this->itemId = $itemId;
        if (!$this->filesLoadedFlag) {
            $this->loadAllFiles();
        }

        if (isset($this->filesCount[$this->scope][$itemId])) {
            $result = $this->filesCount[$this->scope][$itemId];
        }
        return ($result);
    }

    /**
     * Returns indicator of files count for some itemId in current scope
     *
     * @param string $itemId
     * @param string $size
     *
     * @return string
     */
    public function getFilesIndicator($itemId, $size = '') {
        $result = '';
        $filesCountVal = $this->getFilesCount($itemId);
        if ($filesCountVal > 0) {
            $size = (!$size) ? 16 : $size;
            $result = wf_img_sized('skins/filestorage16.png', __('Filestorage') . ' (' . $filesCountVal . ')', $size, $size);
        }
        return ($result);
    }

    /**
     * Renders file preview icon
     * 
     * @param string $filename stored file name (used for icon type by extension and default img title)
     * @param int|string $size
     * @param string $origname optional original name for img title when non-empty
     * 
     * @return string
     */
    protected function renderFilePreviewIcon($filename, $size = '', $origname = '') {
        $result = '';
        if (!empty($filename)) {
            $fileTypeIconsPath = 'skins/fileicons/';
            $extension = pathinfo(strtolower($filename), PATHINFO_EXTENSION);
            $fileTypeIcon = 'skins/fileicons/package.png';
            $customTypeIcon = $fileTypeIconsPath . $extension . '.png';
            if (file_exists($customTypeIcon)) {
                $fileTypeIcon = $customTypeIcon;
            }

            $imgTitle = $filename;
            if (!empty($origname)) {
                $imgTitle = $origname;
            }

            //custom icon size
            if ($size) {
                $result .= wf_img_sized($customTypeIcon, $imgTitle, $size);
            } else {
                $result .= wf_img($fileTypeIcon, $imgTitle);
            }
        }
        return($result);
    }

    /**
     * Renders attached files preview with optional navigation button
     * 
     * @param bool $navButton
     * @param string $navbuttonText
     * @param string $navbuttonClass
     * @param int $iconSize
     * @param string $urlAppend
     * @param bool $targetBlank
     * 
     * @return string
     */
    public function renderFilesPreview($navButton = false, $navbuttonText = '', $navbuttonClass = 'ubButton', $iconSize = '32', $urlAppend = '', $targetBlank = false) {
        $result = '';

        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }

        $result .= wf_tag('div', false, '', '');

        if ($navButton) {
            $result .= $this->renderNavigationButton($navbuttonText, $navbuttonClass, $urlAppend, $targetBlank);
        }

        if (!empty($this->allFiles)) {
            foreach ($this->allFiles as $io => $eachFile) {
                if (($eachFile['scope'] == $this->scope) AND ( $eachFile['item'] == $this->itemId)) {
                    $fileOrigname = '';
                    if (isset($eachFile['origname']) AND ( !empty($eachFile['origname']))) {
                        $fileOrigname = $eachFile['origname'];
                    }
                    $result .= wf_tag('div', false, '', 'style="border: 0px dotted; float:left; margin:2px;"');
                    $result .= wf_tag('center');
                    if (cfr('FILESTORAGE')) {
                        $fileDownloadUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&download=' . $eachFile['id'];
                        $result .= wf_Link($fileDownloadUrl, $this->renderFilePreviewIcon($eachFile['filename'], $iconSize, $fileOrigname));
                    } else {
                        $result .= $this->renderFilePreviewIcon($eachFile['filename'], $iconSize, $fileOrigname);
                    }
                    $result .= wf_tag('center', true);
                    $result .= wf_tag('div', true);
                }
            }
        }
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return($result);
    }

    /**
     * Renders link to filestorage file list (upload/management) for current scope/item, without file previews
     *
     * @param string $buttonText
     * @param string $buttonClass
     * @param string $urlAppend appended to default list URL (same as renderFilesPreview)
     * @param bool $targetBlank
     *
     * @return string
     */
    public function renderNavigationButton($buttonText = '', $buttonClass = 'ubButton', $urlAppend = '', $targetBlank = false) {
        $result = '';
        $target = ($targetBlank ? ' target="_blank" ' : '');
        $mgmtUrl = self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=list' . $urlAppend;
        $result .= wf_Link($mgmtUrl, wf_img('skins/photostorage_upload.png', __('Upload')) . $buttonText, false, $buttonClass, $target);
        return ($result);
    }

    /**
     * Returns current scope/item files list
     * 
     * @return string
     */
    public function renderFilesList($customBackLinkURL = '') {
        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }

        $result = wf_AjaxLoader();

        if (!empty($this->allFiles)) {
            foreach ($this->allFiles as $io => $eachFile) {
                if (($eachFile['scope'] == $this->scope) AND ( $eachFile['item'] == $this->itemId)) {
                    $fileOrigname = '';
                    if (isset($eachFile['origname']) AND ( !empty($eachFile['origname']))) {
                        $fileOrigname = $eachFile['origname'];
                    }
                    $dimensions = 'width:' . ($this->filePreviewSize + 220) . 'px;';
                    $dimensions .= 'height:' . ($this->filePreviewSize + 60) . 'px;';
                    $result .= wf_tag('div', false, '', 'style="border: 1px dotted; float:left;  ' . $dimensions . ' margin:15px;" id="ajRefCont_' . $eachFile['id'] . '"');
                    $result .= wf_tag('center');
                    $result .= $this->renderFilePreviewIcon($eachFile['filename'], '', $fileOrigname);
                    $result .= $this->fileControls($eachFile['id']);
                    $result .= wf_tag('center', true);
                    $result .= wf_tag('div', true);
                }
            }
        }

        $result .= wf_CleanDiv();
        $result .= wf_delimiter();
        $result .= $this->backUrlHelper($customBackLinkURL);
        return ($result);
    }

    /**
     * Downloads file by its id
     * 
     * @param int $fileId database file ID
     * 
     * @return void
     */
    public function catchDownloadFile($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');

        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }
        if (!empty($fileId)) {
            @$filename = $this->allFiles[$fileId]['filename'];
            $downloadAs = '';
            if (!empty($this->allFiles[$fileId]['origname'])) {
                $downloadAs = $this->allFiles[$fileId]['origname'];
            }
            if (file_exists($this->storagePath . $filename)) {
                zb_DownloadFile($this->storagePath . $filename, 'default', $downloadAs);
            } else {
                show_error(__('File not exist'));
                log_register('FILESTORAGE DOWNLOAD FAILED `' . $this->storagePath . $filename.'` NOT_EXIST');
            }
        } else {
            show_error(__('File not exists'));
        }
    }

    /**
     * Deletes file from database and FS by its ID
     * 
     * @param int $fileId database file ID
     * 
     * @return void
     */
    public function catchDeleteFile($fileId) {
        $fileId = ubRouting::filters($fileId, 'int');

        if (empty($this->allFiles)) {
            $this->loadAllFiles();
        }
        if (!empty($fileId)) {
            @$filename = $this->allFiles[$fileId]['filename'];
            if (file_exists($this->storagePath . $filename)) {
                if (cfr('FILESTORAGEDELETE')) {
                    unlink($this->storagePath . $filename);
                    $this->unregisterFile($fileId);
                    $deleteResult = $this->messages->getStyledMessage(__('Deleted'), 'warning');
                } else {
                    $deleteResult = $this->messages->getStyledMessage(__('Access denied'), 'error');
                }
            } else {
                $deleteResult = $this->messages->getStyledMessage(__('File not exist') . ': ' . $filename, 'error');
            }
        } else {
            $deleteResult = $this->messages->getStyledMessage(__('File not exist') . ': [' . $fileId . ']', 'error');
        }
        die($deleteResult);
    }

    /**
     * Catches file upload in background
     * 
     * @return void
     */
    public function catchFileUpload() {
        if (ubRouting::checkGet('uploadfile')) {
            $callBackUrl = '';
            if (ubRouting::checkGet('callback')) {
                $callBackUrl = '&callback=' . ubRouting::get('callback');
            }
            if (!empty($this->scope)) {
                $fileAccepted = true;
                foreach ($_FILES as $file) {
                    if ($file['tmp_name'] > '') {
                        $uploadedFileExtension = pathinfo(strtolower($file['name']), PATHINFO_EXTENSION);

                        if (!isset($this->allowedExtensions[$uploadedFileExtension])) {
                            $fileAccepted = false;
                        }
                    }
                }

                if ($fileAccepted) {
                    $originalFileName = zb_TranslitString($file['name']); //prevent cyrillic filenames on FS
                    $newFilename = zb_rand_string(6) . '_' . $originalFileName;
                    $newSavePath = $this->storagePath . $newFilename;
                    $clientOriginalName = '';
                    if (isset($_FILES['filestorageFileUpload']['name'])) {
                        $clientOriginalName = $_FILES['filestorageFileUpload']['name'];
                    }
                    @move_uploaded_file($_FILES['filestorageFileUpload']['tmp_name'], $newSavePath);
                    if (file_exists($newSavePath)) {
                        $uploadResult = $this->messages->getStyledMessage(__('File upload complete'), 'success');
                        $this->registerFile($newFilename, $clientOriginalName);
                        ubRouting::nav(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader&uldd=1' . $callBackUrl);
                    } else {
                        $uploadResult = $this->messages->getStyledMessage(__('File upload failed'), 'error');
                    }
                } else {
                    $uploadResult = $this->messages->getStyledMessage(__('File upload failed') . ': ' . self::EX_WRONG_EXT, 'error');
                }
            } else {
                $uploadResult = $this->messages->getStyledMessage(__('Strange exeption') . ': ' . self::EX_NOSCOPE, 'error');
            }

            show_window('', $uploadResult);
            show_window('', wf_BackLink(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=loader' . $callBackUrl));
        }
    }

    /**
     * Returns file upload form
     * 
     * @return string
     */
    public function renderUploadForm() {
        $callBackUrl = '';
        if (ubRouting::checkGet('callback')) {
            $callBackUrl = '&callback=' . ubRouting::get('callback');
        }
        $postUrl = self::URL_UPLOAD_FILE . '&scope=' . $this->scope . '&itemid=' . $this->itemId . $callBackUrl;
        $inputs = wf_tag('form', false, 'photostorageuploadform', 'action="' . $postUrl . '" enctype="multipart/form-data" method="POST"');
        $inputs .= wf_tag('input', false, '', 'type="file" name="filestorageFileUpload"');
        $inputs .= wf_Submit(__('Upload').' '.__('file'));
        $inputs .= wf_tag('form', true);

        $result = $inputs;
        $result .= wf_delimiter(2);
        if (ubRouting::checkGet('uldd')) {
            $result .= $this->messages->getStyledMessage(__('File upload complete'), 'success');
            $result .= wf_delimiter();
        }
        $callBackUrl = '';
        if (ubRouting::checkGet('callback')) {
            $callBackUrl = '&callback=' . ubRouting::get('callback');
        }
        $result .= wf_BackLink(self::URL_ME . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '&mode=list' . $callBackUrl);
        return ($result);
    }

}
