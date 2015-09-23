<?php if ( cfr('VOLS') ) {
    /**
     * Класс:
     */
    class VOLS {
        
        /* Private variablesЖ */
        private $html;
        private $config = array();
        
        private $polylines  = '';
        private $placemarks = '';
        
        /* Names of forms: */
        const FORM_ADD  = 'form_add';
        const FORM_EDIT = 'form_edit';
        
        /* URLs: */
        const URL_HOME     = '?module=vols&item=map&action=show';
        const URL_MAP_SHOW = '?module=vols&item=map&action=show';
        const URL_MAP_EDIT = '?module=vols&item=map&action=edit';
        
        const URL_MARK_TYPES_LIST = '?module=vols&item=mark_type&action=list';
        const URL_MARK_TYPE_EDIT  = '?module=vols&item=mark_type&action=edit&id=';
        const URL_MARK_TYPE_DEL   = '?module=vols&item=mark_type&action=delete&id=';
        const URL_MARK_TYPE_ADD   = '?module=vols&item=mark_type&action=add';
        
        const URL_MARKS_LIST = '?module=vols&item=mark&action=list';
        const URL_MARK_PLACE = '?module=vols&item=mark&action=place&id=';
        const URL_MARK_DOCS  = '?module=vols&item=mark&action=documents&id=';
        const URL_MARK_EDIT  = '?module=vols&item=mark&action=edit&id=';
        const URL_MARK_DEL   = '?module=vols&item=mark&action=delete&id=';
        const URL_MARK_ADD   = '?module=vols&item=mark&action=add';
        
        const URL_LINES_LIST = '?module=vols&item=line&action=list';
        const URL_LINE_PLACE = '?module=vols&item=line&action=place&id=';
        const URL_LINE_DOCS  = '?module=vols&item=line&action=documents&id=';
        const URL_LINE_EDIT  = '?module=vols&item=line&action=edit&id=';
        const URL_LINE_DEL   = '?module=vols&item=line&action=delete&id=';
        const URL_LINE_ADD   = '?module=vols&item=line&action=add';
        
        const URL_DOC_DOWNLOAD = '?module=vols&item=document&action=download&id=';
        const URL_DOC_DELETE   = '?module=vols&item=document&action=delete&id=';
        
        /* DB tables: */
        const TABLE_DOCS  = 'vols_docs';
        const TABLE_LINES = 'vols_lines';
        const TABLE_MARKS = 'vols_marks';
        const TABLE_MARKS_TYPES = 'vols_marks_types';
        
        /* Maps` constants: */
        const MAP_ID        = 'ymaps';
        const MAP_VAR       = 'yandexMaps';
        const MAP_CLUSTERER = 'clusterer';
        const MAP_LINES_ARR = 'polylines';
        const MAP_MARKS_ARR = 'placemarks';
        
        /**
         * Constructor. Loads `ymaps.ini` configuration file data
         * 
         * @return  boolean     true
         */
        public function __construct() {
            $this->config = parse_ini_file(CONFIG_PATH . 'ymaps.ini', true);
            return true;
        }
        
        /**
         * Generates HTML-code of Yandex.Maps control buttons
         * 
         * @return  string  HTML-code of Yandex.Maps control buttons
         */
        private function map_controls() {
            // Controls:
            $this->html .= wf_Link(self::URL_MARK_TYPES_LIST, __('Types of marks'), false, 'ubButton');
            $this->html .= wf_Link(self::URL_MARKS_LIST, __('VOLS marks'), false, 'ubButton');
            $this->html .= wf_Link(self::URL_LINES_LIST, __('VOLS lines'), false, 'ubButton');
            switch ( true ) {
                case ( strpos($_SERVER['REQUEST_URI'], self::URL_MAP_EDIT) !== false ):
                case ( strpos($_SERVER['REQUEST_URI'], self::URL_MARK_PLACE) !== false ):
                case ( strpos($_SERVER['REQUEST_URI'], self::URL_LINE_PLACE) !== false ):
                    $this->html .= wf_Link(self::URL_MAP_SHOW, __('Save'), false, 'ubButton');
                    break;
                default:
                    $this->html .= wf_Link(self::URL_MAP_EDIT, __('Edit'), false, 'ubButton');
                    break;
            }
            $this->html .= wf_delimiter(1);
            return $this->html;
        }
        
        /**
         * Generates HTML-code of Yandex.Maps container with control buttons
         * 
         * @return  string  HTML-code of Yandex.Maps container
         */
        private function map_container() {
            // Generate control buttons:
            $this->map_controls();
            // Map container:
            $this->html .= wf_tag('div', false, null, 'id="' . self::MAP_ID . '" style="width: 1000; height:800px;"');
            $this->html .= wf_tag('div', true);
            return $this->html;
        }
        
        /**
         * Generates Yandex.Maps JavaScript init code
         * 
         * @return  string  HTML-code of Yandex.Maps ( JavaScript )
         */
        public function map_init($edit = false) {
            // Generate map container:
            $this->map_container();
            
            // Init code:
            $this->html .= '
<script src="https://api-maps.yandex.ru/2.0/?load=package.full&lang=' . $this->config['LANG'] . '"  type="text/javascript"></script>
<script type="text/javascript">
    ymaps.ready(init);
    function init () {
        var ' . self::MAP_MARKS_ARR . ' = new Array();
        var ' . self::MAP_LINES_ARR . '  = new Array();
        var ' . self::MAP_CLUSTERER . '  = new ymaps.Clusterer({
            gridSize: 100,
            clusterDisableClickZoom: false
        });
        var ' . self::MAP_VAR . ' = new ymaps.Map("' . self::MAP_ID . '", {
            zoom: ' . $this->config['ZOOM'] . ',
            type: "yandex#' . $this->config['TYPE'] . '",
            center: [' . ( !empty($this->config['CENTER']) ? $this->config['CENTER'] : 'ymaps.geolocation.latitude, ymaps.geolocation.longitude' ) . '],
            behaviors: ["default", "scrollZoom"]
        }, {
            balloonMaxWidth: 250
        });
        ' . self::MAP_VAR . '.controls
            .add("zoomControl")
            .add("typeSelector")
            .add("mapTools")
            .add("searchControl");
        
        // Функция, преобразующая массив с координатами в строку:
        function coords_to_string(coords) {
            var result = "";
            if ( jQuery.isArray(coords) ) {
                result = "[ ";
                for (var i = 0, l = coords.length; i < l; i++) {
                    if (i > 0) {
                        result += ", ";
                    }
                    result += coords_to_string(coords[i]);
                }
                result += " ]";
            } else if ( typeof coords == "number" ) {
                result = coords.toPrecision(8);
            } else if ( coords.toString ) {
                result = coords.toString();
            }
            return result;
        }
        
        // Функция, высчитывающая длинну линии:
        function polyline_length(coords) {
            var distance = 0;
            if ( coords.length >= 2) {
                for ( var i = 0, l = coords.length - 1; i < l; i++ ) {
                    distance += ymaps.coordSystem.geo.getDistance(coords[ i ], coords[ i + 1 ]);
                }
            }
            return distance.toFixed(2);
        }

        // Функция для сохраниения координат в БД:
        function placemark_geo_save(id, coords) {
            jQuery.ajax({
                type: "POST",
                url: "' . self::URL_MARK_EDIT . '" + id,
                data: {
                    ' . self::FORM_EDIT . ': {
                        geo: coords_to_string(coords)
                    }
                }
            });
            return true;
        }
        
        // Функция для сохраниения координат в БД:
        function polyline_geo_save(id, coords) {
            jQuery.ajax({
                type: "POST",
                url: "' . self::URL_LINE_EDIT . '" + id,
                data: {
                    ' . self::FORM_EDIT . ': {
                        geo: coords_to_string(coords),
                        length: polyline_length(coords)
                    }
                }
            });
            return true;
        }
                        
        // Функция для добавления кластера на карту:
        function clusterize() {
            // Если кластер уже был создан - удаляем:
            if (' . self::MAP_CLUSTERER . '.getBounds() != null ) {
                ' . self::MAP_CLUSTERER . '.remove(' . self::MAP_MARKS_ARR . ');
                ' . self::MAP_VAR . '.geoObjects.remove(' . self::MAP_CLUSTERER . ');
            }
            // Добавляем элементы в кластер:
            ' . self::MAP_CLUSTERER . '.add(' . self::MAP_MARKS_ARR . ');
            // Добавляем кластер на карту:
            ' . self::MAP_VAR . '.geoObjects.add(' . self::MAP_CLUSTERER . ');
        }
        
        ' . $this->show_placemarks($edit) . '
        ' . $this->show_polylines()  . '
        // Размещаем все линии на карте:
        jQuery.map( ' . self::MAP_LINES_ARR . ' , function( polyline ) {
            ' . self::MAP_VAR . '.geoObjects.add( polyline );
        });
        
        clusterize();
    ';
            
            if ( $edit ) {
                $this->html .= '
        jQuery.map( ' . self::MAP_MARKS_ARR . ' , function( placemark ) {
            placemark.events.add(\'dragend\', function ( e ) {
                var target = e.get(\'target\');
                var coords = target.geometry.getCoordinates();
                placemark_geo_save(target.properties.get(\'id\'), coords);
                clusterize();
            });
            placemark.events.add(\'contextmenu\', function ( e ) {
                var target = e.get(\'target\');
                var coords = new Array();
                ' . self::MAP_CLUSTERER . '.remove(target);
                placemark_geo_save(target.properties.get(\'id\'), coords);
            });
        });
        
        jQuery.map( ' . self::MAP_LINES_ARR . ' , function( polyline ) {
            polyline.editor.startEditing();
            polyline.events.add(\'geometrychange\', function ( e ) {
                var target = e.get(\'target\');
                var coords = target.geometry.getCoordinates();
                polyline_geo_save(target.properties.get(\'id\'), coords);
            });
            polyline.editor.options.set({
                menuManager: function ( menuItems, model ) {
                    menuItems.push({
                        id: \'RemoveFromMap\',
                        title: \'' . __('Remove from map') . '\',
                        onClick: function ( graphicsObject, pointIndex, coordPath ) {
                            var coords = new Array();
                            polyline_geo_save(polyline.properties.get(\'id\'), coords);
                            ' . self::MAP_VAR . '.geoObjects.remove( polyline );
                            graphicsObject.stopEditing();
                        }
                    });
                    return menuItems;
                }
            });
        });
    ';
            }
            return $this->html . '}
</script>
';
        }
        
        /**
         * Places closures on the map, whitch have coords in DB. If
         * $edit_mode was setted to `true` you will be abled to edit
         * position of any placemark. All changes of position will be
         * written to database.
         * 
         * @param   boolean $edit_mode  Is edit mode enabled?
         * @return  string              Javascript code of placemarks
         */
        private function show_placemarks($draggable = false) {
            // Take a closures list from database:
            $query = "
                SELECT
                     `" . self::TABLE_MARKS . "`.`id`,
                     `" . self::TABLE_MARKS . "`.`number`,
                     `" . self::TABLE_MARKS . "`.`placement`,
                     `" . self::TABLE_MARKS . "`.`description`,
                     `" . self::TABLE_MARKS_TYPES . "`.`type`,
                     `" . self::TABLE_MARKS_TYPES . "`.`model`,
                     `" . self::TABLE_MARKS_TYPES . "`.`icon_color`,
                     `" . self::TABLE_MARKS_TYPES . "`.`icon_style`,
                     `" . self::TABLE_MARKS . "`.`geo`
                FROM `" . self::TABLE_MARKS . "`
           LEFT JOIN `" . self::TABLE_MARKS_TYPES . "`
                  ON `" . self::TABLE_MARKS_TYPES . "`.`id` = `" . self::TABLE_MARKS . "`.`type_id` 
               WHERE `" . self::TABLE_MARKS . "`.`geo` != ''
                 AND `" . self::TABLE_MARKS . "`.`geo` != '[  ]'";
            $placemarks = simple_queryall($query);
            
            if ( !empty($placemarks) ) {
                foreach ( $placemarks as $placemark ) {
                    // Actions:
                    $actions  = wf_Link(self::URL_MARK_DOCS . $placemark['id'], web_corporate_icon('Documentation'));
                    $actions  = str_replace("\n", null, $actions);
                    $this->placemarks .= "
        " . self::MAP_MARKS_ARR . ".push(
            new ymaps.Placemark(" . $placemark['geo'] . ", {
                id: " . $placemark['id'] . ",
                iconContent: '" . $placemark['number'] . "',
                balloonContentHeader: '<i>#" . $placemark['number'] . ": " . $placemark['type'] . " </i>',
                balloonContentBody: '<div style=\"text-align: justify; text-indent: 1.5em; margin-top: 5px;\">" . $placemark['description'] . "</div>',
                balloonContentFooter: '" . $actions . "',
            }, {
                draggable: " . ( $draggable ? 'true' : 'false' ) . ",
                preset: 'twirl#" . $placemark['icon_color'] . $placemark['icon_style'] . "Icon'
            })
        );
                    ";
                }
            }
            return $this->placemarks;
        }
        
        /**
         * This function enebles "placemark adding mode". It's add `click` 
         * event handling. After event click coordinates will be added to
         * `geo` column of adding placemark ( WHERE `id` = $placemark_id).
         * It's add `dragend` event handling too.
         * 
         * @param   integer $closure_id 
         * @return  string              
         */
        function place_placemark($id) {
            // Take a closures list from database:
            $query = "
                SELECT
                     `" . self::TABLE_MARKS . "`.`id`,
                     `" . self::TABLE_MARKS . "`.`number`,
                     `" . self::TABLE_MARKS . "`.`placement`,
                     `" . self::TABLE_MARKS . "`.`description`,
                     `" . self::TABLE_MARKS_TYPES . "`.`type`,
                     `" . self::TABLE_MARKS_TYPES . "`.`model`,
                     `" . self::TABLE_MARKS_TYPES . "`.`icon_color`,
                     `" . self::TABLE_MARKS_TYPES . "`.`icon_style`,
                     `" . self::TABLE_MARKS . "`.`geo`
                FROM `" . self::TABLE_MARKS . "`
           LEFT JOIN `" . self::TABLE_MARKS_TYPES . "`
                  ON `" . self::TABLE_MARKS_TYPES . "`.`id` = `" . self::TABLE_MARKS . "`.`type_id` 
               WHERE `" . self::TABLE_MARKS . "`.`id` = '" . $id . "'";
            $placemark = simple_query($query);
            if ( !empty($placemark) ) {
                // Actions:
                $actions = wf_Link(self::URL_MARK_DOCS . $placemark['id'], web_corporate_icon('Documentation'));
                $actions = str_replace("\n", null, $actions);
                $this->placemarks .= "
        " . self::MAP_VAR . ".events.add('click', function (e) {
            // Предотвращаем добавление на карту нескольких
            // одинаковых меток, при повторном клике:
            if ( typeof newPlacemark != 'undefined' ) {
                " . self::MAP_VAR . ".geoObjects.remove( newPlacemark );
            }

            // Размещаем метку на карте:
            var coords = e.get('coordPosition');
            newPlacemark = new ymaps.Placemark(coords, {
                id: " . $placemark['id'] . ",
                iconContent: '" . $placemark['number'] . "',
                balloonContentHeader: '<i>" . $placemark['type'] . " #" . $placemark['number'] . "</i>',
                balloonContentBody:   '" . $placemark['description'] . "',
                balloonContentFooter: '" . $actions . "'
            } , {
                draggable: true,
                preset: 'twirl#" . $placemark['icon_color'] . $placemark['icon_style'] . "Icon',
            });
            " . self::MAP_VAR . ".geoObjects.add( newPlacemark );
            
            // Добавляем координаты клика в БД:
            placemark_geo_save(" . $placemark['id'] . ", coords);

            // Добавляем обработку события перетаскивания:
            newPlacemark.events.add('dragend', function (e) {
                var target = e.get('target');
                var coords = target.geometry.getCoordinates();
                placemark_geo_save(" . $placemark['id'] . ", coords);
            });
        });
                ";
            }
            return true;
        }
        
        /**
         * Places lines on the map, whitch have coords in DB. If $edit_mode
         * was setted to `true` you will be abled to edit geometry of any
         * line. All changes of geometry will be written to database.
         * 
         * @param   boolean $edit_mode  Is edit mode enabled?
         * @return  string              Javascript code of lines
         */
        function show_polylines() {
            // Take a closures list from database:
            $query = "SELECT * FROM `" . self::TABLE_LINES . "` WHERE `geo` IS NOT NULL AND `geo` != '[  ]'";
            $result = simple_queryall($query);
            if ( !empty($result) ) {
                foreach ( $result as $line ) {
                    $this->polylines .= "
        " . self::MAP_LINES_ARR . ".push(
            new ymaps.Polyline(" . $line['geo'] . ", {
                id: " . $line['id'] . ",
                hintContent: '" . $line['point_start'] . " -> " . $line['point_end'] . ", " . $line['length'] . "'
            }, {
                strokeColor: '" . $line['param_color'] . "',
                strokeWidth:  " . $line['param_width'] . "
            })
        );
                    ";
                }
            }
            return $this->polylines;
        }
        
        public function place_polyline($id) {
            // Take a lines list from database:
            $query = "SELECT * FROM `" . self::TABLE_LINES . "` WHERE `id` = '" . $id . "'";
            $polyline = simple_query($query);
            if ( !empty($polyline) ) {
                $this->polylines .= "
        // Добавляем новую линию:
        newPolyline = new ymaps.Polyline( [] , {
            id: " . $polyline['id'] . ",
            hintContent: '" . $polyline['description'] . "'
        }, {
            strokeColor: '" . $polyline['param_color'] . "',
            strokeWidth:  " . $polyline['param_width'] . "
        });
        " . self::MAP_VAR . ".geoObjects.add( newPolyline );

        // Добавляем в контекстное меню кнопку удаления всей линии:
        newPolyline.editor.options.set({
            menuManager: function ( menuItems, model ) {
                menuItems.push({
                    id: 'Cancel',
                    title: '" . __('Remove from map') . "',
                    onClick: function ( graphicsObject, pointIndex, coordPath ) {
                        var coords = new Array();
                        polyline_geo_save(" . $polyline['id'] . ", coords);
                        " . self::MAP_VAR . ".geoObjects.remove( newPolyline );
                        graphicsObject.stopEditing();
                    }
                });
                return menuItems;
            }
        });
        newPolyline.editor.startEditing();	
        newPolyline.editor.startDrawing();

        // При `geometrychange` вносим новые координаты в БД:
        newPolyline.events.add('geometrychange', function (e) {
            var target = e.get('target');
            var coords = target.geometry.getCoordinates();
            polyline_geo_save(" . $polyline['id'] . ", coords);
        });
                ";
            }
            return true;
        }
        
        /**
         * Get employee list from database
         * 
         * @return  array   Employee array ( $id => $name )
         */
        private function get_employee() {
            $query  = "SELECT `id`, `name` FROM `employee`";
            $result = simple_queryall($query);
            $return = array();
            if ( !empty($result) ) {
                foreach ( $result as $employee ) {
                    $return[$employee['id']] = $employee['name'];
                }
            }
            return $return;
        }
        
        /**
         * Get marks types list from database
         * 
         * @return  array   Marks types array ( $id => $type )
         */
        private function get_marks_types() {
            $query  = "SELECT `id`, `type` FROM `" . self::TABLE_MARKS_TYPES . "`";
            $result = simple_queryall($query);
            $return = array();
            if ( !empty($result) ) {
                foreach ( $result as $marks_type ) {
                    $return[$marks_type['id']] = $marks_type['type'];
                }
            }
            return $return;
        }
        
        
        /* MARK TYPES */
        
        
        /**
         * Returns HTML-table, containing existing mark types
         * 
         * @return  string  HTML-table
         */
        public function mark_type_list_show() {
            // Query marks types & amount of eachs is used:
            $query = "SELECT
                    `" . self::TABLE_MARKS_TYPES . "`.`id`,
                    `" . self::TABLE_MARKS_TYPES . "`.`type`,
                    `" . self::TABLE_MARKS_TYPES . "`.`model`,
                    `" . self::TABLE_MARKS_TYPES . "`.`description`,
                    `" . self::TABLE_MARKS_TYPES . "`.`icon_color`,
                    `" . self::TABLE_MARKS_TYPES . "`.`icon_style`,
              COUNT(`" . self::TABLE_MARKS . "`.`id`) AS `amount`
               FROM `" . self::TABLE_MARKS_TYPES . "`
          LEFT JOIN `" . self::TABLE_MARKS . "`
                 ON `" . self::TABLE_MARKS_TYPES . "`.`id` = `" . self::TABLE_MARKS . "`.`type_id`
           GROUP BY `" . self::TABLE_MARKS_TYPES . "`.`id`,  `" . self::TABLE_MARKS_TYPES . "`.`type`
           ORDER BY `" . self::TABLE_MARKS_TYPES . "`.`id`
            ";
            $result = simple_queryall($query);
            // HTML-table header:
            $cells  = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Type'),      100);
            $cells .= wf_TableCell(__('Model'),     150);
            $cells .= wf_TableCell(__('Description'));
            $cells .= wf_TableCell(__('Quantity'),  50);
            $cells .= wf_TableCell(__('Icon'),      125);
            $cells .= wf_TableCell(__('Actions'),   50);
            $rows   =  wf_TableRow($cells, 'row2');
            // HTML-table content:
            if ( !empty($result) ) {
                foreach ( $result as $marks_type ) {
                    $cells  = wf_TableCell($marks_type['id']);
                    $cells .= wf_TableCell($marks_type['type']);
                    $cells .= wf_TableCell($marks_type['model']);
                    $cells .= wf_TableCell($marks_type['description']);
                    $cells .= wf_TableCell($marks_type['amount']);
                    $cells .= wf_TableCell($marks_type['icon_color'] . $marks_type['icon_style']);
                    // Actions:
                    $actions = wf_Link(self::URL_MARK_TYPE_EDIT . $marks_type['id'], web_edit_icon());
                    if ( $marks_type['amount'] == 0 ) {
                        $actions .= wf_Link(self::URL_MARK_TYPE_DEL . $marks_type['id'], web_delete_icon());
                    }
                    $cells .= wf_TableCell($actions);
                    $rows  .= wf_TableRow($cells, 'row3');
                }
            } else {
                $cells = wf_TableCell(__('There is no marks types to show'), null, null, 'colspan="8" align="center"');
                $rows .= wf_TableRow($cells, 'row3');
            }
            // Generate HTML-table:
            return wf_TableBody($rows, '100%', '0', 'sortable');
        }
        
        /**
         * Returns marks type add form
         * 
         * @return  string  Generated HTML-form
         */
        public function mark_type_add_form_show() {
            // Fill in the inputs:
            $inputs  = wf_TextInput(self::FORM_ADD . '[type]', 'Type', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[model]', 'Model', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[description]', 'Description', null, true, '25');
            $inputs .= wf_Selector( self::FORM_ADD . '[icon_color]', array(
                'blue' => 'blue',
                'orange' => 'orange',
                'darkblue' => 'darkblue',
                'pink' => 'pink',
                'darkgreen' => 'darkgreen',
                'red' => 'red',
                'darkorange' => 'darkorange',
                'violet' => 'violet',
                'green' => 'green',
                'white' => 'white',
                'grey' => 'grey',
                'yellow' => 'yellow',
                'lightblue' => 'lightblue',
                'brown' => 'brown',
                'night' => 'night',
                'black' => 'black',
            ), 'Icon', null, true, '25');
            $inputs .= wf_Selector( self::FORM_ADD . '[icon_style]', array(
                '' => '',
                'Dot' => 'Dot',
                'Stretchy' => 'Stretchy'
            ), 'Icon style', null, true, '25');
            $inputs .= wf_Submit('Save', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
                
        /**
         * Returns marks type edit form
         * 
         * @return  string  HTML-form
         */
        public function mark_type_edit_form_show($id) {
            // Get current data from database:
            $query  = "SELECT * FROM `" . self::TABLE_MARKS_TYPES . "` WHERE `id` = '" . $id . "'";
            $result = simple_query($query);
            // Fill in the inputs:
            $inputs  = wf_TextInput(self::FORM_EDIT . '[type]', 'Type', $result['type'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[model]', 'Model', $result['model'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[description]', 'Description', $result['description'], true, '25');
            $inputs .= wf_Selector( self::FORM_EDIT . '[icon_color]', array(
                'blue' => 'blue',
                'orange' => 'orange',
                'darkblue' => 'darkblue',
                'pink' => 'pink',
                'darkgreen' => 'darkgreen',
                'red' => 'red',
                'darkorange' => 'darkorange',
                'violet' => 'violet',
                'green' => 'green',
                'white' => 'white',
                'grey' => 'grey',
                'yellow' => 'yellow',
                'lightblue' => 'lightblue',
                'brown' => 'brown',
                'night' => 'night',
                'black' => 'black'
            ), 'Icon color', $result['icon_color'], true, '25');
            $inputs .= wf_Selector( self::FORM_EDIT . '[icon_style]', array(
                '' => '',
                'Dot' => 'Dot',
                'Stretchy' => 'Stretchy'
            ), 'Icon style', $result['icon_style'], true, '25');
            $inputs .= wf_Submit('Save', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        /**
         * Adds marks type to database
         * 
         * @return  mixed   MySQL-query result
         */
        public function mark_type_add_form_submit($data) {
            $query = "
                INSERT INTO `" . self::TABLE_MARKS_TYPES . "` (
                    `id`,
                    `type`,
                    `model`,
                    `description`,
                    `icon_color`,
                    `icon_style`
                ) VALUES (
                    NULL,
                    '" . $data['type'] . "',
                    '" . $data['model'] . "',
                    '" . $data['description'] . "',
                    '" . $data['icon_color'] . "',
                    '" . ( !empty($data['icon_style']) ? $data['icon_style'] : null ) . "'
                );
            ";
            return nr_query($query);
        }
        
        /**
         * Updates marks type data in database
         * 
         * @return  boolean true
         */
        public function mark_type_edit_form_submit($id, $data) {
            $where = "WHERE `id` = '" . $id . "'";
            foreach ( $data as $column => $new_value ) {
                simple_update_field(self::TABLE_MARKS_TYPES, $column, $new_value, $where);
            }
            return true;
        }
        
        /**
         * Deletes marks type from database
         * 
         * @param   string $id  ID of line
         * @return  mixed       MySQL-query result
         */
        public function mark_type_delete($id) {
            $query = "DELETE FROM `" . self::TABLE_MARKS_TYPES . "` WHERE `id` = '" . $id . "'";
            return nr_query($query);
        }
        
        
        /* MARKS */
        
        
        /**
         * Returns HTML-table, containing existing marks
         * 
         * @return  string  HTML-table
         */
        public function mark_list_show() {
            // Query marks:
            $query  = "SELECT
                `" . self::TABLE_MARKS . "`.`id`,
                `" . self::TABLE_MARKS_TYPES . "`.`type`,
                `" . self::TABLE_MARKS . "`.`number`,
                `" . self::TABLE_MARKS . "`.`placement`,
                `" . self::TABLE_MARKS . "`.`description`,
                `" . self::TABLE_MARKS . "`.`geo`
           FROM `" . self::TABLE_MARKS . "`
      LEFT JOIN `" . self::TABLE_MARKS_TYPES . "`
             ON `" . self::TABLE_MARKS_TYPES . "`.`id` = `" . self::TABLE_MARKS . "`.`type_id`
            ";
            $result = simple_queryall($query);
            // HTML-table header:
            $cells  = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Number'));
            $cells .= wf_TableCell(__('Placement'));
            $cells .= wf_TableCell(__('Description'), 500);
            $cells .= wf_TableCell(__('Actions'), 80);
            $rows   =  wf_TableRow($cells, 'row2');
            // HTML-table content:
            if ( !empty($result) ) {
                foreach ( $result as $mark ) {
                    $cells  = wf_TableCell($mark['id']);
                    $cells .= wf_TableCell($mark['type']);
                    $cells .= wf_TableCell($mark['number']);
                    $cells .= wf_TableCell($mark['placement']);
                    $cells .= wf_TableCell($mark['description']);
                    // Actions:
                    $actions  = wf_Link(self::URL_MARK_DEL  . $mark['id'], web_delete_icon());
                    $actions .= wf_Link(self::URL_MARK_EDIT . $mark['id'], web_edit_icon());
                    $actions .= wf_Link(self::URL_MARK_DOCS . $mark['id'], web_corporate_icon('Documentation'));
                    if ( $mark['geo'] == '[  ]' || empty($mark['geo']) ) {
                        $actions .= wf_Link(self::URL_MARK_PLACE . $mark['id'], web_add_icon(__('Place on map')));
                    }
                    $cells .= wf_TableCell($actions);
                    $rows  .= wf_TableRow($cells, 'row3');
                }
            } else {
                $cells = wf_TableCell(__('There is no marks to show'), null, null, 'colspan="8" align="center"');
                $rows .= wf_TableRow($cells, 'row3');
            }
            // Generate HTML-table:
            return wf_TableBody($rows, '100%', '0', 'sortable');
        }
        
        /**
         * Returns mark add form
         * 
         * @return  string  Generated HTML-form
         */
        public function mark_add_form_show() {
            // Get marks types:
            $types = $this->get_marks_types();
            // Fill in the inputs:
            $inputs  =  wf_Selector(self::FORM_ADD . '[type_id]', $types, 'Type', null, true);
            $inputs .= wf_TextInput(self::FORM_ADD . '[number]', 'Number', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[placement]', 'Placement', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[description]', 'Description', null, true, '25');
            $inputs .= wf_Submit('Save', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        /**
         * Returns mark edit form
         * 
         * @return  string  HTML-form
         */
        public function mark_edit_form_show($id) {
            // Get current data from database:
            $query  = "SELECT * FROM `" . self::TABLE_MARKS . "` WHERE `id` = '" . $id . "'";
            $result = simple_query($query);
            // Get closure types:
            $types = $this->get_marks_types();
            // Fill in the inputs:
            $inputs  =  wf_Selector(self::FORM_EDIT . '[type_id]', $types, 'Type', $result['type_id'], true);
            $inputs .= wf_TextInput(self::FORM_EDIT . '[number]', 'Number', $result['number'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[placement]', 'Placement', $result['placement'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[description]', 'Description', $result['description'], true, '25');
            $inputs .= wf_Submit('Edit', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        /**
         * Adds mark to database
         * 
         * @return  mixed   MySQL-query result
         */
        public function mark_add_form_submit($data) {
            $query = "
                INSERT INTO `" . self::TABLE_MARKS . "` (
                    `id`,
                    `type_id`,
                    `number`,
                    `placement`,
                    `description`,
                    `geo`
                ) VALUES (
                    NULL,
                    '" . $data['type_id'] . "',
                    '" . $data['number'] . "',
                    '" . $data['placement'] . "',
                    '" . $data['description'] . "',
                    NULL
                );
            ";
            return nr_query($query);
        }
        
        /**
         * Updates mark data in database
         * 
         * @return  boolean true
         */
        public function mark_edit_form_submit($id, $data) {
            $where = "WHERE `id` = '" . $id . "'";
            foreach ( $data as $column => $new_value ) {
                simple_update_field(self::TABLE_MARKS, $column, $new_value, $where);
            }
            return true;
        }
        
        /**
         * Deletes mark from database
         * 
         * @param   string $id  ID of line
         * @return  mixed       MySQL-query result
         */
        public function mark_delete($id) {
            $query = "SELECT `id` FROM `" . self::TABLE_DOCS . "` WHERE `mark_id` = '" . $id . "'";
            $result = simple_queryall($query);
            if ( !empty($result) ) {
                foreach( $result as $document ) {
                    $this->document_delete($document['id'], false);
                }
            }
            $query = "DELETE FROM `" . self::TABLE_MARKS . "` WHERE `id` = '" . $id . "'";
            return nr_query($query);
        }
        
        
        /* LINES */
        
        
        /**
         * Generates HTML-table, containing existing lines
         * 
         * @return  string  HTML-table
         */
        public function line_list_show() {
            // Query lines:
            $query  = "SELECT
                     `" . self::TABLE_LINES . "`.`id`,
                     `" . self::TABLE_LINES . "`.`point_start`,
                     `" . self::TABLE_LINES . "`.`point_end`,
                     `" . self::TABLE_LINES . "`.`fibers_amount`,
                     `" . self::TABLE_LINES . "`.`length`,
                     `" . self::TABLE_LINES . "`.`description`,
                     `employee`.`name` AS `engineer`,
                     `" . self::TABLE_LINES . "`.`param_color`,
                     `" . self::TABLE_LINES . "`.`geo`
                FROM `" . self::TABLE_LINES . "`
           LEFT JOIN `employee`
                  ON `" . self::TABLE_LINES . "`.`employee_id` = `employee`.id
            ";
            $result = simple_queryall($query);
            // HTML-table header:
            $cells  = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Starting point'));
            $cells .= wf_TableCell(__('End point'));
            $cells .= wf_TableCell(__('Fibers amount'), 75);
            $cells .= wf_TableCell(__('Length'), 100);
            $cells .= wf_TableCell(__('Description'), 350);
            $cells .= wf_TableCell(__('Engineer'));
            $cells .= wf_TableCell(__('Color'), 60);
            $cells .= wf_TableCell(__('Actions'), 80);
            $rows   =  wf_TableRow($cells, 'row2');
            // HTML-table content:
            if ( !empty($result) ) {
                foreach ( $result as $line ) {
                    // Color decoration:
                    $line['param_color'] = '<span style="color: ' . $line['param_color'] . '">' . $line['param_color'] . '</span>';
                    $cells  = wf_TableCell($line['id']);
                    $cells .= wf_TableCell($line['point_start']);
                    $cells .= wf_TableCell($line['point_end']);
                    $cells .= wf_TableCell($line['fibers_amount']);
                    $cells .= wf_TableCell($line['length']);
                    $cells .= wf_TableCell($line['description']);
                    $cells .= wf_TableCell($line['engineer']);
                    $cells .= wf_TableCell($line['param_color']);
                    // Actions:
                    $actions  = wf_Link(self::URL_LINE_DEL  . $line['id'], web_delete_icon());
                    $actions .= wf_Link(self::URL_LINE_EDIT . $line['id'], web_edit_icon());
                    $actions .= wf_Link(self::URL_LINE_DOCS . $line['id'], web_corporate_icon('Documentation'));
                    if ( empty($line['geo']) || $line['geo'] == '[  ]' ) {
                        $actions .= wf_Link(self::URL_LINE_PLACE . $line['id'], web_add_icon(__('Place on map')));
                    }
                    $cells .= wf_TableCell($actions);
                    $rows  .= wf_TableRow($cells, 'row3');
                }
            } else {
                $cells = wf_TableCell(__('There is no lines to show'), null, null, 'colspan="9" align="center"');
                $rows .= wf_TableRow($cells, 'row3');
            }
            // Generate HTML-table:
            return wf_TableBody($rows, '100%', '0');
        }
        
        /**
         * Returns line add form
         * 
         * @return string Generated HTML-form
         */
        public function line_add_form_show() {
            // Get employee:
            $employee = $this->get_employee();
            // Fill in the inputs:
            $inputs  = wf_TextInput(self::FORM_ADD . '[point_start]', 'Starting point', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[point_end]', 'End point', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[fibers_amount]', 'Fibers amount', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[length]', 'Length', null, true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[description]', 'Description', null, true, '25');
            $inputs .=  wf_Selector(self::FORM_ADD . '[employee_id]', $employee, 'Engineer', null, true);
            $inputs .= wf_ColPicker(self::FORM_ADD . '[param_color]', 'Color', '#f57601', true, '25');
            $inputs .= wf_TextInput(self::FORM_ADD . '[param_width]', 'Line width', 2, true, '25');
            $inputs .= wf_Submit('Save', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        /**
         * Returns line edit form
         * 
         * @return  string  HTML-form
         */
        public function line_edit_form_show($id) {
            // Get employee:
            $employee = $this->get_employee();
            // Get current data from database:
            $query  = "SELECT * FROM `" . self::TABLE_LINES . "` WHERE `id` = '" . $id . "'";
            $result = simple_query($query);
            // Fill in the inputs:
            $inputs  = wf_TextInput(self::FORM_EDIT . '[point_start]', 'Starting point', $result['point_start'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[point_end]', 'End point', $result['point_end'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[fibers_amount]', 'Fibers amount', $result['fibers_amount'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[length]', 'Length', $result['length'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[description]', 'Description', $result['description'], true, '25');
            $inputs .=  wf_Selector(self::FORM_EDIT . '[employee_id]', $employee, 'Engineer', $result['employee_id'], true);
            $inputs .= wf_ColPicker(self::FORM_EDIT . '[param_color]', 'Color', $result['param_color'], true, '25');
            $inputs .= wf_TextInput(self::FORM_EDIT . '[param_width]', 'Line width', $result['param_width'], true, '25');
            $inputs .= wf_Submit('Save', 'ubButton');
            // Generate HTML-form:
            return wf_Form('', 'POST', $inputs, 'glamour');
        }
        
        /**
         * Adds line to database
         * 
         * @return  mixed   Returns MySQL-query result
         */
        public function line_add_form_submit($data) {
            $query = "
                INSERT INTO `" . self::TABLE_LINES . "` (
                    `id`,
                    `point_start`,
                    `point_end`,
                    `fibers_amount`,
                    `length`,
                    `description`,
                    `employee_id`,
                    `param_color`,
                    `param_width`,
                    `geo`
                ) VALUES (
                    NULL,
                    '" . $data['point_start'] . "',
                    '" . $data['point_end'] . "',
                    '" . $data['fibers_amount'] . "',
                    '" . $data['length'] . "',
                    '" . $data['description'] . "',
                    '" . $data['employee_id'] . "',
                    '" . $data['param_color'] . "',
                    '" . $data['param_width'] . "',
                    NULL
                );
            ";
            return nr_query($query);
        }
        
        /**
         * Updates line data in database
         * 
         * @return  boolean true
         */
        public function line_edit_form_submit($id, $data) {
            $where = "WHERE `id` = '" . $id . "'";
            foreach ( $data as $column => $new_value ) {
                simple_update_field(self::TABLE_LINES, $column, $new_value, $where);
            }
            return true;
        }
        
        /**
         * Deletes line from database
         * 
         * @param   string $id  ID of line
         * @return  mixed       MySQL-query result
         */
        public function line_delete($id) {
            $query = "SELECT `id` FROM `" . self::TABLE_DOCS . "` WHERE `line_id` = '" . $id . "'";
            $result = simple_queryall($query);
            if ( !empty($result) ) {
                foreach( $result as $document ) {
                    $this->document_delete($document['id'], false);
                }
            }
            $query = "DELETE FROM `" . self::TABLE_LINES . "` WHERE `id` = '" . $id . "'";
            return nr_query($query);
        }
        
        
        /* DOCUMENTS */
        
        
        public function documents_list_show($item, $item_id) {
            // Get documents list using the elements' id:
            $query  = "SELECT * FROM `" . self::TABLE_DOCS . "` WHERE `" . $item . "_id` = '" . $item_id . "'";
            $result = simple_queryall($query);
            
            $cells  = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Title'));
            $cells .= wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Filename'));
            $cells .= wf_TableCell(__('Actions'));
            $rows   =  wf_TableRow($cells, 'row2');

            if ( !empty($result) ) {
                foreach ( $result as $document ) {
                    $filename = basename($document['path']);
                    
                    $cells  = wf_TableCell($document['id']);
                    $cells .= wf_TableCell($document['title']);
                    $cells .= wf_TableCell($document['date']);
                    $cells .= wf_TableCell($filename);
                    
                    $actions  = wf_Link(self::URL_DOC_DOWNLOAD . $document['id'], wf_img('skins/icon_download.png', __('Download')));
                    $actions .= wf_Link(self::URL_DOC_DELETE . $document['id'], web_delete_icon());
                    
                    $cells .= wf_TableCell($actions);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            } else {
                $cells = wf_TableCell(__('There is no documents to show'), null, null, 'colspan="5" align="center"');
                $rows .= wf_TableRow($cells, 'row3');
            }

            return wf_TableBody($rows, '100%', '0');
        }
        
        public function document_add_form_show($item, $item_id) {
            $inputs  = wf_HiddenInput(self::FORM_ADD . '[' . $item . '_id]', $item_id);
            $inputs .= wf_TextInput(self::FORM_ADD . '[title]', __('Title'), null, true, '20');
            $inputs .= __('Select document from HDD') . wf_tag('br');
            $inputs .= wf_tag('input', false, '', 'id="fileselector" type="file" name="' . self::FORM_ADD . '[file]"') . wf_tag('br');
            $inputs .= wf_Submit('Upload');
            return bs_UploadFormBody('', 'POST', $inputs, 'glamour');
        }
        
        public function document_add_form_submit($item, $item_id, $data) {
            $return = false;
            if ( !empty($data['title']) ) {
                $file_name = uniqid();
                $file_extention = pathinfo($_FILES[self::FORM_ADD]['name']['file'], PATHINFO_EXTENSION);
                $upload_path = DATA_PATH . 'documents/vols/' . $file_name . '.' . $file_extention;
                if ( move_uploaded_file($_FILES[self::FORM_ADD]['tmp_name']['file'], $upload_path) ) {
                    $return = true;
                    $query  = "
                        INSERT INTO `" . self::TABLE_DOCS . "` (
                            `id`,
                            `title`,
                            `date`,
                            `" . $item . "_id`,
                            `path`
                        ) VALUES (
                            NULL,
                            '" . $data['title'] . "',
                            NOW(),
                            '" . $item_id . "',
                            '" . $upload_path . "'
                        )
                    ";
                    nr_query($query);
                } else show_window(__('Error'), __('You should add any file'));
            } else show_window(__('Error'), __('No display title for document'));
            return $return;
        }
        
        /**
         * Sends document on the server to the browser for downloading
         * 
         * @param type $document_id ID of the downloading document
         */
        public function document_download($id) {
            // Get info about downloading file:
            $query  = "SELECT * FROM `" . self::TABLE_DOCS . "` WHERE `id` = '" . $id . "'";
            $result = simple_query($query);
            
            // Send document to browser:
            $document = file_get_contents($result['path']);
            log_register("DOWNLOAD FILE `" . $result['path'] . "`");
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary"); 
            header("Content-disposition: attachment; filename=\"" . $result['title'] . '.' . pathinfo($result['path'], PATHINFO_EXTENSION) . "\""); 
            die($document);
        }
        
        /**
         * Deletes document from database and filesystem and redirects back to
         * documents list of the node
         * 
         * @param type $document_id ID of the deleting document
         * @return type
         */
        public function document_delete($id, $redirect = true) {
            // Get info about deleting file:
            $query  = "SELECT * FROM `" . self::TABLE_DOCS . "` WHERE `id` = '" . $id . "'";
            $result = simple_query($query);
            
            // Delete from database if deleted from filesystem:
            if ( unlink($result['path']) ) {
                $query  = "DELETE FROM `" . self::TABLE_DOCS . "` WHERE `id` = '" . $id . "'";
                nr_query($query);
                if ( $redirect ) {
                    $item    = empty($result['mark_id']) ? 'line' : 'mark';
                    $item_id = empty($result['mark_id']) ? $result['line_id'] : $result['mark_id'];
                    rcms_redirect('?module=vols&item=' . $item . '&action=documents&id=' . $item_id);
                }
            }
        }
    }
    
    /**
     * Controller:
     */
    $alter = $ubillingConfig->getAlter();
    if ( !empty($alter['VOLS_ENABLED']) ) { 
        $greed = new Avarice();
        $runtime = $greed->runtime('VOLS');
        if ( !empty($runtime) ) {
            $obj = new VOLS();
            if ( wf_CheckGet(array('item', 'action')) ) {
                $item   = vf($_GET['item'], 4);
                $action = vf($_GET['action'], 4);
                switch ( $item ) {
                    case 'map':
                        switch ( $action ) {
                            case 'show':
                                if ( method_exists($obj, $runtime['METHOD']['RNDR']) )
                                    show_window(__('Map of VOLS'), $obj->$runtime['METHOD']['RNDR']());
                                break;
                            case 'edit':
                                if ( method_exists($obj, $runtime['METHOD']['RNDR']) )
                                    show_window(__('Map of VOLS'), $obj->$runtime['METHOD']['RNDR'](true));
                                break;
                            default:
                                // Переадресация на главную стр. модуля при попытке доступа
                                // к несуществующему обработчику $_GET['action']:
                                rcms_redirect($obj::URL_HOME);
                                break;
                        }
                        break;
                     case 'mark_type':
                        switch ( $action ) {
                            case 'list':
                                // Show window:
                                $title  = __('Types of marks') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW, wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_MARK_TYPE_ADD, wf_img('skins/vols_nav/add.png', __('Create')));
                                if ( method_exists($obj, $runtime['METHOD']['MRKTPLST']) )
                                    show_window($title, $obj->$runtime['METHOD']['MRKTPLST']());
                                break;
                            case 'add':
                                // Form submit handle:
                                if ( wf_CheckPost(array($obj::FORM_ADD)) ) {
                                    $data = $_POST[$obj::FORM_ADD];
                                    if ( $obj->mark_type_add_form_submit($data) ) {
                                        rcms_redirect($obj::URL_MARK_TYPES_LIST);
                                    }
                                }
                                // Show window:
                                $title = __('Adding of marks type') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW,        wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_MARK_TYPES_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                if ( method_exists($obj, $runtime['METHOD']['MRKTPD']) )
                                    show_window($title, $obj->$runtime['METHOD']['MRKTPD']());
                                break;
                            case 'edit':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    // Form submit handle:
                                    if ( wf_CheckPost(array($obj::FORM_EDIT)) ) {
                                        $data = $_POST[$obj::FORM_EDIT];
                                        if ( $obj->mark_type_edit_form_submit($id, $data) ) {
                                            rcms_redirect($obj::URL_MARK_TYPES_LIST);
                                        }
                                    }
                                    // Show window:
                                    $title = __('Editing of marks type') . ' ';
                                    $title .= wf_Link($obj::URL_MAP_SHOW,        wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                    $title .= wf_Link($obj::URL_MARK_TYPES_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                    if ( method_exists($obj, $runtime['METHOD']['MRKTPDT']) )
                                        show_window($title, $obj->$runtime['METHOD']['MRKTPDT']($id));
                                } else rcms_redirect($obj::URL_MARK_TYPES_LIST);
                                break;
                            case 'delete':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    if ( method_exists($obj, $runtime['METHOD']['MRKTPDLT']) ) {
                                        if ( $obj->$runtime['METHOD']['MRKTPDLT']($id) ) {
                                            rcms_redirect($obj::URL_MARK_TYPES_LIST);
                                        }
                                    }
                                } else rcms_redirect($obj::URL_MARK_TYPES_LIST);
                                break;
                        }
                        break;
                    case 'mark':
                        switch ( $action ) {
                            case 'list':
                                // Show window:
                                $title  = __('VOLS marks') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW, wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_MARK_ADD, wf_img('skins/vols_nav/add.png', __('Add mark'))) . ' ';
                                if ( method_exists($obj, $runtime['METHOD']['MRKLST']) )
                                    show_window($title, $obj->$runtime['METHOD']['MRKLST']());
                                break;
                            case 'add':
                                // Form submit handle:
                                if ( wf_CheckPost(array($obj::FORM_ADD)) ) {
                                    $data = $_POST[$obj::FORM_ADD];
                                    if ( $obj->mark_add_form_submit($data) ) {
                                        rcms_redirect($obj::URL_MARKS_LIST);
                                    }
                                }
                                // Show window:
                                $title = __('Adding of mark') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_MARKS_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                if ( method_exists($obj, $runtime['METHOD']['MRKD']) )
                                    show_window($title, $obj->$runtime['METHOD']['MRKD']());
                                break;
                            case 'place':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    $obj->place_placemark($id);
                                    if ( method_exists($obj, $runtime['METHOD']['MRKPLC']) )
                                        show_window(__('Map of VOLS'), $obj->$runtime['METHOD']['MRKPLC']());
                                } else rcms_redirect($obj::URL_LINES_LIST);
                                break;
                            case 'edit':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    // Form submit handle:
                                    if ( wf_CheckPost(array($obj::FORM_EDIT)) ) {
                                        $data = $_POST[$obj::FORM_EDIT];
                                        // The ajax check:
                                        if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
                                            die($obj->mark_edit_form_submit($id, $data));
                                        } else {
                                            $obj->mark_edit_form_submit($id, $data);
                                            rcms_redirect($obj::URL_MARKS_LIST);
                                        }
                                    }
                                    // Show window:
                                    $title = __('Editing of mark') . ' ';
                                    $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                    $title .= wf_Link($obj::URL_MARKS_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                    if ( method_exists($obj, $runtime['METHOD']['MRKDT']) )
                                        show_window($title, $obj->$runtime['METHOD']['MRKDT']($id));
                                } else rcms_redirect($obj::URL_MARKS_LIST);
                                break;
                            case 'delete':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    if ( method_exists($obj, $runtime['METHOD']['MRKDLT']) ) {
                                        if ( $obj->$runtime['METHOD']['MRKDLT']($id) ) {
                                            rcms_redirect($obj::URL_MARKS_LIST);
                                        }
                                    }
                                } else rcms_redirect($obj::URL_MARKS_LIST);
                                break;
                            case 'documents':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    // Form submit handle:
                                    if ( wf_CheckPost(array($obj::FORM_ADD)) ) {
                                        $data = $_POST[$obj::FORM_ADD];
                                        $obj->document_add_form_submit($item, $id, $data);
                                    }
                                    // Title + navigation buttons:
                                    $title  = __('Documentation of VOLS') . ' ';
                                    $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                    $title .= wf_Link($obj::URL_MARKS_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                    // Show window:
                                    if ( method_exists($obj, $runtime['METHOD']['MRKDCMNTSLST']) )
                                        show_window($title, $obj->$runtime['METHOD']['MRKDCMNTSLST']($item, $id));
                                    if ( method_exists($obj, $runtime['METHOD']['MRKDCMNTSD']) )   
                                        show_window(__('Adding of document'), $obj->$runtime['METHOD']['MRKDCMNTSD']($item, $id));
                                } else rcms_redirect($obj::URL_MARKS_LIST);
                                break;
                        }
                        break;
                    case 'line':
                        switch ( $action ) {
                            case 'list':
                                // Title + navigation buttons:
                                $title  = __('VOLS lines') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW, wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_LINE_ADD, wf_img('skins/vols_nav/add.png', __('Create')));

                                // Show window:
                                if ( method_exists($obj, $runtime['METHOD']['LNLST']) )
                                    show_window($title, $obj->$runtime['METHOD']['LNLST']());
                                break;
                            case 'add':
                                // Form submit handle:
                                if ( wf_CheckPost(array($obj::FORM_ADD)) ) {
                                    $data = $_POST[$obj::FORM_ADD];
                                    if ( $obj->line_add_form_submit($data) ) {
                                        rcms_redirect($obj::URL_LINES_LIST);
                                    }
                                }
                                // Show window:
                                $title = __('Adding of line') . ' ';
                                $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                $title .= wf_Link($obj::URL_LINES_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                if ( method_exists($obj, $runtime['METHOD']['LND']) )
                                    show_window($title, $obj->$runtime['METHOD']['LND']());
                                break;
                            case 'place':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    $obj->place_polyline($id);
                                    if ( method_exists($obj, $runtime['METHOD']['LNPLC']) )
                                        show_window(__('Map of VOLS'), $obj->$runtime['METHOD']['LNPLC']());
                                } else rcms_redirect($obj::URL_LINES_LIST);
                                break;
                            case 'edit':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    // Form submit handle:
                                    if ( wf_CheckPost(array($obj::FORM_EDIT)) ) {
                                        $data = $_POST[$obj::FORM_EDIT];
                                        // The ajax check:
                                        if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
                                            die($obj->line_edit_form_submit($id, $data));
                                        } else {
                                            $obj->line_edit_form_submit($id, $data);
                                            rcms_redirect($obj::URL_LINES_LIST);
                                        }
                                    }
                                    // Show window:
                                    $title = __('Editing of line') . ' ';
                                    $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                    $title .= wf_Link($obj::URL_LINES_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';
                                    if ( method_exists($obj, $runtime['METHOD']['LNDT']) )
                                        show_window($title, $obj->$runtime['METHOD']['LNDT']($id));
                                } else rcms_redirect($obj::URL_LINES_LIST);
                                break;
                            case 'delete':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    if ( method_exists($obj, $runtime['METHOD']['LNDLT']) ) {
                                        if ( $obj->$runtime['METHOD']['LNDLT']($id) ) {
                                            rcms_redirect($obj::URL_LINES_LIST);
                                        }
                                    }
                                } else rcms_redirect($obj::URL_LINES_LIST);
                                break;
                            case 'documents':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    // Form submit handle:
                                    if ( wf_CheckPost(array($obj::FORM_ADD)) ) {
                                        $data = $_POST[$obj::FORM_ADD];
                                        $obj->document_add_form_submit($item, $id, $data);
                                    }

                                    // Title + navigation buttons:
                                    $title  = __('Documentation of VOLS') . ' ';
                                    $title .= wf_Link($obj::URL_MAP_SHOW,   wf_img('skins/vols_nav/map.png', __('Map of VOLS'))) . ' ';
                                    $title .= wf_Link($obj::URL_LINES_LIST, wf_img('skins/vols_nav/arrow-left.png', __('Back'))) . ' ';

                                    // Show window:
                                    if ( method_exists($obj, $runtime['METHOD']['LNDCMNTSLST']) )
                                        show_window($title, $obj->$runtime['METHOD']['LNDCMNTSLST']($item, $id));
                                    if ( method_exists($obj, $runtime['METHOD']['LNDCMNTSD']) )
                                        show_window(__('Adding of document'), $obj->$runtime['METHOD']['LNDCMNTSD']($item, $id));
                                } else rcms_redirect($obj::URL_LINES_LIST);
                                break;
                            default:
                                // Переадресация на главную стр. модуля при попытке доступа
                                // к несуществующему обработчику $_GET['action']:
                                rcms_redirect($obj::URL_HOME);
                                break;
                        }
                        break;
                    case 'document':
                        switch ( $action ) {
                            case 'download':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    if ( method_exists($obj, $runtime['METHOD']['DCMNTSDWNLD']) ) 
                                        $obj->$runtime['METHOD']['DCMNTSDWNLD']($id);
                                } else rcms_redirect($obj::URL_HOME);
                                break;
                            case 'delete':
                                if ( wf_CheckGet(array('id')) ) {
                                    $id = vf($_GET['id'], 3);
                                    if ( method_exists($obj, $runtime['METHOD']['DCMNTSDLT']) ) 
                                        $obj->$runtime['METHOD']['DCMNTSDLT']($id);
                                } else rcms_redirect($obj::URL_HOME);
                                break;
                            default:
                                // Переадресация на главную стр. модуля при попытке доступа
                                // к несуществующему обработчику $_GET['action']:
                                rcms_redirect($obj::URL_HOME);
                                break;
                        }
                        break;
                    default:
                        // Переадресация на главную стр. модуля при попытке доступа
                        // к несуществующему обработчику $_GET['item']:
                        rcms_redirect($obj::URL_HOME);
                        break;
                }
            } else {
                // Переадресация на главную стр. модуля если не получены все
                // необходисые параметры в $_GET[];
                rcms_redirect($obj::URL_HOME);
            }
        } else {
            show_window(__('Error'), __('No license key available'));
        }
    }
    
} else show_error(__('Access denied'));