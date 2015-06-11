var initPhotoSwipeFromDOM = function(collection, options) {

    // parse slide data (url, title, size ...) from DOM elements 
    // (children of gallerySelector)
    var parseThumbnailElements = function() {
        var items = [],
            figureEl,
            linkEl,
            size,
            item;

        for(var i = 0; i < collection.length; i++) {

            figureEl = collection[i]; // <figure> element

            // include only element nodes 
            if(figureEl.nodeType !== 1) {
                continue;
            }

            linkEl = figureEl; // <a> element
            if(linkEl.tagName && linkEl.tagName.toUpperCase() === 'FIGURE'){
                linkEl = linkEl.children[0];
            }
            var sizeAttribute = linkEl.getAttribute('data-size');
            if(sizeAttribute) {
                size = sizeAttribute.split('x');

                // create slide object
                item = {
                    src: linkEl.getAttribute('href'),
                    w: parseInt(size[0], 10),
                    h: parseInt(size[1], 10)
                };

                if (linkEl.children.length > 0) {
                    // <img> thumbnail element, retrieving thumbnail url
                    item.msrc = linkEl.children[0].getAttribute('src');
                }

                var possibleFigure = linkEl.parentElement;
                if (possibleFigure.tagName && possibleFigure.tagName.toUpperCase() === 'FIGURE') {
                    if(possibleFigure.children.length > 1){
                        item.title = possibleFigure.children[1].innerHTML;
                    }
                }


                item.el = figureEl; // save link to element for getThumbBoundsFn
                items.push(item);
            }
        }

        return items;
    };

    // find nearest parent element
    var closest = function closest(el, fn) {
        return el && ( fn(el) ? el : closest(el.parentNode, fn) );
    };

    // triggers when user clicks on thumbnail
    var onThumbnailsClick = function(e) {
        e = e || window.event;
        e.preventDefault ? e.preventDefault() : e.returnValue = false;

        var eTarget = e.target || e.srcElement;

        // find root element of slide
        var clickedLink = closest(eTarget, function(el) {
            return (el.tagName && el.tagName.toUpperCase() === 'A' );
        });
        var clickedGallery = closest(clickedLink, function(el) {
            return (el.tagName && el.tagName.toUpperCase() === 'DIV' );
        });

        var index = -1;
        for(var i = 0;i<collection.length;i++){
            if(collection[i] === clickedLink){
                index  = i;
                break;
            }
        }
        if(index >= 0){
            openPhotoSwipe(index);
        }

        return false;
    };

    // parse picture index and gallery index from URL (#&pid=1&gid=2)
    var photoswipeParseHash = function() {
        var hash = window.location.hash.substring(1),
            params = {};

        if(hash.length < 5) {
            return params;
        }

        var vars = hash.split('&');
        for (var i = 0; i < vars.length; i++) {
            if(!vars[i]) {
                continue;
            }
            var pair = vars[i].split('=');
            if(pair.length < 2) {
                continue;
            }
            params[pair[0]] = pair[1];
        }

        if(params.gid) {
            params.gid = parseInt(params.gid, 10);
        }

        return params;
    };

    var openPhotoSwipe = function(index, disableAnimation, fromURL) {
        var pswpElement = document.querySelectorAll('.pswp')[0],
            gallery,
            items;

        items = parseThumbnailElements();
        // define options (if needed)
        if(!options){
            options = {};
        }

        options.getThumbBoundsFn = function(index) {
            // See Options -> getThumbBoundsFn section of documentation for more info
            var thumbnail = items[index].el.getElementsByTagName('img')[0], // find thumbnail
                pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                rect = thumbnail.getBoundingClientRect();

            return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
        };


        // PhotoSwipe opened from URL
        if(fromURL) {
            if(options.galleryPIDs) {
                // parse real index when custom PIDs are used 
                // http://photoswipe.com/documentation/faq.html#custom-pid-in-url
                for(var j = 0; j < items.length; j++) {
                    if(items[j].pid == index) {
                        options.index = j;
                        break;
                    }
                }
            } else {
                // in URL indexes start from 1
                options.index = parseInt(index, 10) - 1;
            }
        } else {
            options.index = parseInt(index, 10);
        }

        // exit if index not found
        if( isNaN(options.index) ) {
            return;
        }

        if(disableAnimation) {
            options.showAnimationDuration = 0;
        }

        // Pass data to PhotoSwipe and initialize it
        gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();
    };

    // loop through all gallery elements and bind events


    for(var i = 0; i < collection.length; i++) {
        collection[i].setAttribute('data-pswp-uid', 1);
        collection[i].onclick = onThumbnailsClick;
    }

    // Parse URL and open gallery if it contains #&pid=3
    var hashData = photoswipeParseHash();
    if(hashData.pid) {
        openPhotoSwipe( hashData.pid , true, true );
    }
};
