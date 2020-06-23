# fakeloader
A jQuery plugin to make a multi-page website appear to load like a single-page application

Copyright (c) 2015 Jim Keller, [Eastern Standard](http://easternstandard.com)

## Overview

Provides a means to show a loading spinner and overlay so that a multiple page website or application appears to load like a single-page application. It's purely an illusion; there's no ajax magic happening, but it's also very low risk and extremely easy to add to your site.

Supports Google Chrome, Firefox, IE9+

Requires jQuery

Read about a real-world implementation on the [Eastern Standard Blog](http://easternstandard.com/blog/2015/06/elegant-page-transitions-multi-page-website)

## Get it

- [Production JS (minified)](https://raw.github.com/jimkeller/fakeloader/master/dist/fakeloader.min.js)
- [Development JS (unminified)](https://raw.github.com/jimkeller/fakeloader/master/src/fakeloader.js)
- [CSS] (https://raw.github.com/jimkeller/fakeloader/master/dist/fakeloader.css)

## How it Works
The loading overlay is added to the default markup of your page (see below), and is shown by default. On document.ready, or when [waitForImages](https://github.com/alexanderdickson/waitForImages) fires, the overlay is removed. The overlay is re-added on window.onunload, so that the page appears to transition seamlessly from one to the next. 

## Usage

First, include the fakeloader javascript and CSS files (available above) to your page. 

Then, add the following markup to your page. It should appear immediately after the ```<body>``` tag. 

```html
<div id="fakeloader-overlay" class="visible incoming"><div class="loader-wrapper-outer"><div class="loader-wrapper-inner"><div class="loader"></div></div></div></div>
```

Finally, add the following so that the loader fires on DOM ready:

```javascript
$(document).ready(
    function() {
        window.FakeLoader.init( );
    }
);
```

You should now see the loading overlay when transitioning between pages. You can override the CSS as necessary to change colors, animation, etc (note: a circular spinner works best, since it's harder to see the "jump" or screen flash when the new page loads.)

### Options

You can pass a settings object as the first argument to FakeLoader.init(). The settings object supports the following options:

#### auto_hide
Whether or not to hide the overlay automatically. Defaults to true; only override this if you're doing some custom logic to determine when to hide the overlay. 

#### overlay_id
The HTML ID of your overlay container. Defaults to fakeloader-overlay

#### fade_timeout
The speed with which the overlay will fade out, in milliseconds. Defaults to 200.

#### wait_for_images
If set to true, FakeLoader will look for the [waitForImages](https://github.com/alexanderdickson/waitForImages) plugin, and use it to determine when to hide the overlay. Defaults to false.

#### wait_for_images_selector
If wait_for_images is enabled and available, this selector will be passed to waitForImages. Defaults to 'body'

### API 

You can hide or show the overlay manually by calling:

```javascript
window.FakeLoader.showOverlay();
```

```javascript
window.FakeLoader.hideOverlay();
```

### DEMO
http://jimkeller.github.io/fakeloader/demo/page1.html
