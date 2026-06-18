<?php

/**
 * Minimalistic KML reader/writer for Point and LineString placemarks.
 */
class TinyKML {

    /**
     * KML 2.2 namespace URI
     */
    const KML_NS = 'http://www.opengis.net/kml/2.2';

    /**
     * Parsed point placemarks
     *
     * @var array
     */
    protected $points = array();

    /**
     * Parsed line placemarks
     *
     * @var array
     */
    protected $lines = array();

    /**
     * Last error message
     *
     * @var string
     */
    protected $lastError = '';

    /**
     * Creates TinyKML instance and optionally loads file from path
     *
     * @param string $filePath path to .kml or .kmz file
     */
    public function __construct($filePath = '') {
        $filePath = trim((string) $filePath);
        if ($filePath !== '') {
            $this->loadFromFile($filePath);
        }
    }

    /**
     * Clears parsed data and last error
     *
     * @return void
     */
    public function clear() {
        $this->points = array();
        $this->lines = array();
        $this->lastError = '';
    }

    /**
     * Returns last error message
     *
     * @return string
     */
    public function getLastError() {
        return ($this->lastError);
    }

    /**
     * Returns parsed point placemarks
     *
     * Each item: name, description, lat, lng
     *
     * @return array
     */
    public function getPoints() {
        return ($this->points);
    }

    /**
     * Returns parsed line placemarks
     *
     * Each item: name, description, points (array of lat/lng), color, width
     *
     * @return array
     */
    public function getLines() {
        return ($this->lines);
    }

    /**
     * Adds point placemark for export
     *
     * @param string $name
     * @param mixed $lat
     * @param mixed $lng
     * @param string $description
     *
     * @return void
     */
    public function addPoint($name, $lat, $lng, $description = '') {
        if (is_numeric($lat) and is_numeric($lng)) {
            $this->points[] = array(
                'name' => (string) $name,
                'description' => (string) $description,
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            );
        }
    }

    /**
     * Adds line placemark for export
     *
     * Points may be array(lat,lng) or array('lat'=>, 'lng'=>)
     *
     * @param string $name
     * @param array $points
     * @param string $description
     * @param string $color #rrggbb
     * @param int $width
     *
     * @return void
     */
    public function addLine($name, $points, $description = '', $color = '', $width = 0) {
        $normalized = $this->normalizePointsList($points);
        if (count($normalized) > 1) {
            $lineItem = array(
                'name' => (string) $name,
                'description' => (string) $description,
                'points' => $normalized,
                'color' => (string) $color,
                'width' => 0,
            );
            if (is_numeric($width)) {
                $lineItem['width'] = (int) $width;
            }
            $this->lines[] = $lineItem;
        }
    }

    /**
     * Replaces point placemarks list
     *
     * @param array $points
     *
     * @return void
     */
    public function setPoints($points) {
        $this->points = array();
        if (is_array($points)) {
            foreach ($points as $io => $each) {
                if (is_array($each)) {
                    if (isset($each['lat']) and isset($each['lng'])) {
                        $name = isset($each['name']) ? $each['name'] : '';
                        $description = isset($each['description']) ? $each['description'] : '';
                        $this->addPoint($name, $each['lat'], $each['lng'], $description);
                    }
                }
            }
        }
    }

    /**
     * Replaces line placemarks list
     *
     * @param array $lines
     *
     * @return void
     */
    public function setLines($lines) {
        $this->lines = array();
        if (is_array($lines)) {
            foreach ($lines as $io => $each) {
                if (is_array($each) and isset($each['points']) and is_array($each['points'])) {
                    $name = isset($each['name']) ? $each['name'] : '';
                    $description = isset($each['description']) ? $each['description'] : '';
                    $color = isset($each['color']) ? $each['color'] : '';
                    $width = isset($each['width']) ? $each['width'] : 0;
                    $this->addLine($name, $each['points'], $description, $color, $width);
                }
            }
        }
    }

