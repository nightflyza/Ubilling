<?php

/**
 * One-way migration of legacy VOLS map data into CustMaps format.
 */
class VolzConvert {

    const TABLE_VOLS_DOCS = 'vols_docs';
    const TABLE_VOLS_LINES = 'vols_lines';
    const TABLE_VOLS_MARKS = 'vols_marks';
    const TABLE_VOLS_MARK_TYPES = 'vols_marks_types';
    const TABLE_CUSTMAPS = 'custmaps';

    /**
     * File extensions accepted by FileStorage (mirror of api.filestorage.php).
     *
     * @return array
     */
    protected static function fileStorageAllowedExtensionsFlip() {
        $ext = array(
            'jpg',
            'gif',
            'png',
            'jpeg',
            'jfif',
            'bmp',
            'webp',
            'dia',
            'xls',
            'xlsx',
            'doc',
            'odt',
            'ods',
            'docx',
            'ppt',
            'pptx',
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
        $result = array_flip($ext);
        return ($result);
    }

    /**
     * Resolves path from vols_docs to a readable filesystem path.
     *
     * @param string $path
     *
     * @return string|false
     */
    protected static function resolveVolsDocumentPath($path) {
        $result = false;
        $path = trim((string) $path);
        if ($path === '' or $path === '/') {
            return ($result);
        }
        $path = str_replace('\\', '/', $path);
        $candidates = array();
        $candidates[] = $path;
        if (strlen($path) > 0 and $path[0] !== '/') {
            $candidates[] = RCMS_ROOT_PATH . $path;
        }
        $bn = basename($path);
        if ($bn !== '' and $bn !== '.' and $bn !== '..') {
            $candidates[] = DATA_PATH . 'documents/vols/' . $bn;
        }
        foreach ($candidates as $io => $candidate) {
            if (is_readable($candidate) and is_file($candidate)) {
                $result = $candidate;
                break;
            }
        }
        return ($result);
    }

    /**
     * Maps VOLS mark type / model text to CustMaps item type key.
     *
     * @param string $type
     * @param string $model
     *
     * @return string
     */
    public static function mapVolzTypeToCustmap($type, $model) {
        $result = 'other';
        $t = (string) $type;
        $m = (string) $model;
        if (function_exists('mb_strtolower')) {
            $hay = mb_strtolower($t . ' ' . $m, 'UTF-8');
        } else {
            $hay = strtolower($t . ' ' . $m);
        }

        if (self::haystackHasAny($hay, array('опора', 'столб', 'pillar', 'стовб'))) {
            $result = 'pillar';
        } else {
            if (self::haystackHasAny($hay, array('колодязь', 'люк', 'manhole'))) {
                $result = 'manhole';
            } else {
                if (self::haystackHasAny($hay, array('муфта', 'coupling', 'splice'))) {
                    $result = 'coupling';
                } else {
                    if (self::haystackHasAny($hay, array('вузол', 'node','комутація','дільник'))) {
                        $result = 'node';
                    } else {
                        if (self::haystackHasAny($hay, array('коробка', 'box','ящик'))) {
                            $result = 'box';
                        } else {
                            if (self::haystackHasAny($hay, array('підсилювач', 'усилитель', 'amplifier'))) {
                                $result = 'amplifier';
                            } else {
                                if (self::haystackHasAny($hay, array('приймач', 'приемник', 'optrec', 'optical', 'receiver', 'оптич'))) {
                                    $result = 'optrec';
                                } else {
                                    if (self::haystackHasAny($hay, array('камера', 'camera'))) {
                                        $result = 'camera';
                                    } else {
                                        if (self::haystackHasAny($hay, array('wifi', 'wi-fi', 'вайфай'))) {
                                            $result = 'wifi';
                                        } else {
                                            $result = 'other';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * @param string $hay
     * @param array  $needles
     *
     * @return bool
     */
    protected static function haystackHasAny($hay, $needles) {
        $result = false;
        if (!empty($needles)) {
            foreach ($needles as $io => $needle) {
                if (strpos($hay, $needle) !== false) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts first lat,lng pair from decoded VOLS geo (Yandex-style nested arrays).
     *
     * @param mixed $node
     *
     * @return string|false
     */
    protected static function extractFirstLatLngPair($node) {
        $result = false;
        if (is_array($node)) {
            if (count($node) == 2 and is_numeric($node[0]) and is_numeric($node[1])) {
                $lat = trim((string) $node[0]);
                $lng = trim((string) $node[1]);
                if ($lat !== '' and $lng !== '') {
                    $result = $lat . ',' . $lng;
                }
            } else {
                foreach ($node as $io => $child) {
                    if ($result === false) {
                        $tmp = self::extractFirstLatLngPair($child);
                        if ($tmp !== false) {
                            $result = $tmp;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Converts VOLS placemark geo string to CustMaps "lat,lng".
     *
     * @param string $geo
     *
     * @return string|false
     */
    public static function volzPlacemarkGeoToLatLng($geo) {
        $result = false;
        $geo = trim((string) $geo);
        if ($geo !== '' and $geo !== '[  ]') {
            $decoded = json_decode($geo, true);
            if (is_array($decoded)) {
                $pair = self::extractFirstLatLngPair($decoded);
                if ($pair !== false) {
                    $result = $pair;
                }
            }
        }
        return ($result);
    }

    /**
     * Converts VOLS polyline geo to JSON array of [lat,lng] for custmaps_lines.geo.
     *
     * @param string $geo
     *
     * @return string|false
     */
    public static function volzLineGeoToCustmapsJson($geo) {
        $result = false;
        $geo = trim((string) $geo);
        if ($geo !== '' and $geo !== '[  ]') {
            $decoded = json_decode($geo, true);
            $points = array();
            if (is_array($decoded)) {
                self::collectLinePoints($decoded, $points);
            }
            if (count($points) > 1) {
                $result = json_encode($points);
            }
        }
        return ($result);
    }

    /**
     * Walks nested coordinate arrays and appends [lat,lng] pairs.
     *
     * @param mixed $node
     * @param array $points
     *
     * @return void
     */
    protected static function collectLinePoints($node, &$points) {
        if (is_array($node)) {
            if (count($node) == 2 and is_numeric($node[0]) and is_numeric($node[1])) {
                $lat = (float) $node[0];
                $lng = (float) $node[1];
                $points[] = array($lat, $lng);
            } else {
                foreach ($node as $io => $child) {
                    self::collectLinePoints($child, $points);
                }
            }
        }
    }

    /**
     * Normalizes line color to #rrggbb for CustMaps.
     *
     * @param string $paramColor
     *
     * @return string
     */
    public static function normalizeLineColor($paramColor) {
        $result = CustMaps::LINE_DEFAULT_COLOR;
        $c = trim((string) $paramColor);
        if ($c !== '') {
            if ($c[0] !== '#') {
                $c = '#' . $c;
            }
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $c);
            if (strlen($hex) >= 8) {
                $hex = substr($hex, 0, 6);
            } else {
                if (strlen($hex) >= 6) {
                    $hex = substr($hex, 0, 6);
                } else {
                    $hex = '';
                }
            }
            if (strlen($hex) == 6) {
                $result = '#' . strtolower($hex);
            }
        }
        return ($result);
    }

    /**
     * Truncates UTF-8 string to max length (bytes-safe fallback).
     *
     * @param string $str
     * @param int    $maxLen
     *
     * @return string
     */
    protected static function truncateField($str, $maxLen) {
        $result = (string) $str;
        if (function_exists('mb_strlen') and function_exists('mb_substr')) {
            if (mb_strlen($result, 'UTF-8') > $maxLen) {
                $result = mb_substr($result, 0, $maxLen, 'UTF-8');
            }
        } else {
            if (strlen($result) > $maxLen) {
                $result = substr($result, 0, $maxLen);
            }
        }
        return ($result);
    }

    /**
     * Copies a vols_docs file into FileStorage for given scope and item id.
     *
     * @param string $scope
     * @param int    $newItemId
     * @param string $sourcePath
     * @param array  $allowedExt
     *
     * @return bool true if registered
     */
    protected static function copyVolsFileToFileStorage($scope, $newItemId, $sourcePath, $allowedExt) {
        $result = false;
        $resolved = self::resolveVolsDocumentPath($sourcePath);
        if ($resolved === false) {
            $result = false;
        } else {
            $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
            if (!isset($allowedExt[$ext])) {
                $result = false;
            } else {
                $baseName = basename($resolved);
                $transBase = zb_TranslitString($baseName);
                if ($transBase === '') {
                    $transBase = 'file';
                }
                $newFilename = zb_rand_string(6) . '_' . $transBase;
                $fs = new FileStorage($scope, (string) $newItemId);
                $destDir = RCMS_ROOT_PATH . rtrim($fs->getStoragePath(), '/\\');
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                $fullDest = $destDir . '/' . $newFilename;
                if (@copy($resolved, $fullDest)) {
                    $fs->registerFile($newFilename, $baseName);
                    $result = true;
                } else {
                    $result = false;
                }
            }
        }
        return ($result);
    }

    /**
     * Runs full VOLS → CustMaps conversion.
     *
     * @return array keys: success, error, map_id, marks, lines, files_registered, files_skipped
     */
    public function runConversion() {
        $result = array(
            'success' => false,
            'error' => '',
            'map_id' => 0,
            'marks' => 0,
            'lines' => 0,
            'files_registered' => 0,
            'files_skipped' => 0,
        );

        global $ubillingConfig;
        $alt = $ubillingConfig->getAlter();
        if (empty($alt['VOLS_ENABLED']) or empty($alt['CUSTMAP_ENABLED'])) {
            $result['error'] = __('This module is disabled');
        } else {
            $mapNameRaw = 'Converted VOLZ ' . date('Y-m-d H:i:s');
            $mapName = ubRouting::filters($mapNameRaw, 'mres');
            $mapName = ubRouting::filters($mapName, 'safe');

            $mapsDb = new NyanORM(self::TABLE_CUSTMAPS);
            $mapsDb->data('name', $mapName);
            $mapsDb->create();
            $mapId = (int) $mapsDb->getLastId();

            if ($mapId <= 0) {
                $result['error'] = __('Something went wrong');
            } else {
                $result['map_id'] = $mapId;

                try {

                    $custmaps = new CustMaps();

                    $typeIdToCust = array();
                    $typesRows = simple_queryall("SELECT `id`, `type`, `model` FROM `" . self::TABLE_VOLS_MARK_TYPES . "`");
                    if (!empty($typesRows)) {
                        foreach ($typesRows as $io => $trow) {
                            $tid = (int) $trow['id'];
                            $typeIdToCust[$tid] = self::mapVolzTypeToCustmap($trow['type'], $trow['model']);
                        }
                    }

                    $markOldToNew = array();

                    $marksQuery = "
                        SELECT
                            m.`id`,
                            m.`type_id`,
                            m.`number`,
                            m.`placement`,
                            m.`description`,
                            m.`geo`,
                            t.`type` AS vols_type,
                            t.`model` AS vols_model
                        FROM `" . self::TABLE_VOLS_MARKS . "` m
                   LEFT JOIN `" . self::TABLE_VOLS_MARK_TYPES . "` t ON t.`id` = m.`type_id`
                       WHERE m.`geo` IS NOT NULL AND m.`geo` != '' AND m.`geo` != '[  ]'
                    ";
                    $marksRows = simple_queryall($marksQuery);
                    if (!empty($marksRows)) {
                        foreach ($marksRows as $io => $mrow) {
                            $latLng = self::volzPlacemarkGeoToLatLng($mrow['geo']);
                            if ($latLng !== false) {
                                $typeId = (int) $mrow['type_id'];
                                if (isset($typeIdToCust[$typeId])) {
                                    $custType = $typeIdToCust[$typeId];
                                } else {
                                    $custType = self::mapVolzTypeToCustmap(
                                        isset($mrow['vols_type']) ? $mrow['vols_type'] : '',
                                        isset($mrow['vols_model']) ? $mrow['vols_model'] : ''
                                    );
                                }

                                $numPart = '';
                                if (isset($mrow['number']) and $mrow['number'] !== '' and $mrow['number'] !== null) {
                                    $numPart = '#' . $mrow['number'];
                                } else {
                                    $numPart = '#' . $mrow['id'];
                                }
                                $nameBase = $numPart;
                                if (!empty($mrow['vols_type'])) {
                                    $nameBase .= ' ' . $mrow['vols_type'];
                                }
                                $itemName = self::truncateField($nameBase, 255);

                                $locParts = array();
                                if (!empty($mrow['placement'])) {
                                    $locParts[] = trim($mrow['placement']);
                                }
                                if (!empty($mrow['description'])) {
                                    $locParts[] = trim($mrow['description']);
                                }
                                $itemLocation = self::truncateField(implode(' - ', $locParts), 255);

                                $custmaps->itemCreate($mapId, $custType, $latLng, $itemName, $itemLocation);

                                $itemsDb = new NyanORM(CustMaps::TABLE_ITEMS);
                                $newItemId = (int) $itemsDb->getLastId();
                                if ($newItemId > 0) {
                                    $markOldToNew[(int) $mrow['id']] = $newItemId;
                                    $result['marks'] = $result['marks'] + 1;
                                }
                            }
                        }
                    }

                    $allowedExt = self::fileStorageAllowedExtensionsFlip();
                    if (!empty($markOldToNew)) {
                        foreach ($markOldToNew as $oldMarkId => $newItemId) {
                            $oldMarkId = (int) $oldMarkId;
                            $newItemId = (int) $newItemId;
                            $docs = simple_queryall("SELECT * FROM `" . self::TABLE_VOLS_DOCS . "` WHERE `mark_id`='" . $oldMarkId . "'");
                            if (!empty($docs)) {
                                foreach ($docs as $dio => $doc) {
                                    if (!empty($doc['path'])) {
                                        $ok = self::copyVolsFileToFileStorage('CUSTMAPMARKERS', $newItemId, $doc['path'], $allowedExt);
                                        if ($ok) {
                                            $result['files_registered'] = $result['files_registered'] + 1;
                                        } else {
                                            $result['files_skipped'] = $result['files_skipped'] + 1;
                                        }
                                    } else {
                                        $result['files_skipped'] = $result['files_skipped'] + 1;
                                    }
                                }
                            }
                        }
                    }

                    $lineOldToNew = array();

                    $linesRows = simple_queryall("SELECT * FROM `" . self::TABLE_VOLS_LINES . "` WHERE `geo` IS NOT NULL AND `geo` != '' AND `geo` != '[  ]'");
                    if (!empty($linesRows)) {
                        foreach ($linesRows as $lio => $lrow) {
                            $lineGeoJson = self::volzLineGeoToCustmapsJson($lrow['geo']);
                            if ($lineGeoJson !== false) {
                                $ps = isset($lrow['point_start']) ? trim($lrow['point_start']) : '';
                                $pe = isset($lrow['point_end']) ? trim($lrow['point_end']) : '';
                                if ($ps === '' and $pe === '') {
                                    $lineName = '';
                                } else {
                                    if ($ps !== '' and $pe !== '') {
                                        $lineName = $ps . ' - ' . $pe;
                                    } else {
                                        if ($ps !== '') {
                                            $lineName = $ps;
                                        } else {
                                            $lineName = $pe;
                                        }
                                    }
                                    $lineName = self::truncateField($lineName, 255);
                                }
                                $fibers = (int) $lrow['fibers_amount'];
                                $lengthM = isset($lrow['length']) ? (string) $lrow['length'] : '0';
                                $color = self::normalizeLineColor($lrow['param_color']);
                                $width = (int) $lrow['param_width'];
                                if ($width <= 0) {
                                    $width = CustMaps::LINE_DEFAULT_WIDTH;
                                }
                                $descr = isset($lrow['description']) ? $lrow['description'] : '';

                                $custmaps->lineCreate($mapId, $lineName, $fibers, $lengthM, $color, $width, $descr, $lineGeoJson);

                                $linesDb = new NyanORM(CustMaps::TABLE_LINES);
                                $newLineId = (int) $linesDb->getLastId();
                                if ($newLineId > 0) {
                                    $lineOldToNew[(int) $lrow['id']] = $newLineId;
                                    $result['lines'] = $result['lines'] + 1;
                                }
                            }
                        }
                    }

                    if (!empty($lineOldToNew)) {
                        foreach ($lineOldToNew as $oldLineId => $newLineId) {
                            $oldLineId = (int) $oldLineId;
                            $newLineId = (int) $newLineId;
                            $docs = simple_queryall("SELECT * FROM `" . self::TABLE_VOLS_DOCS . "` WHERE `line_id`='" . $oldLineId . "'");
                            if (!empty($docs)) {
                                foreach ($docs as $dio => $doc) {
                                    if (!empty($doc['path'])) {
                                        $ok = self::copyVolsFileToFileStorage('CUSTMAPLINES', $newLineId, $doc['path'], $allowedExt);
                                        if ($ok) {
                                            $result['files_registered'] = $result['files_registered'] + 1;
                                        } else {
                                            $result['files_skipped'] = $result['files_skipped'] + 1;
                                        }
                                    } else {
                                        $result['files_skipped'] = $result['files_skipped'] + 1;
                                    }
                                }
                            }
                        }
                    }

                    $result['success'] = true;
                    log_register(
                        'VOLZCONVERT MAP [' . $mapId . '] MARKS [' . $result['marks'] . '] LINES [' . $result['lines'] . '] FILES_OK [' . $result['files_registered'] . '] FILES_SKIP [' . $result['files_skipped'] . ']'
                    );

                } catch (Exception $e) {
                    $result['success'] = false;
                    $result['error'] = $e->getMessage();
                    log_register('VOLZCONVERT FAIL MAP [' . $mapId . '] `' . $e->getMessage() . '`');
                }
            }
        }

        return ($result);
    }

}

if (cfr('ROOT')) {

    $altCfg = $ubillingConfig->getAlter();

    if (!empty($altCfg['VOLS_ENABLED']) and !empty($altCfg['CUSTMAP_ENABLED'])) {

        if (ubRouting::checkGet('volzconvert_ok')) {
            $okMapId = ubRouting::get('mapid', 'int');
            $okMarks = ubRouting::get('m', 'int');
            $okLines = ubRouting::get('l', 'int');
            $okFiles = ubRouting::get('f', 'int');
            $okSkip = ubRouting::get('s', 'int');
            if (!empty($okMapId)) {
                $msg = __('Done') . ': ' . __('Markers') . ' ' . (int) $okMarks . ', ' . __('Lines') . ' ' . (int) $okLines;
                $msg .= ', ' . __('Files') . ' ' . (int) $okFiles;
                if (!empty($okSkip)) {
                    $msg .= ' (' . __('skipped') . ' ' . (int) $okSkip . ')';
                }
                $messages = new UbillingMessageHelper();
                show_window('', $messages->getStyledMessage($msg, 'success'));
                $mapUrl = CustMaps::URL_ME . '&' . CustMaps::ROUTE_SHOWMAP . '=' . (int) $okMapId;
                show_window('', wf_Link($mapUrl, __('Show map'), false, 'ubButton'));
                show_window('', wf_BackLink('?module=volzconvert'));
            } else {
                show_error(__('Something went wrong'));
            }
        } else {
            if (ubRouting::checkPost('volzconvert_confirm')) {

                $converter = new VolzConvert();
                $convResult = $converter->runConversion();

                if (!empty($convResult['success'])) {
                    $redirUrl = '?module=volzconvert&volzconvert_ok=1&mapid=' . (int) $convResult['map_id'];
                    $redirUrl .= '&m=' . (int) $convResult['marks'] . '&l=' . (int) $convResult['lines'];
                    $redirUrl .= '&f=' . (int) $convResult['files_registered'] . '&s=' . (int) $convResult['files_skipped'];
                    ubRouting::nav($redirUrl);
                } else {
                    $errText = !empty($convResult['error']) ? $convResult['error'] : __('Something went wrong');
                    show_error($errText);
                }

            } else {

                $warn = __('This will create a new CustMaps map named') . ' <code>Converted VOLZ Y-m-d H:i:s</code>. ';
                $warn .= __('VOLS data will not be deleted; you can run this more than once.') . ' ';
                $warn .= __('Make sure File Storage is enabled if you need attachments migrated.');
                $warn .= wf_delimiter();

                $inputs = wf_HiddenInput('volzconvert_confirm', '1');
                $inputs .= wf_Submit('Convert', '', 'class="ubButton" onclick="return confirm(\'' . __('Are you sure') . '?\');"');
                $form = wf_Form('?module=volzconvert', 'POST', $inputs, 'glamour');

                show_window(__('VOLZ maps converter'), $warn . $form);
            }
        }

        zb_BillingStats(true);

    } else {
        show_error(__('This module is disabled').': CUSTMAP_ENABLED '.__('or').' VOLS_ENABLED '.__('Disabled'));
    }

} else {
    show_error(__('Access denied'));
}
