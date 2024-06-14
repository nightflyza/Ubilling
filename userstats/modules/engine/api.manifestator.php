<?php

/**
 * Implements a manifest generator for a web application
 */
class Manifestator {
    /**
     * application name
     *
     * @var string
     */
    protected $name = 'AppName';

    /**
     * short application name
     *
     * @var string
     */
    protected $shortName = 'ShortName';

    /**
     * The start URL 
     *
     * @var string
     */
    protected $startUrl = '.';

    /**
     * The display mode 
     *
     * @var string
     */
    protected $display = 'standalone';

    /**
     * The theme color 
     *
     * @var string
     */
    protected $themeColor = 'ffffff';

    /**
     * The background color 
     *
     * @var string
     */
    protected $backgroundColor = 'ffffff';

    /**
     * The icons set
     *
     * @var array
     */
    protected $icons = array();

    /**
     * Contains appended custom data
     *
     * @var array
     */
    protected $appendData = array();

    /**
     * Constructor method.
     * 
     * Initializes the Manifestator object and sets default icons.
     */
    public function __construct() {
        $this->setDefaultIcons();
    }

    /**
     * Sets the default icons for the web application.
     * 
     * @return void
     */
    protected function setDefaultIcons() {
        $defaultIcons = array(
            0 => array('src' => 'skins/webapp/wa192.png', 'sizes' => '192x192', 'type' => 'image/png'),
            1 => array('src' => 'skins/webapp/wa512.png', 'sizes' => '512x512', 'type' => 'image/png')
        );
        $this->setIcons($defaultIcons);
    }

    /**
     * Sets the name of the web application.
     * 
     * @param string $name The name of the web application.
     * 
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Sets the shortName of the web application.
     * 
     * @param string $name The name of the web application.
     * 
     * @return void
     */
    public function setShortName($name) {
        $this->shortName = $name;
    }

    /**
     * Sets the start URL of the web application.
     * 
     * @param string $startUrl The start URL of the web application.
     * 
     * @return void
     */
    public function setStartUrl($startUrl) {
        $this->startUrl = $startUrl;
    }

    /**
     * Sets the display mode of the web application.
     * 
     * @param string $display The display mode of the web application. fullscreen|standalone|minimal-ui|browser
     * 
     * @return void
     */
    public function setDisplay($display) {
        $this->display = $display;
    }

    /**
     * Sets the theme color of the web application.
     * 
     * @param string $themeColor The theme color of the web application.
     * 
     * @return void
     */
    public function setThemeColor($themeColor) {
        $this->themeColor = $themeColor;
    }

    /**
     * Sets the background color of the web application.
     * 
     * @param string $backgroundColor The background color of the web application.
     * 
     * @return void
     */
    public function setBackgroundColor($backgroundColor) {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * Sets the icons set of the web application.
     * 
     * @param array $icons An array of icons for the web application.
     * 
     * @return void
     */
    public function setIcons($icons) {
        $this->icons = $icons;
    }


    /**
     * Sets the custom data to be appended to the manifest.
     *
     * @param array $customData An array of custom data to be appended.
     * 
     * @return void
     */
    public function setAppendData($customData = array()) {
        $this->appendData = $customData;
    }

    /**
     * Returns application manifest array
     *
     * @return array
     */
    protected function getManifest() {
        $result = array(
            'name' => $this->name,
            'short_name' => $this->shortName,
            'start_url' => $this->startUrl,
            'display' => $this->display,
            'theme_color' => $this->themeColor,
            'background_color' => $this->backgroundColor,
            'icons' => $this->icons
        );

        if (!empty($this->appendData)) {
            $result+=$this->appendData;
        }
        return ($result);
    }


    /**
     * Renders the manifest data as JSON and sends it as the response.
     *
     * @return void
     */
    public function render() {
        $manifest = $this->getManifest();
        $jsonData = json_encode($manifest);
        header('Content-Type: application/json');
        die($jsonData);
    }
}
