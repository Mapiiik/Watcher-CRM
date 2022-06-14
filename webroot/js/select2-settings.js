$(document).ready(function() {
    $('select:not(.button)').select2({
        minimumResultsForSearch: 10,
        width: '100%',
        templateResult: function (data) {    
            // We only really care if there is an element to pull classes and styles from
            if (!data.element) {
                return data.text;
            }

            var $element = $(data.element);

            var $wrapper = $('<span style="' + $element[0].style.cssText + '"></span>');
            $wrapper.addClass($element[0].className);

            $wrapper.text(data.text);

            return $wrapper;
        }
    });
});