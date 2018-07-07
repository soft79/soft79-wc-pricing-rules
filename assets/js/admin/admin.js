jQuery( function( $ ) {
    //Dynamically adding of rows
    $( '.soft79_bulk_rules' ).on( 'click', '.soft79_wcpr_add_rule a', function( event ) {
        event.preventDefault();
        var newRow = $(this).closest('.soft79_bulk_rules').find('.soft79_wcpr_new_row').html();
        var rowElements = $.parseHTML(newRow);
        $('.soft79_wcpr_add_rule').before(rowElements);
    });

    //Dynamically deletion of rows
    $( '.soft79_bulk_rules' ).on( 'click', '.soft79_wcpr_delete_row', function( event ) {
            event.preventDefault();
            $(this).closest(".soft79_bulk_row").remove();
    } );

});
