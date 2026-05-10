$(function () {
    // Select2 search for addresses
    $('#address-registry-search').select2({
        ajax: {
            url: '/api/addresses-bridge/search.json',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    country_code: $('#address-registry-search').data('country-code'),
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
        placeholder: 'Search address…'
    });

    var $form = $('#address-registry-search').closest('form');

    // Autorefresh after address selection
    $('#address-registry-search').on('select2:select', function (e) {
        // Add hidden input to indicate that the form should be refreshed (without saving)
        $('<input>').attr({
            type: 'hidden',
            name: 'refresh',
            value: 'refresh'
        }).appendTo($form);

        $form.submit();
    });

    // Autorefresh after country_id changes and clear address fields
    $('#country-id').on('change', function () {

        // Clear Select2
        $('#address-registry-search').val(null).trigger('change');

        // Add hidden input to indicate that the form should be refreshed (without saving)
        $('<input>').attr({
            type: 'hidden',
            name: 'refresh',
            value: 'refresh'
        }).appendTo($form);

        $form.submit();
    });
});
