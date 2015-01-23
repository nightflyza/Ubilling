<?php

class PhotoStorage {

    protected $photoCfg = array();
    protected $altCfg = array();
    protected $allimages = array();
    protected $scope = '';
    protected $itemId = '';
    protected $myLogin = '';

    const STORAGE_PATH = 'content/documents/photostorage/';
    const UPLOAD_URL = '?module=photostorage&uploadcamphoto=true';
    const MODULE_URL = '?module=photostorage';
    const EX_NOSCOPE = 'NO_OBJECT_SCOPE_SET';

    public function __construct($scope = '', $itemid = '') {
        $this->loadConfig();
        $this->loadAlter();
        $this->setScope($scope);
        $this->setItemid($itemid);
        $this->setLogin();
    }

    /**
     * Object scope setter
     * 
     * @param string $scope Object actual scope
     * 
     * @return void
     */
    protected function setScope($scope) {
        $this->scope = mysql_real_escape_string($scope);
    }

    /**
     * Object scope item Id setter
     * 
     * @param string $scope Object actual scope
     * 
     * @return void
     */
    protected function setItemid($itemid) {
        $this->itemId = mysql_real_escape_string($itemid);
    }

    /**
     * Loads system photostorage config into private prop
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->photoCfg = $ubillingConfig->getPhoto();
        $this->altCfg = $ubillingConfig->getAlter();
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
     * Administrator login setter
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads images list from database into private prop
     * 
     * @return void
     */
    protected function loadAllImages() {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $query = "SELECT * from `photostorage`;";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->allimages[$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Registers uploaded image in database
     * 
     * @param string $filename
     */
    protected function registerImage($filename) {
        if ((!empty($this->scope)) AND ( !empty($this->itemId))) {
            $filename = mysql_real_escape_string($filename);
            $date = curdatetime();
            $query = "INSERT INTO `photostorage` (`id`, `scope`, `item`, `date`, `admin`, `filename`) "
                    . "VALUES (NULL, '" . $this->scope . "', '" . $this->itemId . "', '" . $date . "', '" . $this->myLogin . "', '" . $filename . "'); ";
            nr_query($query);
            log_register('PHOTOSTORAGE CREATE SCOPE `' . $this->scope . '` ITEM [' . $this->itemId . ']');
        }
    }
    
    
    /**
     * Returns basic image controls
     * 
     * @param int $imageId existing image ID
     * @return string
     */
    protected function imageControls($imageId) {
        $result=wf_tag('br');
        $downloadUrl=self::MODULE_URL . '&scope=' . $this->scope. '&itemid=' . $this->itemId . '&download=' . $imageId;
        $result.= wf_Link($downloadUrl, wf_img('skins/icon_download.png', __('Download')), false, '');
        $deleteUrl=self::MODULE_URL . '&scope=' . $this->scope. '&itemid=' . $this->itemId . '&delete=' . $imageId;
        $result.= wf_AjaxLink($deleteUrl, web_delete_icon(), 'ajRefCont_'.$imageId, false, '');
        return ($result);    
    }


        /**
     * Returns current scope/item images list
     * 
     * @return string
     */
    public function renderImagesList() {
        if (empty($this->allimages)) {
            $this->loadAllImages();
        }
        
        $result = '<div>';
        $result.= wf_AjaxLoader();

        if (!empty($this->allimages)) {
            foreach ($this->allimages as $io => $eachimage) {
                if (($eachimage['scope']==$this->scope) AND ($eachimage['item']==$this->itemId)) {
                $imgPreview = wf_img_sized(self::STORAGE_PATH . $eachimage['filename'], __('Show'), $this->photoCfg['IMGLIST_PREV_W'], $this->photoCfg['IMGLIST_PREV_H']);
                $imgFull = wf_img(self::STORAGE_PATH . $eachimage['filename']);
                
                $dimensions= 'width:'.($this->photoCfg['IMGLIST_PREV_W']+10).'px;';
                $dimensions.='height:'.($this->photoCfg['IMGLIST_PREV_H']+10).'px;';
                $result.=wf_tag('div',false,'','style="float:left; '.$dimensions.' padding:10px;" id="ajRefCont_'.$eachimage['id'].'"');
                $result.=wf_modalAuto($imgPreview, __('Image') . ' ' . $eachimage['id'], $imgFull, '');
                $result.=$this->imageControls($eachimage['id']);
                $result.=wf_tag('div',true);
                }
            }
        }
        
        $result.='</div>';
        return ($result);
    }

    /**
     * Downloads image file by its id
     * 
     * @param int $id database image ID
     */
    public function catchDownloadImage($id) {
        $id=vf($id,3);
        if (empty($this->allimages)) {
            $this->loadAllImages();
        }
        if (!empty($id)) {
            @$filename = $this->allimages[$id]['filename'];
            if (file_exists(self::STORAGE_PATH . $filename)) {
                zb_DownloadFile(self::STORAGE_PATH . $filename,'jpg');
            } else {
                show_error(__('File not exist'));
            }
        } else {
            show_error(__('Image not exists'));
        }
    }
    
     /**
     * deletes image from database and FS by its ID
     * 
     * @param int $id database image ID
     */
    public function catchDeleteImage($id) {
        $id=vf($id,3);
        if (empty($this->allimages)) {
            $this->loadAllImages();
        }
        if (!empty($id)) {
            @$filename = $this->allimages[$id]['filename'];
            if (file_exists(self::STORAGE_PATH . $filename)) {
                //deleting file here
                $deleteResult= wf_tag('span', false, 'alert_warning') . __('Deleted') . wf_tag('span', true);
            } else {
                 $deleteResult= wf_tag('span', false, 'alert_error') . __('File not exist'). wf_tag('span', true);
            }
        } else {
            $deleteResult= wf_tag('span', false, 'alert_error') .__('Image not exists'). wf_tag('span', true);
        }
        die($deleteResult);
    }
    
    
    /**
     * Catches webcam snapshot upload in background
     * 
     * @return void
     */
    public function catchWebcamUpload() {
        if (wf_CheckGet(array('uploadcamphoto'))) {
            if (!empty($this->scope)) {
                $newWebcamFilename = zb_rand_string(16) . '_webcam.jpg';
                $newWebcamSavePath = self::STORAGE_PATH . $newWebcamFilename;
                move_uploaded_file($_FILES['webcam']['tmp_name'], $newWebcamSavePath);
                if (file_exists($newWebcamSavePath)) {
                    $uploadResult = wf_tag('span', false, 'alert_success') . __('Photo upload complete') . wf_tag('span', true);
                    $this->registerImage($newWebcamFilename);
                } else {
                    $uploadResult = wf_tag('span', false, 'alert_error') . __('Photo upload failed') . wf_tag('span', true);
                }
            } else {
                $uploadResult = wf_tag('span', false, 'alert_error') . __('Strange exeption') . ': ' . self::EX_NOSCOPE . wf_tag('span', true);
            }
            die($uploadResult);
        }
    }

    /**
     * Returns webcamera snapshot form
     * 
     * @param bool $avatarMode use crop by WEBCAM_AVA_CROP property
     * 
     * @return string
     */
    public function renderWebcamForm($avatarMode = false) {
        $container = wf_tag('div', false, '', 'id="cameraContainer"');
        $container.= wf_tag('div', true);
        $container.= wf_tag('script', false, '', 'type="text/javascript" src="modules/jsc/webcamjs/webcam.min.js"');
        $container.= wf_tag('script', true);

        if ($avatarMode) {
            $cropControls = 'crop_width: ' . $this->photoCfg['WEBCAM_AVA_CROP'] . ',
                           crop_height: ' . $this->photoCfg['WEBCAM_AVA_CROP'] . ',';
            $prev_w = $this->photoCfg['WEBCAM_PREV_W'];
            $prev_h = $this->photoCfg['WEBCAM_PREV_H'];
            $dest_w = $this->photoCfg['WEBCAM_RESULT_W'];
            $dest_h = $this->photoCfg['WEBCAM_RESULT_H'];
        } else {
            $cropControls = '';
            $prev_w = $this->photoCfg['WEBCAM_PREV_W'];
            $prev_h = $this->photoCfg['WEBCAM_PREV_H'];
            $dest_w = $this->photoCfg['WEBCAM_RESULT_W'];
            $dest_h = $this->photoCfg['WEBCAM_RESULT_H'];
        }

        $init = wf_tag('script', false, '', 'language="JavaScript"');
        $init.= '	Webcam.set({
                        ' . $cropControls . '
			width: ' . $prev_w . ',
			height: ' . $prev_h . ',
			dest_width: ' . $dest_w . ',
			dest_height: ' . $dest_h . ',
			image_format: \'' . $this->photoCfg['WEBCAM_FORMAT'] . '\',
			jpeg_quality: ' . $this->photoCfg['WEBCAM_JPEG_QUALITY'] . ',
                        force_flash: ' . $this->photoCfg['WEBCAM_FORCE_FLASH'] . '
		});
		Webcam.attach( \'#cameraContainer\' );';
        $init.= wf_tag('script', true);

        $uploadJs = wf_tag('script', false, '', 'language="JavaScript"');
        $uploadJs.='	var shutter = new Audio();
                        shutter.autoplay = false;
                        shutter.src = navigator.userAgent.match(/Firefox/) ? \'modules/jsc/webcamjs/shutter.ogg\' : \'modules/jsc/webcamjs/shutter.mp3\';
                        function take_snapshot() {
                                shutter.play();
                                Webcam.snap( function(data_uri) {
                                        document.getElementById(\'webcamResults\').innerHTML = 
                                                \'<img src="\'+data_uri+\'" width=' . $prev_w . ' height=' . $prev_h . ' />\';

                                                var url = \'' . self::UPLOAD_URL . '&scope=' . $this->scope . '&itemid=' . $this->itemId . '\';
                                                Webcam.upload( data_uri, url, function(code, text) {

                                            } );
                                        } );
                               Webcam.on( \'uploadProgress\', function(progress) {
                               document.getElementById(\'uploadProgress\').innerHTML=\'<img src="skins/ajaxloader.gif">\';
                               } );
                               Webcam.on( \'uploadComplete\', function(code, text) {
                                document.getElementById(\'uploadProgress\').innerHTML=text;
                               } );
                            }';
        $uploadJs.=wf_tag('script', true);

        $form = wf_tag('br');
        $form.= wf_tag('form', false);
        $form.= wf_tag('input', false, '', 'type=button value="' . __('Take snapshot') . '" onClick="take_snapshot()"');
        $form.= wf_tag('form', true);

        $preview = wf_tag('div', false, '', 'id="webcamResults"');
        $preview.= wf_tag('div', true);

        $uploadProgress = wf_tag('div', false, '', 'id="uploadProgress"');
        $uploadProgress.= wf_tag('div', true);

        $cells = wf_TableCell($container . $init . $form . $uploadJs, '50%', '', 'valign="top"');
        $cells.= wf_TableCell($preview, '', '', 'valign="top"');
        $rows = wf_TableRow($cells);

        $result = wf_TableBody($rows, '100%', 0);
        $result.= $uploadProgress;


        return ($result);
    }

}

?>