function popupwindow(url, title, w, h) {
    var y = window.outerHeight / 2 + window.screenY - ( h / 2)
    var x = window.outerWidth / 2 + window.screenX - ( w / 2)
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + y + ', left=' + x);
}    

// The page is read again once a window it opened has closed, and it comes back where the reader
// was rather than at the top - the row they were working on is still in front of them. Kept for
// one reading of this page only, and only in this tab.
var WHERE_YOU_WERE = 'where-you-were';

// Where a window this one opened said the reader is going next, where it said anything. A window
// that steps out of itself hands the address over rather than setting `location` on this one from
// in there: the two would be a race between navigations, and the reading again below - which
// starts within a tenth of a second of the window closing - won it every time. So it is said here,
// and the one place that decides where this page goes reads it.
var goingOnTo = null;

// What to do once a window this one opened has closed: go where it said, or read again what was
// standing here. Where nothing was said this is exactly what it always did.
function whenItHasClosed() {
    if (goingOnTo === null) {
        readAgainWhereYouWere();

        return;
    }

    var going = goingOnTo;
    goingOnTo = null;
    location = going;
}

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
                whenItHasClosed();
            }
        }, 100);
    });

    $(".smart-link").on("click", function(e) {
        var smartwin = window.open(this.href, this.target, '', true);
        e.preventDefault();
        
        var timer = setInterval(function() {
            if(smartwin.closed) {
                clearInterval(timer);
                whenItHasClosed();
            }
        }, 100);
    });

    $(".leave-window").on("click", function(e) {
        // The print page is opened into a window of its own, so a link out of it belongs in the
        // window that opened it - otherwise the next print would reuse a tab showing something else.
        //
        // Where it goes is handed over rather than set from here: the window that opened this one
        // is watching for this one to close, and would read itself again the moment it does. Two
        // navigations, and this one lost. Said there, it is the only one.
        if (window.opener && !window.opener.closed) {
            e.preventDefault();
            window.opener.goingOnTo = this.href;
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
