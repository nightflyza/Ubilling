/**
 * CustMaps polyline editor (Leaflet.Editable) — static logic; map + options from PHP.
 *
 * @param {L.Map} map
 * @param {Object} config
 * @param {string} config.panelHtml
 * @param {string} config.drawBtnId
 * @param {string} config.finishBtnId
 * @param {string} config.cancelBtnId
 * @param {string} config.undoBtnId
 * @param {string} [config.lengthReadoutId] — DOM id of span showing length in metres (read-only)
 * @param {number} [config.initialEditLineId]
 * @param {string} [config.defaultColor]
 * @param {number} [config.defaultFibersAmount]
 * @param {number} [config.defaultWidth]
 */
window.ubCustmapsLineEditorInit = function(map, config) {
    if (typeof L.Editable !== "function") {
        if (window.console && typeof console.warn === "function") {
            console.warn("CustMaps: Leaflet.Editable plugin is unavailable");
        }
        return;
    }

    if (!config) {
        config = {};
    }

    var ubLinePanelHtml = config.panelHtml || "";
    var ubDrawBtnId = config.drawBtnId || "";
    var ubFinishBtnId = config.finishBtnId || "";
    var ubCancelBtnId = config.cancelBtnId || "";
    var ubUndoBtnId = config.undoBtnId || "";
    var ubLengthReadoutId = config.lengthReadoutId ? String(config.lengthReadoutId) : "";
    var ubInitialEditLineId = config.initialEditLineId ? parseInt(String(config.initialEditLineId), 10) : 0;
    if (isNaN(ubInitialEditLineId)) {
        ubInitialEditLineId = 0;
    }
    var ubDefaultColor = config.defaultColor ? String(config.defaultColor) : "#f57601";
    var ubDefaultFibersAmountStr = "0";
    if (typeof config.defaultFibersAmount !== "undefined" && config.defaultFibersAmount !== null) {
        ubDefaultFibersAmountStr = String(config.defaultFibersAmount);
    }
    var ubDefaultWidth = 2;
    if (typeof config.defaultWidth !== "undefined" && config.defaultWidth !== null && String(config.defaultWidth) !== "") {
        var ubParsedDefaultWidth = parseInt(String(config.defaultWidth), 10);
        if (!isNaN(ubParsedDefaultWidth)) {
            ubDefaultWidth = ubParsedDefaultWidth;
        }
    }

    var ubActiveLine = null;
    var ubDrawingLine = null;
    var ubIsDrawingMode = false;
    var ubEditorPanelControl = null;
    var ubLineSnapshots = {};
    var ubLineStyles = {};
    var ubLineMetas = {};
    var ubLineById = {};

    if (!map.options.editable) {
        map.options.editable = true;
    }
    if (!map.editTools) {
        map.editTools = new L.Editable(map);
    }

    function ubGenerateRandomLineColor() {
        var letters = "0123456789ABCDEF";
        var color = "#";
        var i = 0;
        for (i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    function ubLineKey(line) {
        var result = "";
        if (line) {
            if (!line._ubLineEditorKey) {
                line._ubLineEditorKey = "line_" + (new Date().getTime()) + "_" + Math.floor(Math.random() * 1000000);
            }
            result = line._ubLineEditorKey;
        }
        return result;
    }

    function ubLineToArray(line) {
        var result = [];
        if (line && typeof line.getLatLngs === "function") {
            var latlngs = line.getLatLngs();
            var i = 0;
            for (i = 0; i < latlngs.length; i++) {
                if (latlngs[i] && typeof latlngs[i].lat !== "undefined" && typeof latlngs[i].lng !== "undefined") {
                    result.push([latlngs[i].lat, latlngs[i].lng]);
                }
            }
        }
        return result;
    }

    function ubLineDistance(points) {
        var length = 0;
        var i = 0;
        for (i = 0; i < points.length - 1; i++) {
            var p1 = L.latLng(points[i][0], points[i][1]);
            var p2 = L.latLng(points[i + 1][0], points[i + 1][1]);
            length += p1.distanceTo(p2);
        }
        return length;
    }

    function ubSyncFormFields() {
        var lineIdField = document.querySelector('input[name="newline_lineid"]');
        var geoField = document.querySelector('input[name="newline_geo"]');
        var nameField = document.querySelector('input[name="newline_name"]');
        var fibersField = document.querySelector('select[name="newline_fibers_amount"]');
        var lengthField = document.querySelector('input[name="newline_length_m"]');
        var colorField = document.querySelector('input[name="newline_style_color"]');
        var widthField = document.querySelector('select[name="newline_style_width"]');
        var descriptionField = document.querySelector('input[name="newline_description"]');
        var points = [];
        var activeLineId = "";
        var lineMeta = null;
        if (ubActiveLine) {
            points = ubLineToArray(ubActiveLine);
            if (typeof ubActiveLine._ubLineId !== "undefined" && ubActiveLine._ubLineId) {
                activeLineId = String(ubActiveLine._ubLineId);
            }
            if (ubActiveLine._ubLineMeta) {
                lineMeta = ubActiveLine._ubLineMeta;
            }
        }
        if (lineIdField) {
            lineIdField.value = activeLineId;
        }
        if (geoField) {
            geoField.value = JSON.stringify(points);
        }
        var lengthMStr = ubLineDistance(points).toFixed(2);
        if (lengthField) {
            lengthField.value = lengthMStr;
        }
        if (ubLengthReadoutId) {
            var lengthReadout = document.getElementById(ubLengthReadoutId);
            if (lengthReadout) {
                lengthReadout.textContent = lengthMStr;
            }
        }
        if (lineMeta) {
            if (nameField && typeof lineMeta.name !== "undefined") {
                nameField.value = String(lineMeta.name);
            }
            if (fibersField && typeof lineMeta.fibers_amount !== "undefined") {
                fibersField.value = String(lineMeta.fibers_amount);
            }
            if (colorField && typeof lineMeta.style_color !== "undefined") {
                colorField.value = String(lineMeta.style_color);
            }
            if (widthField && typeof lineMeta.style_width !== "undefined") {
                widthField.value = String(lineMeta.style_width);
            }
            if (descriptionField && typeof lineMeta.description !== "undefined") {
                descriptionField.value = String(lineMeta.description);
            }
        } else {
            if (!activeLineId) {
                var activeLineColor = "";
                if (ubActiveLine && ubActiveLine.options && ubActiveLine.options.color) {
                    activeLineColor = String(ubActiveLine.options.color);
                }
                if (nameField) {
                    nameField.value = "";
                }
                if (fibersField) {
                    fibersField.value = ubDefaultFibersAmountStr;
                }
                if (colorField) {
                    if (activeLineColor) {
                        colorField.value = activeLineColor;
                    } else {
                        colorField.value = ubDefaultColor;
                    }
                }
                if (widthField) {
                    widthField.value = String(ubDefaultWidth);
                }
                if (descriptionField) {
                    descriptionField.value = "";
                }
            }
        }
    }

    function ubRememberLineState(line) {
        var key = ubLineKey(line);
        if (!key) {
            return;
        }
        ubLineSnapshots[key] = ubLineToArray(line);
        if (line && line.options) {
            ubLineStyles[key] = {
                color: line.options.color ? line.options.color : ubDefaultColor,
                weight: line.options.weight ? line.options.weight : ubDefaultWidth
            };
        } else {
            ubLineStyles[key] = {
                color: ubDefaultColor,
                weight: ubDefaultWidth
            };
        }
        if (line && line._ubLineMeta) {
            ubLineMetas[key] = line._ubLineMeta;
        } else {
            ubLineMetas[key] = null;
        }
    }

    function ubApplyLineStyle(line, color, width) {
        if (line && typeof line.setStyle === "function") {
            line.setStyle({
                color: color,
                weight: width,
                opacity: 1
            });
        }
    }

    /**
     * Reads stroke color and width from the line editor form (same fields as save uses).
     */
    function ubReadFormLineStyle() {
        var colorField = document.querySelector('input[name="newline_style_color"]');
        var widthField = document.querySelector('select[name="newline_style_width"]');
        var color = ubDefaultColor;
        var width = ubDefaultWidth;
        if (colorField && colorField.value) {
            color = String(colorField.value);
        }
        if (widthField && widthField.value) {
            width = parseInt(String(widthField.value), 10);
            if (isNaN(width) || width < 1) {
                width = ubDefaultWidth;
            }
        }
        return { color: color, weight: width };
    }

    /**
     * Leaflet.Editable draws the segment to the cursor with forward/backward "line guides"
     * (default weight 1, dashed). Match them to the chosen style so preview matches the final line.
     */
    function ubStyleEditableLineGuides(color, weight) {
        var tools = map.editTools;
        if (!tools) {
            return;
        }
        var w = weight;
        if (isNaN(w) || w < 1) {
            w = 1;
        }
        var guideStyle = {
            color: color,
            weight: w,
            opacity: 0.92,
            dashArray: ""
        };
        if (tools.forwardLineGuide && typeof tools.forwardLineGuide.setStyle === "function") {
            tools.forwardLineGuide.setStyle(guideStyle);
        }
        if (tools.backwardLineGuide && typeof tools.backwardLineGuide.setStyle === "function") {
            tools.backwardLineGuide.setStyle(guideStyle);
        }
    }

    /**
     * Applies current form color/width to the polyline being drawn and to Editable line guides.
     */
    function ubSyncDrawingVisualsFromForm() {
        var st = ubReadFormLineStyle();
        if (ubDrawingLine && typeof ubDrawingLine.setStyle === "function") {
            ubDrawingLine.setStyle({
                color: st.color,
                weight: st.weight,
                opacity: 1
            });
        }
        ubStyleEditableLineGuides(st.color, st.weight);
    }

    /**
     * Dims all map polylines except the one in focus (active edit or draw target).
     */
    function ubRefreshLineFocusVisuals() {
        var focusLine = null;
        if (ubDrawingLine && map.hasLayer(ubDrawingLine)) {
            focusLine = ubDrawingLine;
        } else {
            if (ubActiveLine && map.hasLayer(ubActiveLine)) {
                focusLine = ubActiveLine;
            }
        }
        map.eachLayer(function(layer) {
            if (!layer || !(layer instanceof L.Polyline) || layer instanceof L.Polygon) {
                return;
            }
            if (typeof layer.setStyle !== "function") {
                return;
            }
            if (focusLine && layer === focusLine) {
                layer.setStyle({ opacity: 1 });
                if (typeof layer.bringToFront === "function") {
                    layer.bringToFront();
                }
            } else {
                if (focusLine) {
                    layer.setStyle({ opacity: 0.42 });
                } else {
                    layer.setStyle({ opacity: 0.8 });
                }
            }
        });
    }

    function ubWireFormStyleListeners(panelRoot) {
        var root = panelRoot || document;
        var colorField = root.querySelector('input[name="newline_style_color"]');
        var widthField = root.querySelector('select[name="newline_style_width"]');
        var onFormStyleChange = function() {
            if (ubDrawingLine && map.hasLayer(ubDrawingLine)) {
                ubSyncDrawingVisualsFromForm();
            } else {
                if (ubActiveLine && map.hasLayer(ubActiveLine)) {
                    var st = ubReadFormLineStyle();
                    ubApplyLineStyle(ubActiveLine, st.color, st.weight);
                    ubRefreshLineFocusVisuals();
                }
            }
        };
        if (colorField) {
            colorField.addEventListener("input", onFormStyleChange);
            colorField.addEventListener("change", onFormStyleChange);
        }
        if (widthField) {
            widthField.addEventListener("change", onFormStyleChange);
        }
    }

    function ubUndoActiveLineChanges() {
        if (!ubActiveLine) {
            return;
        }
        var key = ubLineKey(ubActiveLine);
        if (!key) {
            return;
        }
        if (typeof ubLineSnapshots[key] !== "undefined") {
            ubActiveLine.setLatLngs(ubLineSnapshots[key]);
        }
        if (typeof ubLineStyles[key] !== "undefined") {
            ubApplyLineStyle(ubActiveLine, ubLineStyles[key].color, ubLineStyles[key].weight);
        }
        if (typeof ubLineMetas[key] !== "undefined") {
            ubActiveLine._ubLineMeta = ubLineMetas[key];
        }
        if (ubActiveLine.redraw && typeof ubActiveLine.redraw === "function") {
            ubActiveLine.redraw();
        }
        if (typeof ubActiveLine.disableEdit === "function") {
            ubActiveLine.disableEdit();
        }
        ubSyncFormFields();
        ubRefreshLineFocusVisuals();
    }

    function ubIsTypingContext() {
        var activeEl = document.activeElement;
        var result = false;
        if (activeEl) {
            var tagName = activeEl.tagName ? activeEl.tagName.toLowerCase() : "";
            if (tagName === "input" || tagName === "textarea" || activeEl.isContentEditable) {
                result = true;
            }
        }
        return result;
    }

    function ubMatchesHotkey(e, codeValue, keyValues) {
        var matched = false;
        if (e) {
            if (e.code && e.code === codeValue) {
                matched = true;
            } else {
                if (e.key && keyValues.indexOf(e.key) !== -1) {
                    matched = true;
                }
            }
        }
        return matched;
    }

    function ubHotkeysHandler(e) {
        if (ubIsTypingContext()) {
            return;
        }
        var noMod = e && !e.ctrlKey && !e.altKey && !e.metaKey;
        var ctrlOnly = e && e.ctrlKey && !e.altKey && !e.metaKey;
        if (noMod && ubMatchesHotkey(e, "KeyN", ["n", "N"])) {
            if (typeof e.preventDefault === "function") {
                e.preventDefault();
            }
            ubStartDrawing();
        } else {
            if (ctrlOnly && ubMatchesHotkey(e, "Enter", ["Enter"])) {
                if (typeof e.preventDefault === "function") {
                    e.preventDefault();
                }
                ubFinishDrawing();
            } else {
                if (ctrlOnly && ubMatchesHotkey(e, "KeyZ", ["z", "Z"])) {
                    if (typeof e.preventDefault === "function") {
                        e.preventDefault();
                    }
                    ubUndoActiveLineChanges();
                } else {
                    if (noMod && ubMatchesHotkey(e, "Escape", ["Escape", "Esc"])) {
                        if (typeof e.preventDefault === "function") {
                            e.preventDefault();
                        }
                        ubCancelDrawing();
                    }
                }
            }
        }
    }

    function ubActivateLine(line) {
        if (!line) {
            return;
        }
        if (ubActiveLine && ubActiveLine !== line) {
            if (typeof ubActiveLine.disableEdit === "function") {
                ubActiveLine.disableEdit();
            }
        }
        ubActiveLine = line;
        if (typeof ubActiveLine.enableEdit === "function") {
            ubActiveLine.enableEdit();
        }
        ubRememberLineState(ubActiveLine);
        ubSyncFormFields();
        ubRefreshLineFocusVisuals();
    }

    function ubFinishDrawing() {
        if (ubDrawingLine && typeof ubDrawingLine.editor !== "undefined" && ubDrawingLine.editor) {
            if (typeof ubDrawingLine.editor.endDrawing === "function") {
                ubDrawingLine.editor.endDrawing();
            }
            ubActiveLine = ubDrawingLine;
            ubSyncFormFields();
        }
    }

    function ubCancelDrawing() {
        if (map.editTools && typeof map.editTools.stopDrawing === "function") {
            map.editTools.stopDrawing();
        }
        if (ubDrawingLine && ubDrawingLine.editor && typeof ubDrawingLine.editor.cancelDrawing === "function") {
            ubDrawingLine.editor.cancelDrawing();
        } else {
            if (ubDrawingLine && map.hasLayer(ubDrawingLine)) {
                map.removeLayer(ubDrawingLine);
            }
        }
        if (ubDrawingLine && typeof ubDrawingLine.disableEdit === "function") {
            ubDrawingLine.disableEdit();
        }
        if (ubActiveLine && typeof ubActiveLine.disableEdit === "function") {
            ubActiveLine.disableEdit();
        }
        ubDrawingLine = null;
        ubActiveLine = null;
        ubIsDrawingMode = false;
        ubSyncFormFields();
        ubRefreshLineFocusVisuals();
    }

    function ubStartDrawing() {
        ubCancelDrawing();
        var colorField = document.querySelector('input[name="newline_style_color"]');
        if (colorField) {
            colorField.value = ubGenerateRandomLineColor();
        }
        var st = ubReadFormLineStyle();
        ubIsDrawingMode = true;
        ubDrawingLine = map.editTools.startPolyline(undefined, {
            color: st.color,
            weight: st.weight,
            opacity: 1
        });
        ubSyncDrawingVisualsFromForm();
        ubRefreshLineFocusVisuals();
    }

    function ubMakeLineEditable(line) {
        if (!line) {
            return;
        }
        line.on("click", function(e) {
            ubActivateLine(line);
            if (e && e.originalEvent && typeof L.DomEvent.stopPropagation === "function") {
                L.DomEvent.stopPropagation(e);
            }
        });
        line.on("editable:dragend", function() {
            if (ubActiveLine === line) {
                ubSyncFormFields();
            }
        });
        line.on("editable:vertex:dragend", function() {
            if (ubActiveLine === line) {
                ubSyncFormFields();
            }
        });
        line.on("editable:vertex:deleted", function() {
            if (ubActiveLine === line) {
                ubSyncFormFields();
            }
        });
        line.on("editable:vertex:new", function() {
            if (ubActiveLine === line) {
                ubSyncFormFields();
            }
        });
        if (typeof line._ubLineId !== "undefined" && line._ubLineId) {
            ubLineById[String(line._ubLineId)] = line;
        }
    }

    function ubAttachPanel() {
        if (ubEditorPanelControl) {
            return;
        }
        ubEditorPanelControl = L.control({position: "topright"});
        ubEditorPanelControl.onAdd = function() {
            var container = L.DomUtil.create("div", "leaflet-bar ubLineEditorPanel");
            container.style.background = "#fff";
            container.style.padding = "10px";
            container.style.maxWidth = "360px";
            container.style.maxHeight = "70vh";
            container.style.overflowY = "auto";
            container.style.boxSizing = "border-box";
            container.innerHTML = ubLinePanelHtml;
            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);
            ubWireFormStyleListeners(container);
            return container;
        };
        ubEditorPanelControl.addTo(map);

        var drawBtn = document.getElementById(ubDrawBtnId);
        var finishBtn = document.getElementById(ubFinishBtnId);
        var cancelBtn = document.getElementById(ubCancelBtnId);
        var undoBtn = document.getElementById(ubUndoBtnId);
        if (drawBtn) {
            drawBtn.onclick = function(e) {
                if (e && typeof e.preventDefault === "function") {
                    e.preventDefault();
                }
                ubStartDrawing();
                return false;
            };
        }
        if (finishBtn) {
            finishBtn.onclick = function(e) {
                if (e && typeof e.preventDefault === "function") {
                    e.preventDefault();
                }
                ubFinishDrawing();
                return false;
            };
        }
        if (cancelBtn) {
            cancelBtn.onclick = function(e) {
                if (e && typeof e.preventDefault === "function") {
                    e.preventDefault();
                }
                ubCancelDrawing();
                return false;
            };
        }
        if (undoBtn) {
            undoBtn.onclick = function(e) {
                if (e && typeof e.preventDefault === "function") {
                    e.preventDefault();
                }
                ubUndoActiveLineChanges();
                return false;
            };
        }
    }

    map.eachLayer(function(layer) {
        if (layer && layer instanceof L.Polyline && !(layer instanceof L.Polygon)) {
            ubMakeLineEditable(layer);
        }
    });

    map.on("editable:drawing:start", function(e) {
        if (e && e.layer && e.layer instanceof L.Polyline) {
            ubDrawingLine = e.layer;
            ubMakeLineEditable(ubDrawingLine);
            ubActiveLine = ubDrawingLine;
            ubActiveLine._ubLineId = 0;
            ubActiveLine._ubLineMeta = null;
            ubRememberLineState(ubActiveLine);
            ubSyncFormFields();
            ubSyncDrawingVisualsFromForm();
            ubRefreshLineFocusVisuals();
        }
    });

    map.on("editable:drawing:end", function() {
        ubIsDrawingMode = false;
    });

    map.on("editable:drawing:commit", function(e) {
        if (e && e.layer && e.layer instanceof L.Polyline) {
            ubDrawingLine = null;
            ubActivateLine(e.layer);
            ubRememberLineState(e.layer);
        }
    });

    map.on("editable:drawing:clicked", function() {
        if (ubDrawingLine) {
            ubActiveLine = ubDrawingLine;
            ubSyncFormFields();
            ubSyncDrawingVisualsFromForm();
            ubRefreshLineFocusVisuals();
        }
    });

    ubAttachPanel();
    document.addEventListener("keydown", ubHotkeysHandler);
    if (ubInitialEditLineId) {
        setTimeout(function() {
            var initialLine = ubLineById[String(ubInitialEditLineId)];
            if (initialLine) {
                ubActivateLine(initialLine);
            }
        }, 0);
    }
    setTimeout(function() {
        ubSyncFormFields();
        ubRefreshLineFocusVisuals();
    }, 0);
};
