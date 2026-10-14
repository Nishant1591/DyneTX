/**
 * Video Popup TinyMCE Integration
 * Adds custom button and dialog to WordPress classic editor for inserting video popups.
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

(function() {

    var lang = window.vpTinyMCELang || {};
    
    /**
     * Gets translated text string from language object
     * Falls back to key name if translation not available
     * 
     * @param {string} key Text identifier
     * @return {string} Translated text or original key
     */
    function vpTinyMceText(key) {
        return lang[key] || key;
    }

    /**
     * Registers the Video Popup plugin with TinyMCE
     * Adds custom button with popup dialog for video configuration
     * 
     * @param {object} editor TinyMCE editor instance
     * @param {string} url Plugin base URL
     */
    tinymce.PluginManager.add('video_popup_tinymce_plugin', function( editor, url ) {

        function addStarLabelClass() {
            let control = this;
            let labelId = control._id + '-l';
            let labelElement = document.getElementById(labelId);
            
            if (labelElement && labelElement.tagName.toLowerCase() === 'label') {
                labelElement.classList.add('vp-star-label');
            }
        }

        /**
         * Adds custom button to TinyMCE toolbar
         * Configures icon, tooltip and click behavior
         */
        editor.addButton( 'video_popup_tinymce_plugin', {

            text: false,

            title: vpTinyMceText('button_title'),

            icon: 'vp-mce-new-icon',

            /**
             * Button click handler that opens configuration dialog
             * Processes selected text and existing link attributes
             */
            onclick: function() {
                var vptmceGetSelection = tinyMCE.activeEditor.selection;
                var vptmceSelectedText = vptmceGetSelection.getContent( { format: "text" } );

                if( !vptmceSelectedText ){
                    alert(vpTinyMceText('error_no_selection'));
                    e.preventDefault();
                    return;
                }

                // Get existing attributes if editing an existing link
                var selectedNode = vptmceGetSelection.getNode();
                var existingHref = selectedNode.getAttribute('href') || '';
                var existingTitle = selectedNode.getAttribute('title') || '';
                var existingRel = selectedNode.getAttribute('rel');
                var existingAutoplay = selectedNode.className && selectedNode.className.includes('vp-a');
                var existingMute = selectedNode.getAttribute('data-mute');
                var existingWrapClose = selectedNode.getAttribute('data-wrap-c');
                var existingCookie = selectedNode.getAttribute('data-cookie');

                var isGlobalNoCookie = vpTinyMceText('global_no_cookie') == 'true' ? true : false;
                var isGlobalWrapClose = vpTinyMceText('global_wrap_close') == 'true' ? true : false;

                /**
                 * Creates and opens dialog window with all video settings
                 * Pre-populates fields when editing existing video popup link
                 */
                editor.windowManager.open({
                    title: vpTinyMceText('dialog_title'),
                    classes: 'vp-tinymce-dialog custom-video-popup-dialog',
                    id: 'video-popup-dialog',
                    body: [
                        {
                            type: 'textbox',
                            name: 'text',
                            label: vpTinyMceText('text_label'),
                            value: vptmceSelectedText ? String(vptmceSelectedText).trim() : '',
                            maxWidth: 400,
                            tooltip: vpTinyMceText('text_tooltip')
                        },
                        {
                            type: 'textbox',
                            name: 'title',
                            label: vpTinyMceText('title_label'),
                            value: existingTitle ? String(existingTitle).trim() : '',
                            maxWidth: 400,
                            tooltip: vpTinyMceText('title_tooltip')
                        },
                        {
                            type: 'textbox',
                            name: 'url',
                            label: vpTinyMceText('url_label'),
                            value: existingHref ? String(existingHref).trim() : '',
                            maxWidth: 960,
                            tooltip: vpTinyMceText('url_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'rel_nofollow',
                            label: vpTinyMceText('rel_nofollow_label'),
                            checked: existingRel == 'nofollow' || existingRel == 'nofollow noreferrer',
                            tooltip: vpTinyMceText('rel_nofollow_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'autoplay',
                            label: vpTinyMceText('autoplay_label'),
                            checked: existingAutoplay,
                            tooltip: vpTinyMceText('autoplay_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'mute',
                            label: vpTinyMceText('mute_label'),
                            checked: existingMute === '1',
                            tooltip: vpTinyMceText('mute_tooltip')
                        },
                        {
                            type: 'checkbox',
                            label: vpTinyMceText('disable_controls_label'),
                            checked: false,
                            disabled: true,
                            tooltip: vpTinyMceText('disable_controls_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'checkbox',
                            label: vpTinyMceText('disable_related_label'),
                            checked: false,
                            disabled: true,
                            tooltip: vpTinyMceText('disable_related_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('start_time_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('start_time_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('end_time_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('end_time_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'listbox',
                            label: vpTinyMceText('minimize_design_label'),
                            value: 'none',
                            values: [
                                {text: vpTinyMceText('minimize_none'), value: 'none'},
                                {text: vpTinyMceText('minimize_left'), value: 'left', disabled: true},
                                {text: vpTinyMceText('minimize_right'), value: 'right', disabled: true}
                            ],
                            tooltip: vpTinyMceText('minimize_design_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('width_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('width_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('height_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('height_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'checkbox',
                            label: vpTinyMceText('aspect_ratio_label'),
                            checked: false,
                            disabled: true,
                            tooltip: vpTinyMceText('aspect_ratio_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('overlay_color_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('overlay_color_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('overlay_opacity_label'),
                            value: '',
                            disabled: true,
                            maxWidth: 120,
                            tooltip: vpTinyMceText('overlay_opacity_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'textbox',
                            name: 'img_url',
                            label: vpTinyMceText('img_url_label'),
                            value: '',
                            maxWidth: 960,
                            tooltip: vpTinyMceText('img_url_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'show_thumbnail',
                            label: vpTinyMceText('show_thumbnail_label'),
                            checked: false,
                            tooltip: vpTinyMceText('show_thumbnail_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'no_cookie',
                            label: vpTinyMceText('no_cookie_label'),
                            checked: existingCookie === 'true' || isGlobalNoCookie === true && (!existingCookie || existingCookie !== 'false'),
                            tooltip: vpTinyMceText('no_cookie_tooltip')
                        },
                        {
                            type: 'checkbox',
                            name: 'disable_wrap_close',
                            label: vpTinyMceText('disable_wrap_close_label'),
                            checked: existingWrapClose === 'true' || isGlobalWrapClose === true && (!existingWrapClose || existingWrapClose !== 'false'),
                            tooltip: vpTinyMceText('disable_wrap_close_tooltip')
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('shortcode_label'),
                            value: '[video_popup text="" title="" url="" auto="" mute="" nc="" wc="" n="" img="" p="" video_image=""]',
                            maxWidth: 960,
                            classes: 'vp-shortcode-field',
                            readonly: true,
                            onclick: function() {
                                this.getEl().select();
                            },
                            tooltip: vpTinyMceText('shortcode_tooltip')
                        },
                        {
                            type: 'textbox',
                            label: vpTinyMceText('opl_shortcode_label'),
                            value: '[video_popup_opl url="" auto="2" mute="1"]',
                            maxWidth: 640,
                            classes: 'vp-shortcode-field',
                            readonly: true,
                            tooltip: vpTinyMceText('opl_shortcode_tooltip'),
                            onPostRender: addStarLabelClass
                        },
                        {
                            type: 'container',
                            layout: 'flex',
                            direction: 'column',
                            spacing: 12,
                            items: [
                                {
                                    type: 'label',
                                    text: vpTinyMceText('no_cookie_note_label'),
                                    classes: 'vp-no-cookie-note-label',
                                    style: isGlobalNoCookie === true && (!existingCookie || existingCookie !== 'false') ? 'display:block;' : 'display:none;'
                                },
                                {
                                    type: 'label',
                                    text: vpTinyMceText('close_wrap_note_label'),
                                    classes: 'vp-close-wrap-note-label',
                                    style: isGlobalWrapClose === true && (!existingWrapClose || existingWrapClose !== 'false') ? 'display:block;' : 'display:none;'
                                }
                            ]
                        },
                        {
                            type: 'container',
                            layout: 'flex',
                            direction: 'row',
                            align: 'center',
                            spacing: 10,
                            items: [
                                {
                                    type: 'button',
                                    text: vpTinyMceText('get_bldr_guide_button'),
                                    tooltip: vpTinyMceText('get_bldr_guide_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-bldr-guide-p-btn',
                                    onclick: function() {
                                        window.open(vpTinyMceText('bldr_guide_url'), '_blank');
                                    }
                                },
                                {
                                    type: 'button',
                                    text: vpTinyMceText('general_settings_button'),
                                    tooltip: vpTinyMceText('general_settings_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-general-s-btn',
                                    onclick: function() {
                                        window.open(vpTinyMceText('gs_settings_url'), '_blank');
                                    }
                                },
                                {
                                    type: 'button',
                                    text: vpTinyMceText('onpage_load_settings_button'),
                                    tooltip: vpTinyMceText('onpage_load_settings_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-onpageload-s-btn',
                                    onclick: function() {
                                        window.open(vpTinyMceText('opl_settings_url'), '_blank');
                                    }
                                },
                                {
                                    type: 'button',
                                    text: vpTinyMceText('shortcode_usage_button'),
                                    tooltip: vpTinyMceText('shortcode_usage_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-shortcode-p-btn',
                                    onclick: function() {
                                        window.open(vpTinyMceText('scu_page_url'), '_blank');
                                    }
                                },
                                {
                                    type: 'button',
                                    text: vpTinyMceText('get_demo_button'),
                                    tooltip: vpTinyMceText('get_demo_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-get-demo-btn',
                                    onclick: function() {
                                        window.open('https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo', '_blank');
                                    }
                                },
                                {
                                    type: 'button',
                                    text: vpTinyMceText('get_premium_button'),
                                    tooltip: vpTinyMceText('get_premium_button_tooltip'),
                                    classes: 'vp-general-style-btn vp-get-premium-btn',
                                    onclick: function() {
                                        window.open('https://videopopup.net/?utm_source=plugin&utm_medium=button&utm_campaign=tmce_builder#get-premium', '_blank');
                                    }
                                }
                            ]
                        }
                    ],

                    /**
                     * Form submission handler with extensive validation
                     * Creates properly formatted video popup link with all attributes
                     * 
                     * @param {object} e Form submission event
                     */
                    onsubmit: function(e) {
                        var data = e.data;

                        // Trim text field values
                        if (data.text) data.text = String(data.text).trim();
                        if (data.url) data.url = data.url.trim();
                        if (data.img_url) data.img_url = String(data.img_url).trim();
                        if (data.title) data.title = String(data.title).trim();

                        // Check if video URL is provided
                        if (!data.url) {
                            alert(vpTinyMceText('error_no_url'));
                            e.preventDefault();
                            return;
                        }

                        // Determine video type
                        var videoType = 'vp-unknown-type_tinymce';
                        var isYouTube = false;
                        var isVimeo = false;
                        var isMP4 = false;
                        var isWebM = false;

                        if (data.url) {
                            if (/(youtube\.com|youtu\.be|youtube-nocookie\.com)/i.test(data.url)) {
                                videoType = 'vp-yt-type';
                                isYouTube = true;
                            } else if (/vimeo\.com/i.test(data.url)) {
                                videoType = 'vp-vim-type';
                                isVimeo = true;
                            } else if (/\.mp4(\?.*)?$/i.test(data.url)) {
                                videoType = 'vp-mp4-type';
                                isMP4 = true;
                            } else if (/\.webm(\?.*)?$/i.test(data.url)) {
                                videoType = 'vp-webm-type';
                                isWebM = true;
                            }
                        }

                        // Check if URL is not a supported video platform
                        if ( !isYouTube && !isVimeo && !isMP4 && !isWebM ) {
                            alert(vpTinyMceText('error_unsupported_url'));
                            e.preventDefault();
                            return;
                        }

                        // Check if thumbnail feature is used with non-YouTube/Vimeo URLs
                        if (data.show_thumbnail && !isYouTube) {
                            alert(vpTinyMceText('error_youtube_only_thumbnail'));
                            e.preventDefault();
                            return;
                        }

                        // Check if YouTube/Vimeo-only features are used with non-YouTube/Vimeo URLs
                        if (data.no_cookie && !isYouTube && !isVimeo) {
                            alert(vpTinyMceText('error_youtube_vimeo_only_nocookie'));
                            e.preventDefault();
                            return;
                        }

                        // Handle thumbnail generation
                        var thumbnailContent = '';
                        thumbnailContent = data.img_url ? '<img class="vp-img" src="'+data.img_url+'">' : '';
                        if ( data.show_thumbnail && isYouTube ) {
                            var youtubeMatch = data.url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/|youtube-nocookie\.com\/(?:watch\?v=|embed\/|v\/)|m\.youtube\.com\/watch\?v=)([^&\n?#]+)/);
                            if (youtubeMatch && youtubeMatch[1]) {
                                var youtubeVideoID = youtubeMatch[1];
                                thumbnailContent = '<img class="vp-img vp-yt-img" src="https://img.youtube.com/vi/' + youtubeVideoID + '/sddefault.jpg">';
                            }
                        }

                        // Build class attribute
                        var classNames = [];
                        if (data.autoplay) {
                            classNames.push('vp-a');
                        } else {
                            classNames.push('vp-s');
                        }
                        classNames.push(videoType);
                        classNames.push('vp-modal-click vp-modal-click_tinymce');
                        if ( thumbnailContent ) classNames.push('vp-has-img');

                        // Build attributes
                        var attributes = {
                            href: data.url || '#',
                            class: classNames.join(' ')
                        };

                        if (data.title) attributes.title = data.title;
                        if (data.mute) attributes['data-mute'] = '1';

                        attributes.rel = data.rel_nofollow ? 'nofollow noreferrer' : 'noreferrer';

                        if (data.no_cookie) attributes['data-cookie'] = 'true';
                        if ( isGlobalNoCookie && !data.no_cookie ) attributes['data-cookie'] = 'false';

                        if (data.disable_wrap_close) attributes['data-wrap-c'] = 'true';
                        if ( isGlobalWrapClose && !data.disable_wrap_close ) attributes['data-wrap-c'] = 'false';

                        // Build HTML
                        var attrString = '';
                        for (var attr in attributes) {
                            attrString += ' ' + attr + '="' + attributes[attr] + '"';
                        }

                        var linkContent = thumbnailContent || (data.text || vpTinyMceText('default_text'));
                        var html = '<a' + attrString + '>' + linkContent + '</a>';

                        // Check if we're editing an existing link
                        if (selectedNode.tagName === 'A') {
                            // Replace the existing link
                            editor.selection.select(selectedNode);
                            editor.selection.setContent(html);
                        } else {
                            // Insert new content
                            editor.insertContent(html);
                        }

                    }
                });
            }
        });
    });
})();