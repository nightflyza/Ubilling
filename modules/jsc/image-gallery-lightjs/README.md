light
=====

A super lightweight lightbox plugin for images in jQuery.


Usage
-----

    $('a[rel=light]').light();

The available options are:

    $('a[rel=light]').light({
        unbind:true, //whether to unbind other click events from elements
        prevText:'Previous, //the text on the "Previous" button
        nextText:'Next', //the text on the "Next" button
        loadText:'Loading...', //the text to display when loading
        keyboard:true //whether to use the keyboard inputs for next, previous and close
    });

Examples
--------

You can find all the examples live [here](https://edmundgentle.github.io/light/examples/)
