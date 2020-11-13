<?php
/**
 * Generate print card management API
 */
class GenerateCard {
    private $font = 'content/documents/card_print/font/AvanteBs_ExtraBold.ttf';

    /**
     * GenerateCard constructor
     *
     * @param string $nameTemplate
     */
    public function __construct($nameTemplate)
    {
        $this->im = imagecreatefromjpeg($nameTemplate);
        putenv('GDFONTPATH=' . realpath('.'));
    }

    public function saveImage($name)
    {
        imagejpeg($this->im, $name);
        imagedestroy($this->im);
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function createStringForImage(array $data)
    {
        foreach ($data as $row) {
            if (count(array_filter($row)) !== count($row)) {
                continue;
            }
            $color = $this->getColorRGB($row['color']);
            $imageColor = imagecolorallocate($this->im, $color['r'], $color['g'], $color['b']);

            imagettftext(
                $this->im,
                $row['font_size'],
                0,
                $row['left'],
                $row['top'],
                $imageColor,
                $this->font,
                $row['text']
            );
        }

        return $this;
    }

    /**
     * @param $colorSrt
     *
     * @return array
     */
    private function getColorRGB($colorSrt)
    {
        $colorArr = explode('.', $colorSrt);

        $color = array('r' => (int) $colorArr[0]);
        $color += array('g' => (int) $colorArr[1]);
        $color += array('b' => (int) $colorArr[2]);

        return $color;
    }
}

?>
