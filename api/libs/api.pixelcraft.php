<?php

/**
 * PixelCraft is a lightweight PHP library designed for easy image processing using the GD lib. 
 * With PixelCraft, you can perform basic image operations such as resizing, cropping, drawing of watermarks, and format conversion.
 * The library is characterized by its user-friendly interface and minimal code footprint, 
 * allowing for quick and efficient image processing in PHP projects.
 * 
 * @package PixelCraft
 * @author Rostyslav Haitkulov <info@ubilling.net.ua>
 * @see https://github.com/nightflyza/PixelCraft
 * @license MIT
 */
class PixelCraft {

    /**
     * Contains image copy to perform some magic on it
     * 
     * @var GDimage
     */
    protected $image = '';

    /**
     * Contains image rendering quality
     * 
     * @var int
     */
    protected $quality = -1;

    /**
     * Contains loaded image mime type
     * 
     * @var string
     */
    protected $imageType = '';

    /**
     * Contains loaded image original width
     * 
     * @var int
     */
    protected $imageWidth = 0;

    /**
     * Contains loaded image original height
     * 
     * @var int
     */
    protected $imageHeight = 0;

    /**
     * Contains array of custom RGB colors palette as name=>color
     * 
     * @var array
     */
    protected $colorPalette = array();

    /**
     * Contains already allocated image colors as name=>colorInt
     * 
     * @var array
     */
    protected $colorsAllocated = array();

    /**
     * TTF font path
     * 
     * @var string
     */
    protected $font = 'skins/OpenSans-Regular.ttf';

    /**
     * Font size in pt.
     * 
     * @var int
     */
    protected $fontSize = 10;

    /**
     * Drawing line width in px
     * 
     * @var int
     */
    protected $lineWidth = 1;

    /**
     * Contains watermark image copy
     * 
     * @var GDimage
     */
    protected $watermark = '';

    /**
     * Contains currently loaded brush image
     * 
     * @var GDimage
     */
    protected $brush = '';

    /**
     * Schweigen im wald
     */
    public function __construct() {
        $this->setDefaultColors();
    }

    /**
     * Sets few default colors to palette
     * 
     * @return void
     */
    protected function setDefaultColors() {
        $this->addColor('white', 255, 255, 255);
        $this->addColor('black', 0, 0, 0);
        $this->addColor('red', 255, 0, 0);
        $this->addColor('green', 0, 255, 0);
        $this->addColor('blue', 0, 0, 255);
        $this->addColor('yellow', 255, 255, 0);
        $this->addColor('grey', 85, 85, 85);
    }

    /**
     * Sets current instance image save/render quality
     * 
     * @param int $quality Deafult -1 means IJG quality value for jpeg or default zlib for png
     * 
     * @return void
     */
    public function setQuality($quality) {
        if (is_int($quality)) {
            $this->quality = $quality;
        }
    }

    /**
     * Set TTF font path
     *
     * @param  string  $font  TTF font path
     *
     * @return  void
     */
    public function setFont($font) {
        $this->font = $font;
    }

    /**
     * Set font size in pt.
     *
     * @param  int  $fontSize  Font size in pt.
     *
     * @return  void
     */
    public function setFontSize($fontSize) {
        $this->fontSize = $fontSize;
    }

    /**
     * Returns current image width
     * 
     * @return int
     */
    public function getImageWidth() {
        return ($this->imageWidth);
    }

    /**
     * Returns current image height
     * 
     * @return int
     */
    public function getImageHeight() {
        return ($this->imageHeight);
    }

    /**
     * Returns loaded image type
     * 
     * @return string
     */
    public function getImageType() {
        return ($this->imageType);
    }

    /**
     * Returns specified image 
     * 
     * @param string $filePath
     * 
     * @return array
     */
    protected function getImageParams($filePath) {
        return (@getimagesize($filePath));
    }

    /**
     * Creates or replaces RGB color in palette
     * 
     * @param string $colorName
     * @param int $r
     * @param int $g
     * @param int $b
     * 
     * @return void
     */
    public function addColor($colorName, $r, $g, $b) {
        $this->colorPalette[$colorName] = array('r' => $r, 'g' => $g, 'b' => $b);
    }

