jQuery( document ).ready( function($) {

    const iaffpro_save_column_title_html = [];
    
    // On clicking the Edit Attributes (Edit icon) button or the image attribute data in the columns.
    $( document ).on( 'click', '.iaffpro-dashicons-edit, .iaffpro_image_title_data, .iaffpro_image_alt_data, .iaffpro_image_caption_data, .iaffpro_image_description_data', function(e) {

        e.preventDefault();
        
        /**
         * The ID of the parent will be the array key from iaffpro_manage_media_columns_add_columns().
         * Example: iaffpro_image_title for image title.
         */
        var attribute = $(this).data( 'image-attribute' );

        // Save current HTML of column title so that it can be replenished later once the job is done.
        iaffpro_save_column_title_html[attribute] = $( 'th.column-' + attribute ).html()

        // Replace edit button (icon) with Save all and Cancel buttons.
        $( 'th.column-' + attribute ).html( 
            '<span style="padding: 11px;"><a href="#" class="button button-primary button-small iaffpro-button-small-save-all" data-image-attribute="' + attribute + '">Save All</a>&nbsp;<a href="#" class="button button-primary button-small iaffpro-button-small-cancel" data-image-attribute="' + attribute + '">Cancel</a></span>' 
        );

        $( '.' + attribute + '_data' ).hide(); // Hide column text.
        $( '.' + attribute + '_input' ).show(); // Show the input box.
    });

    // Cancel button.
    $( document ).on( 'click', '.iaffpro-button-small-cancel', function(e) {
		
        e.preventDefault();
        var attribute = $(this).data( 'image-attribute' );

        // Restore previous HTML.
        $( 'th.column-' + attribute ).html( iaffpro_save_column_title_html[attribute] );

        $( '.iaffpro-' + attribute + '-save-all-spinner' ).hide(); // Hide spinner (if it exist)
        $( '.' + attribute + '_input' ).hide(); // Hide input text box.
        $( '.' + attribute + '_data' ).show(); // Show column data.
    });

    // Save all button.
    $( document ).on( 'click', '.iaffpro-button-small-save-all', function(e) {
		
        e.preventDefault();
        var attribute = $(this).data( 'image-attribute' );
        
        /**
         * This array will hold all the information that is passed to PHP.
         * 
         * attribute: The image attribute are we editing.
         * id: ID of the image to be updated.
         * value: The new value entered by the user in the input text box.
         */
        var image_data = [];

        // Show spinner.
        $( '.iaffpro-' + attribute + '-save-all-spinner' ).show();

        // Loop through all input elements.
        $( '.' + attribute +'_input' ).each( ( index, element ) => {

            /**
             * Only add values that user edited into an array to be sent to PHP later.
             * 
             * Original value is stored in data-iaffpro_image_title (for example). 
             * Comparing this with the value of input box will show the edited ones.
             */
            if ( $( element ).data( attribute ) !== $( element ).val() ) {
                image_data.push({
                    attribute: attribute,
                    id: $( element ).data( attribute + '_attachment_id' ),
                    value: $( element ).val()
                });
                
                /**
                 * Update data-iaffpro_{attribute} with the value that was typed in.
                 * 
                 * This is important because we check this data attribute against the value in the input box to see
                 * if the user edited an attribute. Refer above.
                 */
                $( element ).attr( 'data-' + attribute, $( element ).val() );
            }
        });

        var data = {
            action: 'iaffpro_media_library_columns_edit_data',
            security: iaffpro_ml_ajax.nonce,
            data: image_data,
        };

        // Ajax call.
        $.post( 
            iaffpro_ml_ajax.ajax_url, 
            data,
            function( response ) { // callback.
                
                if ( response.success === true ) {

                    // Update column data with new value.
                    $( image_data ).each( function( key, value ) {
                        
                        // Get the new value from the input text box. value['id'] = attachment ID.
                        var new_value = $( '#' + attribute + '_input_' + value['id'] ).val();

                        // Update #iaffpro_{attribute}_data_{id} with the new value.
                        $( '#' + attribute + '_data_' + value['id'] ).text( new_value );

                        // Show "Saved" message. This will be removed few lines later after a timeout.
                        $( '.iaffpro-' + attribute + '-save-all-success.attachment_id_' + value['id'] ).show(); // Show Saved message.
                    });
                    
                    $( '.iaffpro-' + attribute + '-save-all-spinner' ).hide(); // Hide spinner.
                    $( '.' + attribute + '_input' ).hide(); // Hide input text box.
                    $( '.' + attribute + '_data' ).show(); // Reveal column data.
                    
                    // Hide "Saved" message after a timeout.
                    setTimeout( function() {
                        $( '.iaffpro-' + attribute + '-save-all-success' ).hide(); // Hide Saved message.
                    }, 1500 );
                    
                    // Restore previous HTML.
                    $( 'th.column-' + attribute ).html( iaffpro_save_column_title_html[attribute] );
                }
			},
            'json'
        ).fail( function( response ) {
            if ( response.status == 403 ) {
                alert( response.responseJSON.data );
            } else {
                alert( 'Saving failed. Edits will be lost if you refresh the page. Please try again in a few minutes.' );
            }
        });
    });
});