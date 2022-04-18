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
    let switchid = data[3];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajaxswitch&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan + "&switchid=" + switchid, true);
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

function occupiedByOltZte(element) {
    id = element.id;
    let modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let switchid = data[3];
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajaxoltzte&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan + "&switchid=" + switchid, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        modalContent.innerHTML = response;
        modalOpen();
    };
}

function occupiedByOltNonZte(element) {
    id = element.id;
    let modalContent = document.getElementById("content-cvmodal");

    let splited = id.split("_");
    let data = splited[1].split("/");
    let realm = data[0];
    let svlan = data[1];
    let cvlan = data[2];
    let switchid = data[3];
    console.log(switchid);
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=vlanmanagement&action=ajaxoltnonzte&realm_id=" + realm + "&svlan_id=" + svlan + "&cvlan_num=" + cvlan + "&switchid=" + switchid, true);
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

function loadQinqOptions(interface, swid) {
    let container = document.getElementById('qinqcontainer');
    let xhr = new XMLHttpRequest();
    let url2 = "?module=zteunreg&action=ajaxlogin&swid=" + swid + "&interface=" + interface;
    xhr.open("GET", url2, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        let decoded = JSON.parse(response);
        container.innerHTML = decoded.result;
    };
}

function getQinqByLogin(login, interface, swid) {
    let container = document.getElementById('qinqcontainer');

    let xhr = new XMLHttpRequest();
    xhr.open("GET", "?module=zteunreg&action=ajaxlogin&login=" + login + "&interface=" + interface + "&swid=" + swid, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send();

    xhr.onload = function () {
        let response = xhr.response;
        console.log(response);
        let decoded = JSON.parse(response);
        container.innerHTML = decoded.main;
        if (decoded.svlan != 'none') {
            let table = document.getElementById("qinqoptions");
            let newRow1 = table.insertRow(-1);
            let newCell1 = newRow1.insertCell(-1);
            newCell1.innerHTML = decoded.cell1;
            let newCell2 = newRow1.insertCell(-1);
            newCell2.innerHTML = decoded.cell2;
            let newRow2 = table.insertRow(-1);
            let newCell3 = newRow2.insertCell(-1);
            newCell3.innerHTML = decoded.cell3;
            let newCell4 = newRow2.insertCell(-1);
            newCell4.innerHTML = decoded.cell4;
        }
    };

}

