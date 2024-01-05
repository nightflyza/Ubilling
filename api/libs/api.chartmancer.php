<?php

/**
 * The ChartMancer class automates the generation of graphs based on data provided in an array. 
 * It utilizes the GD library to create visually appealing charts, allowing developers to easily 
 * visualize and display data trends. With ChartMancer, you can customize various aspects of the 
 * graph, such as colors, labels, and other chart properties, providing a versatile tool 
 * for data representation in PHP applications.
 * 
 * @package ChartMancer
 * @author Rostyslav Haitkulov <info@ubilling.net.ua>
 * @see https://github.com/nightflyza/ChartMancer
 * @license MIT
 */
class ChartMancer {

    /**
     * Custom chart chart title
     * 
     * @var string 
     */
    protected $chartTitle = '';

    /**
     * Custom colors palette modifier
     * 
     * @var string
     */
    protected $palette = 'O-M-G';

    /**
     * Image dimensions, width in px
     * 
     * @var int
     */
    protected $imageWidth = 1540;

    /**
     * Image dimensions, height in px
     * 
     * @var int
     */
    protected $imageHeight = 400;

    /**
     * Grid dimensions and placement within chart, top side, px
     * 
     * @var int
     */
    protected $gridTop = 40;

    /**
     * Grid dimensions and placement within chart, left side, px
     * @var int
     */
    protected $gridLeft = 50;

    /**
     * Grid line width in px
     * 
     * @var int
     */
    protected $lineWidth = 1;

    /**
     * Bar default width in px
     * 
     * @var int
     */
    protected $barWidth = 5;

    /**
     * Margin between label and axis in px
     * 
     * @var int
     */
    protected $labelMargin = 8;

    /**
     * Contains optional chart legend
     * 
     * @var array
     */
    protected $chartLegend = array();

    /**
     * Contains default base color RGB decimal values
     * 
     * @var array
     */
    protected $baseColor = array(
        'r' => 47,
        'g' => 133,
        'b' => 217
    );

    /**
     * Contains default background color RGB decimal values. White by default.
     * 
     * @var array
     */
    protected $backGroundColor = array(
        'r' => 255,
        'g' => 255,
        'b' => 255
    );

    /**
     * Contains default grid color RGB decimal values.
     * 
     * @var array
     */
    protected $gridColor = array(
        'r' => 212,
        'g' => 212,
        'b' => 212
    );

    /**
     * Contains default axis color RGB decimal values.
     * 
     * @var array
     */
    protected $axisColor = array(
        'r' => 85,
        'g' => 85,
        'b' => 85
    );

    /**
     * Contains default text color RGB decimal values.
     * 
     * @var array
     */
    protected $textColor = array(
        'r' => 85,
        'g' => 85,
        'b' => 85
    );

    /**
     * Dynamic palette overrides
     * 
     * @var array
     */
    protected $overrideColors=array();

    /**
     * Transparent background transparency flag
     * 
     * @var bool
     */
    protected $backgroundTransparent = false;

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
     * Maximum length of x-Axis label text
     * 
     * @var int
     */
    protected $xLabelLen = 5;

    /**
     * Render maximum dataset value on chart?
     * 
     * @var bool
     */
    protected $displayPeakValue = false;

    /**
     * Contains custom Y-axis label
     * 
     * @var string
     */
    protected $yAxisName = '';

    /**
     * Rendering debug flag
     * 
     * @var bool
     */
    protected $debug = false;

    /**
     * Bar automatic width modifier depend on data set size
     * 
     * @var bool
     */
    protected $barAutoWidth = true;

    public function __construct() {
        //what are you expecting to see here?
    }

    /**
     * Returns a decimal RGB color based on text string as array(r/g/b)
     *
     * @param $text Some string of text
     * @param $palette palette string
     *
     * @return array
     */
    protected function getColorFromText($text) {
        $result = array();
        $hash = md5($this->palette . $text);
        $result['r'] = hexdec(substr($hash, 0, 2));
        $result['g'] = hexdec(substr($hash, 2, 2));
        $result['b'] = hexdec(substr($hash, 4, 2));
        return($result);
    }

