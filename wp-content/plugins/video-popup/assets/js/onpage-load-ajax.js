/**
 * Handles AJAX-based on-page-load functionality for Video Popup
 * Triggers automatic popup display based on server-side conditions
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */


/**
 * Main execution block for on-page-load functionality
 * Runs after DOM content is fully loaded and page resources are complete
 */
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('load', () => {

        /**
         * Primary function that handles the AJAX request and popup display
         * Validates configuration, makes AJAX call, and processes response
         * @async
         */
        async function VideoPopupOnPageLoad() {

            if ( typeof theVideoPopupOplVars === 'undefined' ) {
                console.log('onpage-load.js: theVideoPopupOplVars is not defined. AJAX request aborted.');
                return;
            }

            const currentId         = theVideoPopupOplVars.current_id || 'none';
            const currentLocation   = theVideoPopupOplVars.current_location || 'none';
            const userType          = theVideoPopupOplVars.user_type || 'none';
            const ajaxAction        = theVideoPopupOplVars.ajax_action || '';
            const ajaxUrl           = theVideoPopupOplVars.ajax_url || '';
            const debugMode         = theVideoPopupOplVars.debug && theVideoPopupOplVars.debug == 1 ? true : false;

            if ( debugMode ) {
                console.log(`onpage-load.js (on page load debug): Current ID by js parameter is "${currentId}".`);
                console.log(`onpage-load.js (on page load debug): Current location by js parameter is "${currentLocation}".`);
                console.log(`onpage-load.js (on page load debug): User type by js parameter is "${userType}".`);
                console.log(`onpage-load.js (on page load debug): Ajax action by js parameter is "${ajaxAction}".`);
                console.log(`onpage-load.js (on page load debug): Ajax URL by js parameter is "${ajaxUrl}".`);
                console.log(''); // an empty line
            }

            if ( !currentId || !currentLocation || !userType || !ajaxAction || !ajaxUrl ) {
                if ( debugMode ) {
                    console.log('onpage-load.js (on page load debug): Missing required data (currentId, currentLocation, userType, ajaxAction, or ajaxUrl). AJAX request aborted.');
                    console.log('Suggested Solutions:');
                    console.log('   1. Remove the Video Popup plugin and reinstall it.');
                    console.log('   2. Clear the cache if you are using a caching plugin on your website.');
                }
                return;
            }

            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=' + encodeURIComponent(ajaxAction) + '&current_id=' + encodeURIComponent(currentId) + '&current_location=' + encodeURIComponent(currentLocation) + '&user_type=' + encodeURIComponent(userType)
                });

                if ( !response.ok ) {
                    if ( debugMode ) {
                        console.log('onpage-load.js (on page load debug): Fetch failed with status ' + response.status);
                    }
                    return;
                }

                const textResponse = await response.text();

                if ( !textResponse ) {
                    console.log('Suggested Solutions:');
                    console.log('   1. Remove the Video Popup plugin and reinstall it.');
                    console.log('   2. Clear the cache if you are using a caching plugin on your website.');
                    return;
                }

                if ( textResponse.trim() == 'no_options' ) {
                    console.log('onpage-load.js: No options.');
                    console.log('Suggested Solutions:');
                    console.log('   1. Go to Video Popup > On Page Load > and click the "Save Changes" button.');
                    console.log('   2. Remove the Video Popup plugin and reinstall it.');
                    console.log('   3. Clear the cache if you are using a caching plugin on your website.');
                    return;
                }

                if ( textResponse.trim() == 'opl_disabled' ) {
                    if ( debugMode ) {
                        console.log('onpage-load.js (on page load debug): On Page Load feature is disabled. Go to Video Popup > On Page Load > General Options, and enable it.');
                    }
                    return;
                }

                if ( textResponse.trim() == 'no_url' ) {
                    if ( debugMode ) {
                        console.log('onpage-load.js (on page load debug): No video URL. Go to Video Popup > On Page Load > Video Settings, and enter a video URL.');
                    }
                    return;
                }

                if ( textResponse.trim() == 'invalid_url' ) {
                    if ( debugMode ) {
                        console.log('onpage-load.js (on page load debug): Invalid video URL. Go to Video Popup > On Page Load > Video Settings, and enter a YouTube, Vimeo, or direct video URL (MP4 or WebM only).');
                    }
                    return;
                }

                if ( textResponse.trim() == 'hide_by_cookie' ) {
                    if ( debugMode ) {
                        console.log('onpage-load.js (on page load debug): The video popup was prevented on page load by cookie.');
                        console.log('onpage-load.js (on page load debug): You need to wait until the cookie expires, change the value of the "Cookie Expiry" field, or clear its value.');
                    }
                    return;
                }

                const script = document.createElement('script');
                script.textContent = textResponse;
                document.body.appendChild(script);

                if ( debugMode ) {
                    console.log('=== Video Popup - On Page Load AJAX Debug - Start ===');
                    console.log(textResponse);
                    console.log('=== Video Popup - On Page Load AJAX Debug - End ===');
                    console.log(''); // an empty line
                }
            } catch (error) {
                if (debugMode) {
                    console.log('onpage-load.js (on page load debug): Fetch request failed', error);
                }
            }
        }

        // Initialize the on-page-load process
        VideoPopupOnPageLoad();
    });
});