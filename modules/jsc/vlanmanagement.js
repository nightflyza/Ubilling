function vlanAcquire(element) {
    id = element.id;
    let modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajax&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    };
}

function occupiedBySwitch(element) {
    id = element.id;
    let modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajaxswitch&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    };
}

function occupiedByCustomer(element) {
    id = element.id;
    let modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let switchid = data[3];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajaxcustomer&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan + "&switchid=" + switchid, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    };
}

function realmEdit(element) {
    let modalContent = document.getElementById("content-cvmodal");

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "?module=vlanmanagement&realms=true&action=ajaxedit", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send("realm_encode=" + element.id);

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    }
}

function svlanEdit(element) {
    let modalContent = document.getElementById("content-cvmodal");

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "?module=vlanmanagement&svlan=true&action=ajaxedit", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send("svlan_encode=" + element.id);

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    }
}

function qinqEdit(element) {
    let modalContent = document.getElementById("content-cvmodal");

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "?module=universalqinq&action=ajaxedit", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send("universal_encode=" + element.id);

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    }
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