    /**
     * Checks is array contains valid RGB values or not?
     * 
     * @return bool
     */
    protected function checkColor($colorArray) {
        $result = false;
        if (isset($colorArray['r']) AND isset($colorArray['g']) AND isset($colorArray['b'])) {
            if (is_numeric($colorArray['r']) AND is_numeric($colorArray['g']) AND is_numeric($colorArray['b'])) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Set bar automatic width modifier depend on data set size
     *
     * @param  bool  $barAutoWidth  Bar automatic width modifier depend on data set size
     *
     * @return  void
     */
    public function setBarAutoWidth($barAutoWidth) {
        $this->barAutoWidth = $barAutoWidth;
    }

    /**
     * Set custom colors palette modifier
     *
     * @param  string  $palette  Custom colors palette modifier
     *
     * @return  void
     */
    public function setPalette($palette) {
        $this->palette = $palette;
    }

    /**
     * Set image dimensions, width in px
     *
     * @param  int  $imageWidth  Image dimensions, width in px
     *
     * @return  void
     */
    public function setImageWidth($imageWidth) {
        $this->imageWidth = $imageWidth;
    }

    /**
     * Set image dimensions, height in px
     *
     * @param  int  $imageHeight  Image dimensions, height in px
     *
     * @return  void
     */
    public function setImageHeight($imageHeight) {
        $this->imageHeight = $imageHeight;
    }

    /**
     * Set bar default width in px
     *
     * @param  int  $barWidth  Bar default width in px
     *
     * @return  void
     */
    public function setBarWidth($barWidth) {
        $this->barWidth = $barWidth;
    }

    /**
     * Set default base color RGB decimal values
     *
     * @param  array  $baseColor  Contains default base color RGB decimal values
     *
     * @return  void
     */
    public function setBaseColor($baseColor) {
        if ($this->checkColor($baseColor)) {
            $this->baseColor = $baseColor;
        }
    }

    /**
     * Set default background color RGB decimal values. White by default.
     *
     * @param  array  $backGroundColor  Contains default background color RGB decimal values. 
     *
     * @return  void
     */
    public function setBackGroundColor($backGroundColor) {
        if ($this->checkColor($backGroundColor)) {
            $this->backGroundColor = $backGroundColor;
        }
    }

    /**
     * Set default grid color RGB decimal values.
     *
     * @param  array  $gridColor  Contains default grid color RGB decimal values.
     *
     * @return  void
     */
    public function setGridColor($gridColor) {
        if ($this->checkColor($gridColor)) {
            $this->gridColor = $gridColor;
        }
    }

    /**
     * Set default axis color RGB decimal values.
     *
     * @param  array  $axisColor  Contains default axis color RGB decimal values.
     *
     * @return  void
     */
    public function setAxisColor($axisColor) {
        if ($this->checkColor($axisColor)) {
            $this->axisColor = $axisColor;
        }
    }

    /**
     * Set transparent background transparency flag
     *
     * @param  bool  $backgroundTransparent  Transparent background transparency flag
     *
     * @return  void
     */
    public function setBackgroundTransparent($backgroundTransparent) {
        $this->backgroundTransparent = $backgroundTransparent;
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
     * Set rendering debug flag
     *
     * @param  bool  $debug  Rendering debug flag
     *
     * @return  void
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * Set default text color RGB decimal values.
     *
     * @param  array  $textColor  default text color RGB decimal values.
     *
     * @return  self
     */
    public function setTextColor(array $textColor) {
        if ($this->checkColor($textColor)) {
            $this->textColor = $textColor;
        }
    }

    /**
     * Set chart chartTitle
     *
     * @param  string  $chartTitle  Chart chartTitle
     *
     * @return  void
     */
    public function setChartTitle($chartTitle) {
        $this->chartTitle = $chartTitle;
    }

    /**
     * Set maximum dataset value rendering on chart flag
     *
     * @return  void
     */
    public function setDisplayPeakValue($displayPeakValue) {
        $this->displayPeakValue = $displayPeakValue;
    }

    /**
     * Sets optional chart legend
     * 
     * @param array $chartLegend
     * 
     * @return void
     */
    public function setChartLegend($chartLegend) {
        if (is_array($chartLegend)) {
            $this->chartLegend = $chartLegend;
        }
    }

    /**
     * Sets custom Y-axis name
     * 
     * @param string $yAxisName
     * 
     * @return void
     */
    public function setChartYaxisName($yAxisName) {
        $this->yAxisName = $yAxisName;
    }

    /**
     * Sets custom colors overrides as array of RGB decimal values.
     * 
     * @return void
     */
    public function setOverrideColors($customColors) {
        $this->overrideColors=$customColors;
    }

    /**
     * Renders chart as PNG image into browser or into specified file
     * 
     * @param array $data
     * 
     * @return bool
     */
    public function renderChart($data, $fileName = '') {
        if ($this->debug) {
            //chart generation start timing
            $starttime = explode(' ', microtime());
            $starttime = $starttime[1] + $starttime[0];
        }
        $dataMax = 0;
        $nestedData = false;
        $nestedDepth = 0;
        $dataSize = sizeof($data);
        $dataSize = ($dataSize == 0) ? 1 : $dataSize;
        $result = false;

        $xAxisLabelCount = 20;
        if ($dataSize < 10) {
            $xAxisLabelCount = 10;
        }

// Avoid non array input data usage
        if (!is_array($data)) {
            $data = array();
        }

// Basic data preprocessing
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $nestedData = true;
                    $nestedDepth = sizeof($value);
                    foreach ($value as $io => $subVal) {
                        if ($subVal > $dataMax) {
                            $dataMax = $subVal;
                        }
                    }
                } else {
                    if ($value > $dataMax) {
                        $dataMax = $value;
                    }
                }
            }
        }



// Calculating grid dimensions and placement within image
        $gridBottom = $this->imageHeight - $this->gridTop;
        $gridRight = $this->imageWidth - $this->gridLeft;
        $gridHeight = $gridBottom - $this->gridTop;
        $gridWidth = $gridRight - $this->gridLeft;

// Max value on y-axis
        $yMaxValue = 200;
// Setting yMaxValue depend of data values
        if ($dataMax) {
            $yMaxValue = round($dataMax + ($dataMax * 0.3));
            $yMaxValue = ($yMaxValue != 0) ? $yMaxValue : 2; //preventing division by zero
        }

// Distance between grid lines on y-axis
        $yLabelSpan = 20;
        if ($dataMax) {
            if ($dataMax <= 20) {
                $yLabelSpan = 1;
            } else {
                if ($dataMax<=100) {
                    $yLabelSpan=5;
                } else {
                    $yLabelSpan=10;
                }
            }
            
            if ($dataMax >= 200) {
                $yLabelSpan = round($dataMax / 10);
            }
        }

// Bar width based on data set size?
        if ($this->barAutoWidth) {
            if ($dataSize <= 50) {
                $this->barWidth = round(($this->imageWidth - $this->gridLeft) / ($dataSize * 1.2));
            }
        }


// Init image
        $chart = imagecreate($this->imageWidth, $this->imageHeight);

// Chart backgroun color setup
        if ($this->backgroundTransparent) {
            imagealphablending($chart, false);
            $backgroundColor = imagecolorallocatealpha($chart, 255, 255, 255, 127);
            imagesavealpha($chart, true);
        } else {
            $backgroundColor = imagecolorallocate($chart, $this->backGroundColor['r'], $this->backGroundColor['g'], $this->backGroundColor['b']);
        }
// Chart base, axis, labels and grid colors setup        
        $baseColor = imagecolorallocate($chart, $this->baseColor['r'], $this->baseColor['g'], $this->baseColor['b']);
        $axisColor = imagecolorallocate($chart, $this->axisColor['r'], $this->axisColor['g'], $this->axisColor['b']);
        $labelColor = imagecolorallocate($chart, $this->textColor['r'], $this->textColor['g'], $this->textColor['b']);
        $gridColor = imagecolorallocate($chart, $this->gridColor['r'], $this->gridColor['g'], $this->gridColor['b']);
        $customColors = array();
        $customColors[0] = $baseColor;
// Nested colors palette generation here
        if ($nestedData) {
            for ($i = 1; $i <= $nestedDepth; $i++) {
                if (isset($this->overrideColors[$i])) {
                    //use color override
                    $randomColor=$this->overrideColors[$i];
                } else {
                    //or generating new
                    $randomColor = $this->getColorFromText($i);
                }
                $customColors[$i] = imagecolorallocate($chart, $randomColor['r'], $randomColor['g'], $randomColor['b']);
            }
        }

        imagefill($chart, 0, 0, $backgroundColor);
        imagesetthickness($chart, $this->lineWidth);

        /*
         * Print grid lines bottom up
         */

        for ($i = 0; $i <= $yMaxValue; $i += $yLabelSpan) {
            $y = $gridBottom - $i * $gridHeight / $yMaxValue;
// Draw the line
            imageline($chart, $this->gridLeft, (int) $y, $gridRight, (int) $y, $gridColor);

// Draw right aligned label
            $labelBox = imagettfbbox($this->fontSize, 0, $this->font, strval($i));
            $labelWidth = $labelBox[4] - $labelBox[0];

            $labelX = $this->gridLeft - $labelWidth - $this->labelMargin;
            $labelY = $y + $this->fontSize / 2;
            $labelX = (int) $labelX;
            $labelY = (int) $labelY;

            imagettftext($chart, $this->fontSize, 0, $labelX, $labelY, $labelColor, $this->font, strval($i));
        }

        /*
         * Draw x- and y-axis
         */

        imageline($chart, $this->gridLeft, $this->gridTop, $this->gridLeft, $gridBottom, $axisColor);
        imageline($chart, $this->gridLeft, $gridBottom, $gridRight, $gridBottom, $axisColor);

        /*
         * Draw the bars with labels
         */

        $barSpacing = $gridWidth / $dataSize;

        $itemX = $this->gridLeft + $barSpacing / 2;
        $index = 0;

        foreach ($data as $key => $value) {
            /**
             *  Draw the bars
             */
            if (is_array($value)) {
//nested data rendering here
                $i = 0;
                $zBuffer = array();
                foreach ($value as $io => $subVal) {
                    $x1 = $itemX - $this->barWidth / 2;
                    $y1 = $gridBottom - $subVal / $yMaxValue * $gridHeight;
                    $x2 = $itemX + $this->barWidth / 2;
                    $y2 = $gridBottom - 1;

                    $x1 = (int) $x1;
                    $y1 = (int) $y1;
                    $x2 = (int) $x2;
                    $y2 = (int) $y2;
                    @$rValue =(isset($zBuffer[$subVal])) ? ($subVal-1).'_' : $subVal;
                    @$zBuffer[$rValue] = array(
                        'value' => $subVal,
                        'x1' => $x1,
                        'y1' => $y1,
                        'x2' => $x2,
                        'y2' => $y2,
                        'colorIdx' => $i
                    );
                    $i++;
                }

                if (!empty($zBuffer)) {
                    krsort($zBuffer);
                    foreach ($zBuffer as $subValue => $rParams) {
                        if ($rParams['value'] > 0) {
                            imagefilledrectangle($chart, $rParams['x1'], $rParams['y1'], $rParams['x2'], $rParams['y2'], $customColors[$rParams['colorIdx']]);
                        }
                    }
                }
            } else {
// raw key=>val dataset
                $x1 = $itemX - $this->barWidth / 2;
                $y1 = $gridBottom - $value / $yMaxValue * $gridHeight;
                $x2 = $itemX + $this->barWidth / 2;
                $y2 = $gridBottom - 1;

//explict conversion to avoid implict precision warnings
                $x1 = (int) $x1;
                $y1 = (int) $y1;
                $x2 = (int) $x2;
                $y2 = (int) $y2;
                if ($value > 0) {
                    imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $customColors[0]);
                }
            }

// Draw the label
            $labelBox = imagettfbbox($this->fontSize, 0, $this->font, $key);
            $labelWidth = $labelBox[4] - $labelBox[0];

            $labelX = $itemX - $labelWidth / 2;
            $labelY = $gridBottom + $this->labelMargin + $this->fontSize;

            $labelX = (int) $labelX;
            $labelY = (int) $labelY;

// Skipping some labels display?
            $index++;
            if ($dataSize > 10) {
                $labelIterator = (int) ($dataSize / $xAxisLabelCount);
                $labelIterator = ($labelIterator == 0) ? 1 : $labelIterator; //prevents mod by zero
                if (($index) % $labelIterator == 0) {
                    $labelText = (((mb_strlen($key, 'UTF-8') > $this->xLabelLen)) ) ? mb_substr($key, 0, $this->xLabelLen, 'utf-8') . '...' : $key;
                    imagettftext($chart, $this->fontSize, 0, $labelX, $labelY, $labelColor, $this->font, $labelText);
                }
            } else {
                $labelText = (((mb_strlen($key, 'UTF-8') > $this->xLabelLen)) ) ? mb_substr($key, 0, $this->xLabelLen, 'utf-8') . '...' : $key;
                imagettftext($chart, $this->fontSize, 0, $labelX, $labelY, $labelColor, $this->font, $labelText);
            }

            $itemX += $barSpacing;
        }

// Optional chart chartTitle?
        if ($this->chartTitle) {
            $titleX = ($this->imageWidth - $this->gridLeft) / 2.3;
            imagettftext($chart, $this->fontSize + 8, 0, (int) $titleX, 24, $labelColor, $this->font, $this->chartTitle);
        }
// Rendering custom Y-axis label
        if ($this->yAxisName) {
            $yAxisX = $this->gridLeft - 40;
            $yAxisY = (int) $this->gridTop - 10;
            imagettftext($chart, $this->fontSize, 0, $yAxisX, $yAxisY, $labelColor, $this->font, $this->yAxisName);
        }
// Rendering of data set peak value?
        if ($this->displayPeakValue) {
            $peakX = (int) ($this->imageWidth - $this->gridLeft) - 150;
            $peakY = (int) $this->imageHeight - ($this->fontSize * 0.5);
            $peakLabel = ($this->yAxisName) ? round($dataMax, 3) . ' ' . $this->yAxisName : round($dataMax, 3);
            imagettftext($chart, $this->fontSize, 0, $peakX, $peakY, $labelColor, $this->font, 'Max: ' . $peakLabel);
        }
// Rendering chart legend
        if (!empty($this->chartLegend)) {
            $lWidth = 20;
            foreach ($customColors as $colorIndex => $customColor) {
                if (isset($this->chartLegend[$colorIndex])) {
                    $rawLabel = $this->chartLegend[$colorIndex];
                    $legendText = (((mb_strlen($rawLabel, 'UTF-8') > $this->xLabelLen + 3)) ) ? mb_substr($rawLabel, 0, $this->xLabelLen + 3, 'utf-8') . '...' : $rawLabel;
                    $offset = $colorIndex * 10;
                    $y1 = $this->imageHeight - 5;
                    $y2 = $this->imageHeight - 20;

                    $x1 = $offset * 10 + $lWidth;
                    $x2 = $x1 + $lWidth;

                    $x1 = (int) $x1;
                    $y1 = (int) $y1;
                    $x2 = (int) $x2;
                    $y2 = (int) $y2;

                    $labelX = $x2 + 5;

                    imagefilledrectangle($chart, $x1, $y1, $x2, $y2, $customColor);
                    imagettftext($chart, $this->fontSize, 0, $labelX, $y1 - 2, $labelColor, $this->font, $legendText);
                }
            }
        }

        if ($this->debug) {
            //chart generation end timing
            $mtime = explode(' ', microtime());
            $totaltime = $mtime[0] + $mtime[1] - $starttime;
            $debugX = $this->imageWidth - 150;
            imagettftext($chart, 10, 0, $debugX, 18, $labelColor, $this->font, 'DS: ' . $dataSize . ' items');
            imagettftext($chart, 10, 0, $debugX, 38, $labelColor, $this->font, 'GT: ' . round($totaltime, 5) . ' sec.');
        }

        if (empty($fileName)) {
            header('Content-Type: image/png');
            $result = imagepng($chart);
            imagedestroy($chart);
            die();
        } else {
            $result = imagepng($chart, $fileName);
            imagedestroy($chart);
        }

        return($result);
    }
}
