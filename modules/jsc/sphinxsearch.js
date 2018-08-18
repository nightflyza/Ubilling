function appendToList(value, login) {
    var billingLink = '?module=userprofile&username=';
    var userLink = billingLink.concat(login);

    var node = document.createElement("LI");
    var link = document.createElement("a");
    var container = document.createElement("div");
    var textnode = document.createTextNode(value);

    link.appendChild(textnode);
    link.title = 'title';
    link.href = userLink;
    node.classList.add('ui-menu-item');
    container.appendChild(link);
    container.classList.add('ui-menu');
    container.classList.add('ui-menu-item-wrapper');
    node.appendChild(container);
    document.getElementById("search").appendChild(node);
}
function querySearch(value) {
    var searchList = document.getElementById('search');
    if (value !== "") {
        var xhr = new XMLHttpRequest();
        var searchString = 'search=';
        var searchQuery = searchString.concat(value);
        xhr.open('POST', '?module=usersearch&sphinxsearch=true', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            searchList.innerHTML = "";
            console.log(value);
            var JSONresponse = JSON.parse(this.responseText);
            JSONresponse.forEach(function (object) {
                if (object.value !== undefined) {
                    var description = object.title.concat(": ");
                    appendToList(description.concat(object.value), object.login);
                }
            })
        };
        xhr.send(searchQuery);
    } else {
        searchList.innerHTML = "";
    }
}
