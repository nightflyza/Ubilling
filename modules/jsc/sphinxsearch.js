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

//keyboard navigation
document.addEventListener("DOMContentLoaded", function () {
    let currentIndex = -1;
    let searchContainer = document.getElementById("ssearchcontainer");

    document.addEventListener("keydown", function (e) {
        let items = searchContainer.querySelectorAll(".ui-menu-item");

        if (searchContainer.style.display === "block" && items.length > 0) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                if (currentIndex < items.length - 1) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                if (currentIndex > 0) {
                    currentIndex--;
                } else {
                    currentIndex = items.length - 1;
                }
            } else if (e.key === "Enter" && currentIndex >= 0) {
                e.preventDefault();
                let link = items[currentIndex].querySelector("a");
                if (link) {
                    window.location.href = link.href;
                }
            }

            items.forEach(item => item.classList.remove("active"));
            if (currentIndex >= 0) {
                items[currentIndex].classList.add("active");
            }
        }
    });
});
