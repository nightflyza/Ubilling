function vlanAcquire(element) {
    id = element.id;
    var modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajax&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan, true);

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    };


}

function modalOpen() {
    $("#dialog-modal_cvmodal").dialog({
        autoOpen: true,
        width: 'auto',
        height: 'auto',
        modal: true,
        show: "drop",
        hide: "fold"
    });
    $("#opener_cvmodal").click(function () {
        $("#dialog-modal_cvmodal").dialog("open");
        return false;
    });
}
;

