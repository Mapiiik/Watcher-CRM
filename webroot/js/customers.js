$(function () {
    var $search = $('#business-register-search');
    var $source = $('#business-register-source');

    // Select2 search for companies in the selected business register
    $search.select2({
        width: '100%',
        ajax: {
            url: '/api/business-register-bridge/search.json',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    source: $source.val(),
                    query: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                return {
                    results: data.results,
                    pagination: data.pagination
                };
            }
        },
        minimumInputLength: 3,
        placeholder: $search.data('placeholder')
    });

    var $form = $search.closest('form');

    // Autorefresh after company selection
    $search.on('select2:select', function (e) {
        // Add hidden input to indicate that the form should be refreshed (without saving)
        $('<input>').attr({
            type: 'hidden',
            name: 'refresh',
            value: 'refresh'
        }).appendTo($form);

        $form.submit();
    });

    // Clear the suggestions after the register changes - they belong to the previous one
    $source.on('change', function () {
        $search.val(null).trigger('change');
    });
});
