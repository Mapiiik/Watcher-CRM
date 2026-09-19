function popupwindow(url, title, w, h) {
    var y = window.outerHeight / 2 + window.screenY - ( h / 2)
    var x = window.outerWidth / 2 + window.screenX - ( w / 2)
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + y + ', left=' + x);
}    

// The page is read again once a window it opened has closed, and it comes back where the reader
// was rather than at the top - the row they were working on is still in front of them. Kept for
// one reading of this page only, and only in this tab.
var WHERE_YOU_WERE = 'where-you-were';

function readAgainWhereYouWere() {
    var here = location.href.split("#")[0];

    try {
        sessionStorage.setItem(WHERE_YOU_WERE, JSON.stringify({url: here, y: window.scrollY}));
    } catch (e) {
        // Without storage the page simply starts at the top, as it always did.
    }

    location = here;
}

function comeBackWhereYouWere() {
    var saved = null;

    try {
        saved = JSON.parse(sessionStorage.getItem(WHERE_YOU_WERE) || 'null');
        sessionStorage.removeItem(WHERE_YOU_WERE);
    } catch (e) {
        return;
    }

    if (!saved || saved.url !== location.href.split("#")[0]) {
        return;
    }

    var go = function() {
        window.scrollTo(0, saved.y);
    };

    // Once now, and again when the images are in, since they may still move things down.
    go();
    if (document.readyState !== 'complete') {
        $(window).one("load", go);
    }
}

$(document).ready(function() {
    comeBackWhereYouWere();

    $(".win-link").on("click", function(e) {
        var url = new URL(this.href);
        url.searchParams.append('win-link', 'true');

        var win = popupwindow(url.href, 'win-link', 1200, 700);
        e.preventDefault();
        
        var timer = setInterval(function() {   
            if(win.closed) {  
                clearInterval(timer);  
                readAgainWhereYouWere();
            }  
        }, 100);            
    });

    $(".smart-link").on("click", function(e) {
        var smartwin = window.open(this.href, this.target, '', true);
        e.preventDefault();
        
        var timer = setInterval(function() {   
            if(smartwin.closed) {  
                clearInterval(timer);  
                readAgainWhereYouWere();
            }  
        }, 100);            
    });

    $(".leave-window").on("click", function(e) {
        // The print page is opened into a window of its own, so a link out of it belongs in the
        // window that opened it - otherwise the next print would reuse a tab showing something else.
        if (window.opener && !window.opener.closed) {
            e.preventDefault();
            window.opener.location = this.href;
            window.opener.focus();
            window.close();
        }
    });

    $(".refresh-on-return").on("click", function() {
        // What was handed over is written down where the page it was asked from lists it, so that
        // list is out of date the moment it opens. Reading it again when the reader comes back
        // covers both closing the document and only switching away from it.
        $(window).off("focus.refresh").one("focus.refresh", function() {
            readAgainWhereYouWere();
        });
    });
});
