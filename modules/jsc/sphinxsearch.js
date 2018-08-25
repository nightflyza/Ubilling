function appendToList(value, login, title) {
    var billingLink = '?module=userprofile&username=';
    var userLink = billingLink.concat(login);

    var node = document.createElement("li");
    var link = document.createElement("a");
    var container = document.createElement("div");
    var textnode = document.createTextNode(value);

    link.appendChild(textnode);
    link.title = title;
    link.href = userLink;
    node.classList.add('ui-menu-item');
    container.appendChild(link);
    container.classList.add('ui-menu');
    container.classList.add('ui-menu-item-wrapper');
    node.appendChild(container);
    document.getElementById("ssearchcontainer").appendChild(node);
    showSearchContainer();
}

function querySearch(value) {
    var searchList = document.getElementById('ssearchcontainer');
    if (value !== "") {
        animationStart();
        var xhr = new XMLHttpRequest();
        var searchString = 'search=';
        var searchQuery = searchString.concat(value);
        xhr.open('POST', '?module=usersearch&sphinxsearch=true', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            searchList.innerHTML = "";
            var JSONresponse = JSON.parse(this.responseText);
            JSONresponse.forEach(function (object) {
                if (object.value !== undefined) {
                    var description = object.title.concat(": ");
                    var fulldesc = description.concat(object.value);
                    appendToList(object.value, object.login, fulldesc);
                }
            })
            animationStop();
        };
        xhr.send(searchQuery);

    } else {
        searchList.innerHTML = "";
        hideSearchContainer();
    }
}

function hideSearchContainer() {
    document.getElementById("ssearchcontainer").style.display = "none";
}

function showSearchContainer() {
    document.getElementById("ssearchcontainer").style.display = "block";
}

function animationStart() {
    document.getElementById("sphinxsearchinput").className = "sphinxsearch-input-loading";
}

function animationStop() {
    document.getElementById("sphinxsearchinput").className = "sphinxsearch-input";
}

//some reaction on ESC key
$(document).keyup(function (e) {
    if (e.keyCode == 27) {
        hideSearchContainer();
    }
});