    /**
     * Builds KML document XML string from current points and lines
     *
     * @param string $documentName
     *
     * @return string
     */
    public function toString($documentName = '') {
        $result = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $result .= '<kml xmlns="' . self::KML_NS . '">' . "\n";
        $result .= '<Document>' . "\n";
        $documentName = trim((string) $documentName);
        if ($documentName !== '') {
            $result .= '<name>' . $this->escapeXml($documentName) . '</name>' . "\n";
        }
        if (!empty($this->points)) {
            foreach ($this->points as $io => $eachPoint) {
                $result .= $this->buildPointPlacemarkXml($eachPoint);
            }
        }
        if (!empty($this->lines)) {
            foreach ($this->lines as $io => $eachLine) {
                $result .= $this->buildLinePlacemarkXml($eachLine);
            }
        }
        $result .= '</Document>' . "\n";
        $result .= '</kml>' . "\n";
        return ($result);
    }

    /**
     * Saves current KML document to filesystem path
     *
     * @param string $path
     * @param string $documentName
     *
     * @return bool
     */
    public function saveToFile($path, $documentName = '') {
        $result = false;
        $this->lastError = '';
        $path = trim((string) $path);
        if ($path === '') {
            $this->lastError = 'TinyKML: empty output path';
        } else {
            $kml = $this->toString($documentName);
            $written = @file_put_contents($path, $kml);
            if ($written === false) {
                $this->lastError = 'TinyKML: unable to write file';
            } else {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Loads KML or KMZ file from filesystem path
     *
     * @param string $path
     *
     * @return bool
     */
    public function loadFromFile($path) {
        $result = false;
        $this->clear();
        $path = trim((string) $path);
        if ($path === '' or !is_readable($path) or !is_file($path)) {
            $this->lastError = 'TinyKML: file is not readable';
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'kmz') {
                $kml = $this->extractKmlFromKmz($path);
                if ($kml === false) {
                    $result = false;
                } else {
                    $result = $this->loadFromString($kml);
                }
            } else {
                $kml = @file_get_contents($path);
                if ($kml === false or $kml === '') {
                    $this->lastError = 'TinyKML: unable to read file contents';
                } else {
                    $result = $this->loadFromString($kml);
                }
            }
        }
        return ($result);
    }

    /**
     * Loads KML markup from string
     *
     * @param string $kml
     *
     * @return bool
     */
    public function loadFromString($kml) {
        $result = false;
        $this->points = array();
        $this->lines = array();
        $this->lastError = '';
        $kml = trim((string) $kml);
        if ($kml === '') {
            $this->lastError = 'TinyKML: empty KML payload';
        } else {
            if (function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader(true);
            }
            $dom = new DOMDocument();
            $prev = libxml_use_internal_errors(true);
            $loaded = $dom->loadXML($kml);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            if ($loaded === false) {
                $this->lastError = 'TinyKML: invalid XML';
            } else {
                $styleMap = $this->buildStyleMap($dom);
                $placemarks = $dom->getElementsByTagName('Placemark');
                if ($placemarks->length > 0) {
                    for ($i = 0; $i < $placemarks->length; $i++) {
                        $node = $placemarks->item($i);
                        if ($node instanceof DOMElement) {
                            $this->parsePlacemark($node, $styleMap);
                        }
                    }
                }
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Extracts first .kml entry from KMZ archive
     *
     * @param string $path
     *
     * @return string|false
     */
    protected function extractKmlFromKmz($path) {
        $result = false;
        if (!class_exists('ZipArchive')) {
            $this->lastError = 'TinyKML: ZipArchive is not available';
        } else {
            $zip = new ZipArchive();
            $opened = $zip->open($path);
            if ($opened !== true) {
                $this->lastError = 'TinyKML: unable to open KMZ archive';
            } else {
                $preferred = array('doc.kml', 'Doc.kml');
                $foundName = '';
                $idx = 0;
                while ($idx < count($preferred)) {
                    $candidate = $preferred[$idx];
                    $content = $zip->getFromName($candidate);
                    if ($content !== false and trim($content) !== '') {
                        $foundName = $candidate;
                        $result = $content;
                        break;
                    }
                    $idx++;
                }
                if ($result === false) {
                    $kmlIndex = 0;
                    while ($kmlIndex < $zip->numFiles) {
                        $entryName = $zip->getNameIndex($kmlIndex);
                        if (is_string($entryName)) {
                            $entryExt = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                            if ($entryExt === 'kml') {
                                $content = $zip->getFromName($entryName);
                                if ($content !== false and trim($content) !== '') {
                                    $result = $content;
                                    break;
                                }
                            }
                        }
                        $kmlIndex++;
                    }
                }
                if ($result === false) {
                    $this->lastError = 'TinyKML: no KML entry found inside KMZ';
                }
                $zip->close();
            }
        }
        return ($result);
    }

    /**
     * Builds map of style id => line style options
     *
     * @param DOMDocument $dom
     *
     * @return array
     */
    protected function buildStyleMap($dom) {
        $result = array();
        $styles = $dom->getElementsByTagName('Style');
        if ($styles->length > 0) {
            for ($i = 0; $i < $styles->length; $i++) {
                $styleNode = $styles->item($i);
                if ($styleNode instanceof DOMElement) {
                    $styleId = trim($styleNode->getAttribute('id'));
                    if ($styleId !== '') {
                        $lineStyle = $this->extractLineStyle($styleNode);
                        if (!empty($lineStyle)) {
                            $result[$styleId] = $lineStyle;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Parses single Placemark node
     *
     * @param DOMElement $node
     * @param array $styleMap
     *
     * @return void
     */
    protected function parsePlacemark($node, $styleMap) {
        $name = $this->getDirectChildText($node, 'name');
        $description = $this->getDirectChildText($node, 'description');
        $lineStyle = $this->resolvePlacemarkLineStyle($node, $styleMap);

        $points = $node->getElementsByTagName('Point');
        if ($points->length > 0) {
            for ($i = 0; $i < $points->length; $i++) {
                $pointNode = $points->item($i);
                if ($pointNode instanceof DOMElement) {
                    $coords = $this->getChildCoordinates($pointNode);
                    if (count($coords) > 0) {
                        $this->points[] = array(
                            'name' => $name,
                            'description' => $description,
                            'lat' => $coords[0]['lat'],
                            'lng' => $coords[0]['lng'],
                        );
                    }
                }
            }
        }

        $lineStrings = $node->getElementsByTagName('LineString');
        if ($lineStrings->length > 0) {
            for ($i = 0; $i < $lineStrings->length; $i++) {
                $lineNode = $lineStrings->item($i);
                if ($lineNode instanceof DOMElement) {
                    $coords = $this->getChildCoordinates($lineNode);
                    if (count($coords) > 1) {
                        $lineItem = array(
                            'name' => $name,
                            'description' => $description,
                            'points' => $coords,
                            'color' => '',
                            'width' => 0,
                        );
                        if (isset($lineStyle['color'])) {
                            $lineItem['color'] = $lineStyle['color'];
                        }
                        if (isset($lineStyle['width'])) {
                            $lineItem['width'] = $lineStyle['width'];
                        }
                        $this->lines[] = $lineItem;
                    }
                }
            }
        }
    }

    /**
     * Resolves line style for placemark from inline Style or styleUrl
     *
     * @param DOMElement $node
     * @param array $styleMap
     *
     * @return array
     */
    protected function resolvePlacemarkLineStyle($node, $styleMap) {
        $result = array();
        $inlineStyles = $node->getElementsByTagName('Style');
        if ($inlineStyles->length > 0) {
            $styleNode = $inlineStyles->item(0);
            if ($styleNode instanceof DOMElement) {
                $result = $this->extractLineStyle($styleNode);
            }
        } else {
            $styleUrl = $this->getDirectChildText($node, 'styleUrl');
            $styleUrl = trim($styleUrl);
            if ($styleUrl !== '' and $styleUrl[0] === '#') {
                $styleId = substr($styleUrl, 1);
                if (isset($styleMap[$styleId])) {
                    $result = $styleMap[$styleId];
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts LineStyle color and width from Style node
     *
     * @param DOMElement $styleNode
     *
     * @return array
     */
    protected function extractLineStyle($styleNode) {
        $result = array();
        $lineStyles = $styleNode->getElementsByTagName('LineStyle');
        if ($lineStyles->length > 0) {
            $lineStyleNode = $lineStyles->item(0);
            if ($lineStyleNode instanceof DOMElement) {
                $colorRaw = $this->getDirectChildText($lineStyleNode, 'color');
                $widthRaw = $this->getDirectChildText($lineStyleNode, 'width');
                if ($colorRaw !== '') {
                    $hex = $this->kmlColorToHex($colorRaw);
                    if ($hex !== '') {
                        $result['color'] = $hex;
                    }
                }
                if ($widthRaw !== '' and is_numeric($widthRaw)) {
                    $result['width'] = (int) $widthRaw;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns coordinates child text for geometry node
     *
     * @param DOMElement $geometryNode
     *
     * @return array
     */
    protected function getChildCoordinates($geometryNode) {
        $result = array();
        $coordsRaw = $this->getDirectChildText($geometryNode, 'coordinates');
        if ($coordsRaw !== '') {
            $result = $this->parseCoordinates($coordsRaw);
        }
        return ($result);
    }

    /**
     * Parses KML coordinates string into lat/lng pairs
     *
     * KML order is lon,lat[,alt]
     *
     * @param string $raw
     *
     * @return array
     */
    protected function parseCoordinates($raw) {
        $result = array();
        $raw = trim((string) $raw);
        if ($raw !== '') {
            $raw = str_replace(array("\r\n", "\n", "\r", "\t"), ' ', $raw);
            $chunks = preg_split('/\s+/', $raw);
            if (is_array($chunks)) {
                foreach ($chunks as $io => $chunk) {
                    $chunk = trim($chunk);
                    if ($chunk !== '') {
                        $parts = explode(',', $chunk);
                        if (count($parts) >= 2) {
                            $lng = trim($parts[0]);
                            $lat = trim($parts[1]);
                            if ($lat !== '' and $lng !== '' and is_numeric($lat) and is_numeric($lng)) {
                                $result[] = array(
                                    'lat' => (float) $lat,
                                    'lng' => (float) $lng,
                                );
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Converts KML aabbggrr color to #rrggbb
     *
     * @param string $kmlColor
     *
     * @return string
     */
    protected function kmlColorToHex($kmlColor) {
        $result = '';
        $kmlColor = strtolower(trim((string) $kmlColor));
        $kmlColor = preg_replace('/[^0-9a-f]/', '', $kmlColor);
        if (strlen($kmlColor) === 6) {
            $kmlColor = 'ff' . $kmlColor;
        }
        if (strlen($kmlColor) === 8) {
            $bb = substr($kmlColor, 2, 2);
            $gg = substr($kmlColor, 4, 2);
            $rr = substr($kmlColor, 6, 2);
            $result = '#' . $rr . $gg . $bb;
        }
        return ($result);
    }

    /**
     * Converts #rrggbb color to KML aabbggrr format
     *
     * @param string $hex
     * @param string $alpha
     *
     * @return string
     */
    protected function hexToKmlColor($hex, $alpha = 'ff') {
        $result = '';
        $hex = strtolower(trim((string) $hex));
        $hex = ltrim($hex, '#');
        $hex = preg_replace('/[^0-9a-f]/', '', $hex);
        if (strlen($hex) === 6) {
            $rr = substr($hex, 0, 2);
            $gg = substr($hex, 2, 2);
            $bb = substr($hex, 4, 2);
            $alpha = strtolower(trim((string) $alpha));
            $alpha = preg_replace('/[^0-9a-f]/', '', $alpha);
            if (strlen($alpha) !== 2) {
                $alpha = 'ff';
            }
            $result = $alpha . $bb . $gg . $rr;
        }
        return ($result);
    }

    /**
     * Normalizes points list to array of lat/lng pairs
     *
     * @param array $points
     *
     * @return array
     */
    protected function normalizePointsList($points) {
        $result = array();
        if (is_array($points)) {
            foreach ($points as $io => $each) {
                $lat = '';
                $lng = '';
                if (is_array($each)) {
                    if (isset($each['lat']) and isset($each['lng'])) {
                        $lat = $each['lat'];
                        $lng = $each['lng'];
                    } else {
                        if (count($each) >= 2) {
                            $lat = $each[0];
                            $lng = $each[1];
                        }
                    }
                }
                if ($lat !== '' and $lng !== '' and is_numeric($lat) and is_numeric($lng)) {
                    $result[] = array(
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                    );
                }
            }
        }
        return ($result);
    }

    /**
     * Builds KML coordinates string from lat/lng pairs
     *
     * @param array $points
     *
     * @return string
     */
    protected function buildCoordinatesString($points) {
        $result = '';
        $chunks = array();
        if (is_array($points)) {
            foreach ($points as $io => $each) {
                if (is_array($each) and isset($each['lat']) and isset($each['lng'])) {
                    if (is_numeric($each['lat']) and is_numeric($each['lng'])) {
                        $chunks[] = (float) $each['lng'] . ',' . (float) $each['lat'] . ',0';
                    }
                }
            }
        }
        if (!empty($chunks)) {
            $result = implode(' ', $chunks);
        }
        return ($result);
    }

    /**
     * Builds XML for point Placemark
     *
     * @param array $point
     *
     * @return string
     */
    protected function buildPointPlacemarkXml($point) {
        $result = '<Placemark>' . "\n";
        if (isset($point['name']) and trim((string) $point['name']) !== '') {
            $result .= '<name>' . $this->escapeXml($point['name']) . '</name>' . "\n";
        }
        if (isset($point['description']) and trim((string) $point['description']) !== '') {
            $result .= '<description>' . $this->escapeXml($point['description']) . '</description>' . "\n";
        }
        $coords = $this->buildCoordinatesString(array(
            array(
                'lat' => $point['lat'],
                'lng' => $point['lng'],
            ),
        ));
        $result .= '<Point><coordinates>' . $coords . '</coordinates></Point>' . "\n";
        $result .= '</Placemark>' . "\n";
        return ($result);
    }

    /**
     * Builds XML for line Placemark
     *
     * @param array $line
     *
     * @return string
     */
    protected function buildLinePlacemarkXml($line) {
        $result = '<Placemark>' . "\n";
        if (isset($line['name']) and trim((string) $line['name']) !== '') {
            $result .= '<name>' . $this->escapeXml($line['name']) . '</name>' . "\n";
        }
        if (isset($line['description']) and trim((string) $line['description']) !== '') {
            $result .= '<description>' . $this->escapeXml($line['description']) . '</description>' . "\n";
        }
        $color = isset($line['color']) ? $line['color'] : '';
        $width = isset($line['width']) ? (int) $line['width'] : 0;
        if ($color !== '' or $width > 0) {
            $result .= '<Style><LineStyle>';
            if ($color !== '') {
                $kmlColor = $this->hexToKmlColor($color);
                if ($kmlColor !== '') {
                    $result .= '<color>' . $kmlColor . '</color>';
                }
            }
            if ($width > 0) {
                $result .= '<width>' . $width . '</width>';
            }
            $result .= '</LineStyle></Style>' . "\n";
        }
        $coords = $this->buildCoordinatesString($line['points']);
        $result .= '<LineString><coordinates>' . $coords . '</coordinates></LineString>' . "\n";
        $result .= '</Placemark>' . "\n";
        return ($result);
    }

    /**
     * Escapes text for XML node content
     *
     * @param string $text
     *
     * @return string
     */
    protected function escapeXml($text) {
        return (htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Returns trimmed text of direct child element by tag name
     *
     * @param DOMElement $parent
     * @param string $tagName
     *
     * @return string
     */
    protected function getDirectChildText($parent, $tagName) {
        $result = '';
        if ($parent->hasChildNodes()) {
            foreach ($parent->childNodes as $child) {
                if ($child instanceof DOMElement and $child->tagName === $tagName) {
                    $result = trim($child->textContent);
                    break;
                }
            }
        }
        return ($result);
    }
}
