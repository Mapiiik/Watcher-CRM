function popupwindow(url, title, w, h) {
    var y = window.outerHeight / 2 + window.screenY - ( h / 2)
    var x = window.outerWidth / 2 + window.screenX - ( w / 2)
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + y + ', left=' + x);
}    
$(document).ready(function() {
    $(".win-link").on("click", function(e) {
        var url = new URL(this.href);
        url.searchParams.append('win-link', 'true');

        var win = popupwindow(url.href, 'win-link', 1200, 700);
        e.preventDefault();
        
        var timer = setInterval(function() {   
            if(win.closed) {  
                clearInterval(timer);  
                location = location.href.split("#")[0];
            }  
        }, 100);            
    });

    $(".smart-link").on("click", function(e) {
        var smartwin = window.open(this.href, this.target, '', true);
        e.preventDefault();
        
        var timer = setInterval(function() {   
            if(smartwin.closed) {  
                clearInterval(timer);  
                location = location.href.split("#")[0];
            }  
        }, 100);            
    });
});
