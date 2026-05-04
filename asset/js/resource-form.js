$(document).on('o:prepare-value', function(e, type, value) {
    if (typeof type === 'undefined' || !type.startsWith('table:')) {
        return;
    }
    value.find('select.table-data-type').chosen({
        width: '100%',
        disable_search_threshold: 25,
        allow_single_deselect: true,
        max_shown_results: 1000,
    });
});
