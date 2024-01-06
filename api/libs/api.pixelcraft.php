<?php

/**
 * Tiny image processing library
 */
class PixelCraft
{

    /**
     * Contains image copy to perform some magic on it
     * 
     * @var GDimage
     */
    protected $image = '';

    /**
     * Contains image type 
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

    public function __construct()
    {
        //Schweigen im wald
    }

    /**
     * Sets current instance image save/render quality
     * 
     * @param int $quality Deafult -1 means IJG quality value for jpeg or default zlib for png
     * 
     * @return void
     */
    public function setQuality($quality)
    {
        if (is_int($quality)) {
            $this->quality = $quality;
        }
    }

    /**
     * Returns current image width
     * 
     * @return int
     */
    public function getImageWidth()
    {
        return ($this->imageWidth);
    }

    /**
     * Returns current image height
     * 
     * @return int
     */
    public function getImageHeight()
    {
        return ($this->imageHeight);
    }

    /**
     * Returns loaded image type
     * 
     * @return string
     */
    public function getImageType()
    {
        return ($this->imageType);
    }

    /**
     * Returns specified image 
     * 
     * @param string $filePath
     * 
     * @return array
     */
    protected function getImageParams($filePath)
    {
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
    public function addColor($colorName, $r, $g, $b)
    {
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
    public function isImageValid($filePath = '', $imageParams = array())
    {
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
    protected function detectImageType($filePath = '', $imageParams = array())
    {
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
    public function loadImage($filePath)
    {
        $result = false;
        $imageParams = $this->getImageParams($filePath);
        $imageType = $this->detectImageType('', $imageParams);
        if (!empty($imageType)) {
            $loaderFunctionName = 'imagecreatefrom' . $imageType;
            if (function_exists($loaderFunctionName)) {
                $this->image = $loaderFunctionName($filePath);
                //setting loaded image base props
                if ($this->image != false) {
                    $this->imageWidth = $imageParams[0];
                    $this->imageHeight = $imageParams[1];
                    $this->imageType = $imageType;
                    $result = true;
                }
            } else {
                throw new Exception('EX_NOT_SUPPORTED_FILETYPE:' . $imageType);
            }
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
    public function saveImage($fileName = null, $type)
    {
        $result = false;
        if ($this->image) {
            $saveFunctionName = 'image' . $type;
            if (function_exists($saveFunctionName)) {
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
            } else {
                throw new Exception('EX_NOT_SUPPORTED_FILETYPE:' . $type);
            }
        } else {
            throw new Exception('EX_VOID_IMAGE');
        }
        return ($result);
    }

    /**
     * Creates new empty true-color image
     * 
     * @return void
     */
    public function createImage($width, $height)
    {
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
    public function scale($scale)
    {
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
                imagedestroy($imageCopy);
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
    public function resize($width, $height)
    {
        if ($this->imageWidth and $this->imageHeight) {
            $imageResized = imagescale($this->image, $width, $height);
            $this->image = $imageResized;
            imagedestroy($imageResized);
            $this->imageWidth = $width;
            $this->imageHeight = $height;
        }
    }

    /**
     * Fills image with some color from palette 
     * 
     * @param string $colorName
     * 
     * @return void
     */
    public function fill($colorName)
    {
        if (isset($this->colorPalette[$colorName])) {
            $colorData = $this->colorPalette[$colorName];
            if (isset($this->colorsAllocated[$colorName])) {
                $fillColor = $this->colorsAllocated;
            } else {
                $fillColor = imagecolorallocate($this->image, $colorData['r'], $colorData['g'], $colorData['b']);
                $this->colorsAllocated[$colorName] = $fillColor;
            }
            imagefill($this->image, 0, 0, $fillColor);
        } else {
            throw new Exception('EX_COLOR_NOT_EXISTS:' . $colorName);
        }
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
    public function drawPixel($x, $y, $colorName)
    {
        if (isset($this->colorPalette[$colorName])) {
            $colorData = $this->colorPalette[$colorName];
            if (isset($this->colorsAllocated[$colorName])) {
                $pixelColor = $this->colorsAllocated[$colorName];
            } else {
                $pixelColor = imagecolorallocate($this->image, $colorData['r'], $colorData['g'], $colorData['b']);
                $this->colorsAllocated[$colorName] = $pixelColor;
            }

            imagesetpixel($this->image, $x, $y, $pixelColor);
        } else {
            throw new Exception('EX_COLOR_NOT_EXISTS:' . $colorName);
        }
    }

}
