<?php

/**
 * Minimal Mermaid diagram renderer wrapper.
 */
class Mermaid {

    const LIB_PATH = 'modules/jsc/mermaid/mermaid.min.js';

    /**
     * Script tag already emitted on this page
     *
     * @var bool
     */
    protected static $libLoaded = false;

    /**
     * mermaid.initialize already emitted on this page
     *
     * @var bool
     */
    protected static $configured = false;

    /**
     * Mermaid diagram source
     *
     * @var string
     */
    protected $source = '';

    /**
     * Default render options.
     *
     * Supported keys:
     *   width - string, wrapper div width (CSS value), default '100%'
     *   theme - string, Mermaid color theme: default|neutral|dark|forest
     *           applied globally on first diagram render on the page
     *
     * @var array
     */
    protected $options = array(
        'width' => '100%',
        'theme' => 'default',
    );

    /**
     * Creates new Mermaid instance
     *
     * @param string $source
     */
    public function __construct($source = '') {
        $this->source = $source;
    }

    /**
     * Sets diagram source
     *
     * @param string $source
     *
     * @return void
     */
    public function setSource($source) {
        $this->source = $source;
    }

    /**
     * Returns mermaid.min.js script tag once per page
     *
     * @return string
     */
    protected function getLibScript() {
        $result = '';
        if (!self::$libLoaded) {
            self::$libLoaded = true;
            $result .= wf_tag('script', false, '', 'type="text/javascript" src="' . self::LIB_PATH . '"') . wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Validates theme name against allowed values
     *
     * @param string $theme
     *
     * @return string
     */
    protected function normalizeTheme($theme) {
        $allowed = array('default', 'neutral', 'dark', 'forest');
        $result = 'default';
        if (in_array($theme, $allowed)) {
            $result = $theme;
        }
        return ($result);
    }

    /**
     * Renders diagram HTML and init script.
     *
     * Returns empty string when diagram source is empty.
     *
     * @param array $options optional overrides for default render options:
     *   width (string) - wrapper div width, any CSS length: '100%', '800px', '50vw'
     *   theme (string) - Mermaid built-in theme, one of:
     *       default  - blue accent, light background (default)
     *       neutral  - grayscale palette
     *       dark     - dark background, light text
     *       forest   - green accent palette
     *     Note: theme is passed to mermaid.initialize() once per page;
     *     only the first rendered diagram on the page defines the theme.
     *
     * @return string HTML markup with embedded init script
     */
    public function render($options = array()) {
        $result = '';
        if (!empty($this->source)) {
            $mergedOptions = $this->options;
            if (!empty($options)) {
                $mergedOptions = array_merge($mergedOptions, $options);
            }

            $width = isset($mergedOptions['width']) ? $mergedOptions['width'] : '100%';
            $theme = isset($mergedOptions['theme']) ? $mergedOptions['theme'] : 'default';
            $theme = $this->normalizeTheme($theme);
            $elementId = 'mmd_' . wf_InputId();

            $result .= $this->getLibScript();
            $result .= wf_tag('div', false, '', 'style="width: ' . $width . ';"');
            $result .= wf_tag('pre', false, 'mermaid', 'id="' . $elementId . '"');
            $result .= htmlspecialchars($this->source, ENT_QUOTES, 'UTF-8');
            $result .= wf_tag('pre', true);
            $result .= wf_tag('div', true);

            $initCode = '';
            if (!self::$configured) {
                self::$configured = true;
                $initCode = 'mermaid.initialize({ startOnLoad: false, theme: \'' . $theme . '\' });';
            }

            $result .= wf_tag('script');
            $result .= '$(document).ready(function() {
                if (typeof mermaid === "undefined") {
                    return;
                }
                ' . $initCode . '
                var node = document.getElementById("' . $elementId . '");
                if (node) {
                    mermaid.run({ nodes: [node] });
                }
            });';
            $result .= wf_tag('script', true);
        }
        return ($result);
    }

}