    /**
     * Checks is some image valid?
     * 
     * @param string $filePath
     * @param array $imageParams
     * 
     * @return bool
     */
    public function isImageValid($filePath = '', $imageParams = array()) {
        $result = false;
        if (empty($imageParams) and !empty($filePath)) {
            $imageParams = $this->getImageParams($filePath);
        }

        if (is_array($imageParams)) {
            if (isset($imageParams['mime'])) {
                if (strpos($imageParams['mime'], 'image/') !== false) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns image type name
     * 
     * @param string $filePath
     * @param array $imageParams
     * 
     * @return string
     */
    protected function detectImageType($filePath = '', $imageParams = array()) {
        $result = '';
        if ($this->isImageValid($filePath, $imageParams)) {
            if (empty($imageParams) and !empty($filePath)) {
                $imageParams = $this->getImageParams($filePath);
            }

            if (is_array($imageParams)) {
                $imageType = $imageParams['mime'];
                $imageType = str_replace('image/', '', $imageType);
                $result = $imageType;
            }
        }
        return ($result);
    }

    /**
     * Loads some image into protected property from file 
     * 
     * @param string $fileName readable image file path

     * @return bool
     */
    protected function loadImageFile($filePath, $propertyName = 'image') {
        $result = false;
        $imageParams = $this->getImageParams($filePath);
        $imageType = $this->detectImageType('', $imageParams);
        if (!empty($imageType)) {
            $loaderFunctionName = 'imagecreatefrom' . $imageType;
            if (function_exists($loaderFunctionName)) {
                $this->$propertyName = $loaderFunctionName($filePath);
                //setting loaded image base props
                if ($this->$propertyName != false) {
                    if ($propertyName == 'image') {
                        $this->imageWidth = $imageParams[0];
                        $this->imageHeight = $imageParams[1];
                        $this->imageType = $imageType;
                    }
                    $result = true;
                }
            } else {
                throw new Exception('EX_NOT_SUPPORTED_FILETYPE:' . $imageType);
            }
        }
        return ($result);
    }

    /**
     * Loads some image into protected property from file 
     * 
     * @param string $fileName readable image file path

     * @return bool
     */
    public function loadImage($filePath) {
        $result = $this->loadImageFile($filePath, 'image');
        return ($result);
    }


    /**
     * Loads a instance image from an base64 encoded image string.
     *
     * @param string $encodedImage The encoded image string.
     * @param string $propertyName The name of the property to store the image in. Default is 'image'.
     * 
     * @return bool Returns true if the base image is successfully loaded, false otherwise.
     */
    public function loadBaseImage($encodedImage, $propertyName = 'image') {
        $result = false;
        if (!empty($encodedImage)) {
            $decodedImage = base64_decode($encodedImage);
            if ($decodedImage) {
                $imageParams = getimagesizefromstring($decodedImage);
                $imageType = $this->detectImageType('', $imageParams);

                if (!empty($imageType)) {
                    $this->$propertyName = imagecreatefromstring($decodedImage);
                    if ($this->$propertyName != false) {
                        if ($propertyName == 'image') {
                            $this->imageWidth = $imageParams[0];
                            $this->imageHeight = $imageParams[1];
                            $this->imageType = $imageType;
                        }
                        $result = true;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Loads some watermark image into protected property from file 
     * 
     * @param string $fileName readable image file path

     * @return bool
     */
    public function loadWatermark($filePath) {
        $result = $this->loadImageFile($filePath, 'watermark');
        if ($result) {
            imagealphablending($this->watermark, false);
        }
        return ($result);
    }

    /**
     * Renders current instance image into browser or specified file path
     * 
     * @param string $fileName writable file path with name or null to browser rendering
     * @param string $type image type to save, like jpeg, png, gif...
     * 
     * @return bool
     */
    public function saveImage($fileName = null, $type = 'png') {
        $result = false;
        if ($this->image) {
            $saveFunctionName = 'image' . $type;
            if (function_exists($saveFunctionName)) {
                //custom header on browser output
                if (!$fileName) {
                    header('Content-Type: image/' . $type);
                }
                if ($type == 'jpeg' or $type == 'png') {
                    if ($type == 'png') {
                        imagesavealpha($this->image, true);
                    }
                    $result = $saveFunctionName($this->image, $fileName, $this->quality);
                } else {
                    $result = $saveFunctionName($this->image, $fileName);
                }

                //memory free
                imagedestroy($this->image);

                //droppin image props
                $this->imageWidth = 0;
                $this->imageHeight = 0;
                $this->imageType = '';
                //nothing else matters
                if ($fileName === false) {
                    die();
                }
            } else {
                throw new Exception('EX_NOT_SUPPORTED_FILETYPE:' . $type);
            }
        } else {
            throw new Exception('EX_VOID_IMAGE');
        }
        return ($result);
    }

    /**
     * Returns current instance image as base64 encoded text
     *
     * @param string $type image mime type
     * @param bool $htmlData data ready to embed as img src HTML base64 data (data URI scheme)
     * 
     * @return void
     */
    public function getImageBase($type = 'png', $htmlData = false) {
        $result = '';
        if ($this->image) {
            $saveFunctionName = 'image' . $type;
            if (function_exists($saveFunctionName)) {
                ob_start();
                if ($type == 'jpeg' or $type == 'png') {
                    if ($type == 'png') {
                        imagesavealpha($this->image, true);
                    }
                }
                $result = $saveFunctionName($this->image, null, $this->quality);

                //memory free
                imagedestroy($this->image);

                //droppin image props
                $this->imageWidth = 0;
                $this->imageHeight = 0;
                $this->imageType = '';
                $imageBody = ob_get_contents();

                ob_end_clean();
                if (!empty($imageBody)) {
                    $result = base64_encode($imageBody);
                    // print($imageBody);
                }

                //optional html embed data
                if ($htmlData) {
                    $result = 'data:image/' . $type . ';charset=utf-8;base64,' . $result;
                }
            } else {
                throw new Exception('EX_NOT_SUPPORTED_FILETYPE:' . $type);
            }
        } else {
            throw new Exception('EX_VOID_IMAGE');
        }
        return ($result);
    }

    /**
     * Renders current instance image into browser
     * 
     * @param string $type
     * 
     * @return void
     */
    public function renderImage($type = 'png') {
        $this->saveImage(null, $type);
    }

    /**
     * Creates new empty true-color image
     * 
     * @return void
     */
    public function createImage($width, $height) {
        $this->image = imagecreatetruecolor($width, $height);
        $this->imageWidth = $width;
        $this->imageHeight = $height;
    }

    /**
     * Scales image to some scale
     * 
     * @param float $scale something like 0.5 for 50% or 2 for 2x scale
     * 
     * @return void
     */
    public function scale($scale) {
        if ($this->imageWidth and $this->imageHeight) {
            if ($scale != 1) {
                $nWidth = $this->imageWidth * $scale;
                $nHeight = $this->imageHeight * $scale;
                $imageCopy = imagecreatetruecolor($nWidth, $nHeight);
                imagealphablending($imageCopy, false);
                imagesavealpha($imageCopy, true);
                imagecopyresized($imageCopy, $this->image, 0, 0, 0, 0, $nWidth, $nHeight, $this->imageWidth, $this->imageHeight);
                $this->image = $imageCopy;
                $this->imageWidth = $nWidth;
                $this->imageHeight = $nHeight;
            }
        }
    }

    /**
     * Resizes image to some new dimensions
     * 
     * @param int $width
     * @param int $height
     * 
     * @return void
     */
    public function resize($width, $height) {
        if ($this->imageWidth and $this->imageHeight) {
            $imageResized = imagescale($this->image, $width, $height);
            $this->image = $imageResized;
            $this->imageWidth = $width;
            $this->imageHeight = $height;
        }
    }

    /**
     * Crops image to new dimensions starting from 0x0
     * 
     * @return void
     */
    public function crop($width, $height) {
        if ($this->imageWidth and $this->imageHeight) {
            $imageCropped = imagecrop($this->image, array('x' => 0, 'y' => 0, 'width' => $width, 'height' => $height));
            $this->image = $imageCropped;
            $this->imageWidth = $width;
            $this->imageHeight = $height;
        }
    }

    /**
     * Crops image to selected region by coords
     * 
     * @return void
     */
    public function cropRegion($x1, $y1, $x2, $y2) {
        if ($this->imageWidth and $this->imageHeight) {
            $imageCropped = imagecrop($this->image, array('x' => $x1, 'y' => $y1, 'width' => $x2, 'height' => $y2));
            $this->image = $imageCropped;
            $this->imageWidth = $x2;
            $this->imageHeight = $y2;
        }
    }

    /**
     * Allocates and returns some image color by its name
     * 
     * @param string $colorName
     * 
     * @return int
     */
    protected function allocateColor($colorName) {
        $result = 0;
        if (isset($this->colorPalette[$colorName])) {
            $colorData = $this->colorPalette[$colorName];
            if (isset($this->colorsAllocated[$colorName])) {
                $result = $this->colorsAllocated[$colorName];
            } else {
                $result = imagecolorallocate($this->image, $colorData['r'], $colorData['g'], $colorData['b']);
                $this->colorsAllocated[$colorName] = $result;
            }
        } else {
            throw new Exception('EX_COLOR_NOT_EXISTS:' . $colorName);
        }
        return ($result);
    }

    /**
     * Fills image with some color from palette 
     * 
     * @param string $colorName
     * 
     * @return void
     */
    public function fill($colorName) {
        imagefill($this->image, 0, 0, $this->allocateColor($colorName));
    }

    /**
     * Draws pixel on X/Y coords with some color from palette
     * 
     * @param int y
     * @param int x
     * @param string $colorName
     * 
     * @return void
     */
    public function drawPixel($x, $y, $colorName) {
        imagesetpixel($this->image, $x, $y, $this->allocateColor($colorName));
    }

    /**
     * Prints some text string at specified X/Y coords with default font
     * 
     * @param int $x
     * @param int $y
     * @param string $text
     * @param string $colorName
     * @param int $size=1
     * @param bool $vertical
     * 
     * @return void
     */
    public function drawString($x, $y, $text, $colorName, $size = 1, $vertical = false) {
        if (!empty($text)) {
            if ($vertical) {
                imagestringup($this->image, $size, $x, $y, $text, $this->allocateColor($colorName));
            } else {
                imagestring($this->image, $size, $x, $y, $text, $this->allocateColor($colorName));
            }
        }
    }

    /**
     * Write text to the image using TrueType fonts
     * 
     * @param int $x
     * @param int $y
     * @param string $text
     * @param string $colorName
     * 
     * @return void
     */
    public function drawText($x, $y, $text, $colorName) {
        if (!empty($text)) {
            imagettftext($this->image, $this->fontSize, 0, $x, $y, $this->allocateColor($colorName), $this->font, $text);
        }
    }

    /**
     * Returns font size that fits into image width
     * 
     * @param int $fontSize font size that required to fit text
     * @param string $text text data that required to fit
     * @param int $padding text padding in px
     * 
     * @return int
     */
    protected function guessFontSize($fontSize, $text, $padding) {
        $box = imageftbbox($fontSize, 0, $this->font, $text);
        $boxWidth = $box[4] - $box[6];
        $imageWidth = $this->imageWidth - ($padding * 2);
        if ($boxWidth > $imageWidth) {
            $fontSize = $fontSize - 1;
            return $this->guessFontSize($fontSize, $text, $padding);
        }
        return ($fontSize);
    }

    /**
     * Write single text to the image using TrueType font with auto size selection
     * 
     * @param int $y
     * @param int $padding
     * @param string $text
     * @param string $colorName
     * @param string $outlineColor
     * 
     * @return void
     */
    public function drawTextAutoSize($y, $padding = 10, $text = '', $colorName = '', $outlineColor = '') {
        if (!empty($text)) {
            $defaultFontSize = 40;
            $border = 1;
            $x = $padding;
            $colorName = (empty($colorName)) ? 'white' : $colorName;
            $outlineColor = (empty($outlineColor)) ? 'black' : $outlineColor;

            //guessing font size
            $fontSize = $this->guessFontSize($defaultFontSize, $text, $padding);

            //drawing outline if required
            if ($outlineColor) {
                for ($c1 = ($x - abs($border)); $c1 <= ($x + abs($border)); $c1++) {
                    for ($c2 = ($y - abs($border)); $c2 <= ($y + abs($border)); $c2++) {
                        imagettftext($this->image, $fontSize, 0, $c1, $c2, $this->allocateColor($outlineColor), $this->font, $text);
                    }
                }
            }
            //and text with selected color
            imagettftext($this->image, $fontSize, 0, $x, $y, $this->allocateColor($colorName), $this->font, $text);
        }
    }

    /**
     * Set the thickness for line drawing in px
     * 
     * @param int $lineWidth 
     * 
     * @return void
     */
    public function setLineWidth($lineWidth) {
        $this->lineWidth = $lineWidth;
        imagesetthickness($this->image, $this->lineWidth);
    }

    /**
     * Draws filled rectangle
     * 
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param string $colorName
     * 
     * @return void
     */
    public function drawRectangle($x1, $y1, $x2, $y2, $colorName) {
        if (isset($this->colorPalette[$colorName])) {
            $colorData = $this->colorPalette[$colorName];
            if (isset($this->colorsAllocated[$colorName])) {
                $drawingColor = $this->colorsAllocated[$colorName];
            } else {
                $drawingColor = imagecolorallocate($this->image, $colorData['r'], $colorData['g'], $colorData['b']);
                $this->colorsAllocated[$colorName] = $drawingColor;
            }

            imagefilledrectangle($this->image, $x1, $y1, $x2, $y2, $drawingColor);
        } else {
            throw new Exception('EX_COLOR_NOT_EXISTS:' . $colorName);
        }
    }

    /**
     * Draws a line using some color
     * 
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param string $colorName
     * 
     * @return void
     */
    public function drawLine($x1, $y1, $x2, $y2, $colorName) {
        imageline($this->image, $x1, $y1, $x2, $y2, $this->allocateColor($colorName));
    }

    /**
     * Draws a line using preloaded brush
     * 
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * 
     * @return void
     */
    public function drawLineBrush($x1, $y1, $x2, $y2) {
        imageline($this->image, $x1, $y1, $x2, $y2, IMG_COLOR_BRUSHED);
    }

    /**
     * Puts preloaded watermark on base image
     * 
     * @param int $stretch=true
     * @param int $x=0
     * @param int $y=0
     * 
     * @return void
     */
    public function drawWatermark($stretch = true, $x = 0, $y = 0) {
        imagealphablending($this->watermark, false);
        $watermarkWidth = imagesx($this->watermark);
        $watermarkHeight = imagesy($this->watermark);
        if ($stretch) {
            imagecopyresampled($this->image, $this->watermark, $x, $y, 0, 0, $this->imageWidth, $this->imageHeight, $watermarkWidth, $watermarkHeight);
        } else {
            imagecopy($this->image, $this->watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight);
        }
    }

    /**
     * Applies pixelation filter
     * 
     * @param int $blockSize
     * @param bool $smooth
     * 
     * @return void
     */
    public function pixelate($blockSize, $smooth = true) {
        imagefilter($this->image, IMG_FILTER_PIXELATE, $blockSize, $smooth);
    }

    /**
     * Applies image filters set to current instance base image
     *
     * @param array|int $filterSet must contains array of filters as index=>(IMAGE_FILTER=>argsArray)
     * 
     * @return void
     */
    public function imageFilters($filterSet = array()) {
        if (!empty($filterSet)) {
            foreach ($filterSet as $eachFilterIdx => $eachFilterData) {
                if (is_array($eachFilterData)) {
                    foreach ($eachFilterData as $eachFilter => $eachFilterArgs/*  */) {
                        if (is_array($eachFilterArgs)) {
                            $filterArgsTmp = array();
                            $filterArgsTmp[] = $this->image;
                            $filterArgsTmp[] = $eachFilter;
                            foreach ($eachFilterArgs as $io => $each) {
                                $filterArgsTmp[] = $each;
                            }
                            //not using just "..." arg unpack operator here due PHP <5.6 compat
                            call_user_func_array('imagefilter', $filterArgsTmp);
                        } else {
                            imagefilter($this->image, $eachFilter, $eachFilterArgs);
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns RGB values for some specified image pixel as r/g/b/a(lpha)
     * 
     * @return array
     */
    public function getPixelColor($x, $y) {
        $result = array();
        $rgb = imagecolorat($this->image, $x, $y);
        $components = imagecolorsforindex($this->image, $rgb);
        $result['r'] = $components['red'];
        $result['g'] = $components['green'];
        $result['b'] = $components['blue'];
        $result['a'] = $components['alpha'];
        return ($result);
    }

    /**
     * Converts RGB components array into hex string
     * 
     * @param array $rgb RGB/RGBa components array
     * 
     * @return string
     */
    public function rgbToHex($rgb) {
        $result = '';
        if (!empty($rgb)) {
            $result = sprintf("#%02x%02x%02x", $rgb['r'], $rgb['g'], $rgb['b']);
        }
        return ($result);
    }

    /**
     * Converts hex color string to RGB components array
     * 
     * @param string $hex hex string as RRGGBB
     * 
     * @return array
     */
    public function hexToRgb($hex) {
        $result = '';
        if (!empty($hex)) {
            $hex = str_replace('#', '', $hex);
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $result = array('r' => $r, 'g' => $g, 'b' => $b);
        }
        return ($result);
    }

    /**
     * Calculates the brightness value of an RGB color.
     *
     * @param array $rgb The RGB color values as an associative array with keys 'r', 'g', and 'b'.
     * 
     * @return int The brightness value of the RGB color.
     */
    public function rgbToBrightness($rgb) {
        $result = round(($rgb['r'] + $rgb['g'] + $rgb['b']) / 3);
        return ($result);
    }

    /**
     * Returns color map for current intance image as array(y,x)=>color
     * 
     * @param bool $hex returns map values as rrggbb hex values or raw rgba components
     * 
     * @return array
     */
    public function getColorMap($hex = true) {
        $result = array();
        for ($x = 0; $x < $this->imageWidth; $x++) {
            for ($y = 0; $y < $this->imageHeight; $y++) {
                $rgb = $this->getPixelColor($x, $y);
                if ($hex) {
                    $result[$y][$x] = $this->rgbToHex($rgb);
                } else {
                    $result[$y][$x] = $rgb;
                }
            }
        }
        return ($result);
    }

    /**
     * Calculates the brightness of a pixel at the specified coordinates.
     *
     * @param int $x The x-coordinate of the pixel.
     * @param int $y The y-coordinate of the pixel.
     * 
     * @return int The brightness value of the pixel.
     */
    public function getPixelBrightness($x, $y) {
        $result = false;
        $pixelColor = $this->getPixelColor($x, $y);
        $result = $this->rgbToBrightness($pixelColor);
        return ($result);
    }

    /**
     * Rotates the image by the specified angle clockwise
     *
     * @param int $angle The angle in degrees to rotate the image.
     * 
     * @return void
     */
    public function rotate($angle) {
        $this->image = imagerotate($this->image, 360 - $angle, 0);
    }

    /**
     * Sets the brush for the image using the specified file path.
     *
     * @param string $filePath The path to the image file to be used as a brush.
     * 
     * @return bool
     */
    public function setBrush($filePath) {
        $result = $this->loadImageFile($filePath, 'brush');
        if ($result) {
            imagesetbrush($this->image, $this->brush);
        }
        return ($result);
    }

    /**
     * Draw a partial arc and fill it
     *
     * @param int $x The x-coordinate of the center.
     * @param int $y The y-coordinate of the center.
     * @param int $width The width of the arc.
     * @param int $height The height of the arc.
     * @param int $startAngle The start angle of the arc in degrees.
     * @param int $endAngle The end angle of the arc in degrees.
     * @param string $colorName The name of the color to use for the arc.
     * @param int $style The style of the arc. Available: IMG_ARC_PIE, IMG_ARC_CHORD, IMG_ARC_NOFILL, IMG_ARC_EDGED
     *
     * @return void
     */
    public function drawArcFilled($x, $y, $width, $height, $startAngle, $endAngle, $colorName, $style = IMG_ARC_PIE) {
        imagefilledarc($this->image, $x, $y, $width, $height, $startAngle, $endAngle, $this->allocateColor($colorName), $style);
    }

    /**
     * Draw an arc without filling
     *
     * @param int $x The x-coordinate of the center.
     * @param int $y The y-coordinate of the center.
     * @param int $width The width of the arc.
     * @param int $height The height of the arc.
     * @param int $startAngle The start angle of the arc in degrees.
     * @param int $endAngle The end angle of the arc in degrees.
     * @param string $colorName The name of the color to use for the arc.
     * 
     * @return void
     */
    public function drawArc($x, $y, $width, $height, $startAngle, $endAngle, $colorName) {
        imagearc($this->image, $x, $y, $width, $height, $startAngle, $endAngle, $this->allocateColor($colorName));
    }

    /**
     * Draw a polygon
     *
     * @param array $points The points of the polygon.
     * @param string $colorName The name of the color to use for the polygon.
     * 
     * @return void
     */
    public function drawPolygon($points, $colorName) {
        if (phpversion() < '8.0.0') {
            $num_points = count($points) / 2;
            imagepolygon($this->image, $points, $num_points, $this->allocateColor($colorName));
        } else {
            imagepolygon($this->image, $points, $this->allocateColor($colorName));
        }
    }

    /**
     * Draw a filled polygon
     *
     * @param array $points The points of the polygon.
     * @param string $colorName The name of the color to use for the polygon.
     * 
     * @return void
     */
    public function drawPolygonFilled($points, $colorName) {
        if (phpversion() < '8.0.0') {
            $num_points = count($points) / 2;
            imagefilledpolygon($this->image, $points, $num_points, $this->allocateColor($colorName));
        } else {
            imagefilledpolygon($this->image, $points, $this->allocateColor($colorName));
        }
    }
}
