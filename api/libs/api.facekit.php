<?php

/**
 * FaceKit is a library for working with avatars
 */
class FaceKit {

    //some predefined stuff
    const PATH_ORIG = 'content/documents/facekit/';
    const PATH_DUMMY = 'skins/admava.png';
    const PATH_AVATARS = 'content/avatars/';
    const DEFAULT_EXT = 'jpg';
    const PROUTE_FILEUPLOAD = 'facekitFileUpload';
    const PROUTE_ADMINLOGIN = 'adminlogin';
    const PROUTE_STARTUPLOAD = 'facekitStartUpload';
    const ALLLOWED_EXTENSIONS = array('jpg', 'jpeg', 'png', 'gif');

    public function __construct() {
        //yare yare daze
    }


    /**
     * Returns avatar image URL
     * 
     * @param string $admLogin
     * @param int $size
     * 
     * @return string
     */
    public static function getAvatarUrl($admLogin, $size = '64') {
        $admLogin = ubRouting::filters($admLogin, 'mres');
        $size = ubRouting::filters($size, 'int');
        $avaName = $admLogin . '_' . $size . '.' . self::DEFAULT_EXT;
        if (file_exists(self::PATH_AVATARS . $avaName)) {
            $fullUrl = self::PATH_AVATARS . $avaName;
        } else {
            //need some preprocessing
            $pixelCraft = new PixelCraft();
            $origPath = self::PATH_DUMMY;
            if (file_exists(USERS_PATH . $admLogin)) {
                if (file_exists(self::PATH_ORIG . $admLogin . '.' . self::DEFAULT_EXT)) {
                    if ($pixelCraft->isImageValid(self::PATH_ORIG . $admLogin . '.' . self::DEFAULT_EXT)) {
                        $origPath = self::PATH_ORIG . $admLogin . '.' . self::DEFAULT_EXT;
                    }
                }
            }


            $pixelCraft->loadImage($origPath);
            $pixelCraft->resize($size, $size);
            $pixelCraft->saveImage(self::PATH_AVATARS . $avaName);
            $fullUrl = self::PATH_AVATARS . $avaName;
        }

        return ($fullUrl);
    }


    /**
     * Returns avatar HTML code by user login
     * 
     * @param string $admLogin
     * @param int $size
     * @param string $class
     * @param string $title
     * 
     * @return string
     */
    public static function getAvatar($admLogin, $size = '64', $class = '', $title = '') {
        $admLogin = ubRouting::filters($admLogin, 'mres');
        $size = ubRouting::filters($size, 'int');
        $fullUrl = FaceKit::getAvatarUrl($admLogin, $size);
        $result = wf_tag('img', false, $class, 'src="' . $fullUrl . '" alt="avatar" title="' . $title . '"');
        return ($result);
    }

    /**
     * Flushes avatar cache
     * 
     * @param string $admLogin
     * 
     * @return void
     */
    public function flushAvatarCache($admLogin = '') {
        $admLogin = ubRouting::filters($admLogin, 'mres');
        $allAvatars = rcms_scandir(self::PATH_AVATARS, '*.jpg');
        if (!empty($allAvatars)) {
            foreach ($allAvatars as $io => $each) {
                if (!empty($admLogin)) {
                    if (ispos($each, $admLogin)) {
                        unlink(self::PATH_AVATARS . $each);
                    }
                } else {
                    unlink(self::PATH_AVATARS . $each);
                }
            }
        }


        if (!empty($admLogin)) {
            log_register('FACEKIT AVATAR CACHE FLUSH {' . $admLogin . '}');
        } else {
            log_register('FACEKIT AVATAR CACHE FLUSH ALL');
        }
    }

    /**
     * Renders avatar upload form
     * 
     * @return string
     */
    protected function renderAvatarUploadForm($adminLogin) {
        $result = '';
        $result = wf_tag('form', false, 'photostorageuploadform', 'action="" enctype="multipart/form-data" method="POST"');
        $result .= wf_tag('input', false, '', 'type="file" name="' . self::PROUTE_FILEUPLOAD . '" accept="image/*" required');
        $result .= wf_HiddenInput(self::PROUTE_STARTUPLOAD, 'true');
        $result .= wf_HiddenInput(self::PROUTE_ADMINLOGIN, $adminLogin);
        $result .= wf_Submit(__('Upload'));
        $result .= wf_tag('form', true);
        return ($result);
    }


