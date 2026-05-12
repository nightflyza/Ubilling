/**
 * CustMaps — Leaflet control to show/hide marker layer (ubMarkerToggleShell) + localStorage.
 * Expects globals from MapCore inline script: map, ubMarkerToggleShell.
 *
 * @param {L.Map} map
 * @param {Object} config
 * @param {string} config.storageKey — localStorage key (1 = visible, 0 = hidden)
 * @param {string} config.iconMarkersOn — markers visible (e.g. skins/icon_fullmap16.png)
 * @param {string} config.iconMarkersOff — markers hidden (e.g. skins/icon_briefmap16.png)
 * @param {string} config.titleShowMarkers — tooltip when markers are hidden (click to show)
 * @param {string} config.titleHideMarkers — tooltip when markers are visible (click to hide)
 * @param {string} [config.controlPosition] — Leaflet control position (default: topleft)
 */
window.ubCustmapsMarkersToggleInit = function(map, config) {
    if (!map) {
        return;
    }
    if (typeof ubMarkerToggleShell === "undefined" || !ubMarkerToggleShell) {
        return;
    }
    if (!config) {
        config = {};
    }
    var storageKey = config.storageKey ? String(config.storageKey) : "";
    if (!storageKey) {
        return;
    }
    var iconSrcMarkersOn = config.iconMarkersOn ? String(config.iconMarkersOn) : "skins/icon_fullmap16.png";
    var iconSrcMarkersOff = config.iconMarkersOff ? String(config.iconMarkersOff) : "skins/icon_briefmap16.png";
    var titleShowMarkers = config.titleShowMarkers ? String(config.titleShowMarkers) : "";
    var titleHideMarkers = config.titleHideMarkers ? String(config.titleHideMarkers) : "";
    var controlPosition = config.controlPosition ? String(config.controlPosition) : "topleft";

    var markersVisible = true;
    try {
        if (window.localStorage) {
            var v = localStorage.getItem(storageKey);
            if (v === "0") {
                markersVisible = false;
            }
        }
    } catch (err) {
    }
    if (!markersVisible) {
        map.removeLayer(ubMarkerToggleShell);
    }

    var CustmapMarkersLayerControl = L.Control.extend({
        options: {position: controlPosition},
        onAdd: function() {
            var container = L.DomUtil.create("div", "leaflet-bar leaflet-control custmap-markers-layer-control");
            L.DomEvent.disableClickPropagation(container);
            var btn = L.DomUtil.create("a", "custmap-markers-toggle-btn", container);
            btn.href = "#";
            btn.setAttribute("role", "button");
            btn.style.display = "flex";
            btn.style.alignItems = "center";
            btn.style.justifyContent = "center";
            btn.style.width = "30px";
            btn.style.height = "30px";
            btn.style.padding = "0";
            btn.style.boxSizing = "border-box";
            btn.style.backgroundColor = "#fff";
            btn.style.cursor = "pointer";
            var img = L.DomUtil.create("img", "", btn);
            img.width = 16;
            img.height = 16;
            img.alt = "";
            img.style.display = "block";
            function ubCustmapMarkersUpdateUi(visible) {
                img.src = visible ? iconSrcMarkersOn : iconSrcMarkersOff;
                btn.title = visible ? titleHideMarkers : titleShowMarkers;
            }
            ubCustmapMarkersUpdateUi(markersVisible);
            L.DomEvent.on(btn, "click", function(e) {
                L.DomEvent.preventDefault(e);
                markersVisible = !markersVisible;
                if (markersVisible) {
                    if (!map.hasLayer(ubMarkerToggleShell)) {
                        map.addLayer(ubMarkerToggleShell);
                    }
                } else {
                    if (map.hasLayer(ubMarkerToggleShell)) {
                        map.removeLayer(ubMarkerToggleShell);
                    }
                }
                try {
                    if (window.localStorage) {
                        localStorage.setItem(storageKey, markersVisible ? "1" : "0");
                    }
                } catch (e2) {
                }
                ubCustmapMarkersUpdateUi(markersVisible);
            });
            L.DomEvent.on(btn, "mousedown dblclick", L.DomEvent.stopPropagation);
            return container;
        }
    });
    map.addControl(new CustmapMarkersLayerControl());
};