    /**
     * Catches avatar upload and saves it to filesystem if its valid
     *
     * @return void|string
     */
    protected function catchAvatarUpload() {
        $uploadResult = '';
        if (ubRouting::checkPost(self::PROUTE_STARTUPLOAD)) {
            $adminLogin = whoami();
            if (ubRouting::checkPost('adminlogin')) {
                if (cfr('ROOT')) {
                    $adminLogin = ubRouting::post('adminlogin');
                }
            }
            $fileAccepted = true;
            foreach ($_FILES as $file) {
                if ($file['tmp_name'] > '') {
                    if (@!in_array(end(explode(".", strtolower($file['name']))), self::ALLLOWED_EXTENSIONS)) {
                        $fileAccepted = false;
                    }
                }
            }

            if ($fileAccepted) {
                //upload successful?
                if (file_exists(@$_FILES[self::PROUTE_FILEUPLOAD]['tmp_name'])) {

                    //checking image validity
                    $pixelCraft = new PixelCraft();
                    if ($pixelCraft->isImageValid($_FILES[self::PROUTE_FILEUPLOAD]['tmp_name'])) {
                        $newFilename = $adminLogin . '.' . self::DEFAULT_EXT;
                        $newSavePath = self::PATH_ORIG . $newFilename;
                        @move_uploaded_file($_FILES[self::PROUTE_FILEUPLOAD]['tmp_name'], $newSavePath);
                        if (file_exists($newSavePath)) {
                            log_register('FACEKIT AVATAR UPLOAD SUCCESS {' . $adminLogin . '}');
                            $uploadResult = wf_tag('span', false, 'alert_success') . __('Photo upload complete') . wf_tag('span', true);
                            $this->flushAvatarCache($adminLogin);
                        } else {
                            $uploadResult = wf_tag('span', false, 'alert_error') . __('Photo upload failed') . wf_tag('span', true);
                            log_register('FACEKIT AVATAR UPLOAD FAILED {' . $adminLogin . '} FILE NOT FOUND');
                        }
                    } else {
                        $uploadResult = wf_tag('span', false, 'alert_error') . __('Photo upload failed') . ': ' . __('File') . ' ' . __('is corrupted') . wf_tag('span', true);
                        log_register('FACEKIT AVATAR UPLOAD FAILED {' . $adminLogin . '} FILE IS CORRUPTED');
                    }
                } else {
                    $uploadResult = wf_tag('span', false, 'alert_error') . __('Photo upload failed') . ': ' . __('File not found') . wf_tag('span', true);
                    log_register('FACEKIT AVATAR UPLOAD FAILED {' . $adminLogin . '} FILE NOT FOUND');
                }
            } else {
                $uploadResult = wf_tag('span', false, 'alert_error') . __('Photo upload failed') . ': ' . __('Wrong file type') . wf_tag('span', true);
                log_register('FACEKIT AVATAR UPLOAD FAILED {' . $adminLogin . '} WRONG FILE TYPE');
            }
        }
        return ($uploadResult);
    }

    /**
     * Renders avatar control form
     *
     * @param string $backUrl
     * @param string $adminLogin
     * 
     * @return string
     */
    public function renderAvatarControlForm($backUrl = '', $adminLogin = '') {
        $result = '';
        $myLogin = whoami();
        $uploadResult = $this->catchAvatarUpload();
        $previewSizes = array(256, 128, 80, 64, 32, 16);
        $previewStyle = 'style="float:left; margin:10px; border:1px solid #ccc;"';
        if (cfr('ROOT')) {
            if (!empty($adminLogin)) {
                $myLogin = $adminLogin;
            }
        }


        foreach ($previewSizes as $size) {
            $result .= wf_tag('div', false, '', $previewStyle);
            $result .= FaceKit::getAvatar($myLogin, $size);
            $result .= wf_delimiter(0);
            $result .= $size . 'x' . $size;
            $result .= wf_tag('div', true);
        }
        $result .= wf_CleanDiv();
        $result .= $this->renderAvatarUploadForm($myLogin);
        if ($uploadResult) {
            $result .= wf_delimiter();
            $result .= $uploadResult;
        }

        if ($backUrl) {
            $backUrl = base64_decode($backUrl);
            $result .= wf_delimiter();
            $result .= wf_BackLink($backUrl, __('Back'), false, 'ubButton');
        }

        return ($result);
    }
}
