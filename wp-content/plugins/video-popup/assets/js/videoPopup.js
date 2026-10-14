/**
 * Pure JavaScript Video Popup Library - Developed by Alobaidi.
 * Core library that manages the popup display, video embedding, and all related functionality
 * 
 * @author   Alobaidi
 * @version  2.0.4
 */


/**
 * Main VideoPopup class
 * Manages popup creation, video embedding and all popup behaviors
 */
class VideoPopup {
    /**
     * Initializes plugin with customizable options
     * @param {Object} options Configuration options
     */
    constructor(options = {}) {
        this.defaults = {
            autoplay: this.validateBoolean(options.autoplay, true),
            wrapClose: this.validateBoolean(options.wrapClose, false),
            noCookie: this.validateBoolean(options.noCookie, false),
            handleVars: this.validateBoolean(options.handleVars, false),
            onPageLoad: this.validateBoolean(options.onPageLoad, false),
            oplCookie: options.oplCookie || false,
            debug: this.validateBoolean(options.debug, false)
        };
        
        this.init();
    }

    /**
     * Initializes event listeners
     */
    init() {
        this.addClickListener();
    }

    /**
     * Adds click event listeners to video links
     */
    addClickListener() {
        document.querySelectorAll('a.vp-a, a.vp-s').forEach(link => {
            link.addEventListener('click', async (e) => {

                if ( link.classList.contains('vp-a') ) {
                    e.preventDefault();
                    link.blur();
                    await this.openPopup(link, true);
                }

                else if ( link.classList.contains('vp-s') ) {
                    e.preventDefault();
                    link.blur();
                    await this.openPopup(link, false);
                }
        
            });
        });
    }

    /**
     * Validates boolean values with various formats
     * @param {*} value Value to validate as boolean
     * @param {boolean} defaultValue Default if invalid
     * @return {boolean} Normalized boolean value
     */
    validateBoolean(value, defaultValue) {
        if (value === undefined || value === null) return defaultValue;
        
        if (value === true || value === false) return value;
        
        if (value && typeof value === 'string') {
            const cleanValue = value.trim().toLowerCase();
            if (cleanValue === 'true' || cleanValue === '1') return true;
            if (cleanValue === 'false' || cleanValue === '0') return false;
        }
        
        if (typeof value === 'number') {
            if (value === 1) return true;
            if (value === 0) return false;
        }
        
        return defaultValue;
    }

    /**
     * Trims string values if they exist
     * @param {*} value Value to trim if string
     * @return {*} Trimmed value or original value
     */
    trimIfExists(value) {
        if ( value && (typeof value === 'string' || typeof value === 'number') ) {
            const trimmed = typeof value === 'string' ? value.trim() : value;
            return trimmed === '' ? null : trimmed;
        }
        return value;
    }

    /**
     * Opens popup with video content
     * @param {HTMLElement} element Link element that was clicked
     * @param {boolean} autoplay Whether video should autoplay
     */
    async openPopup(element, autoplay) {
        const data = this.extractData(element);
        const embedHTML = this.generateEmbed(data, autoplay);
        await this.createPopup(embedHTML, data);
    }

    /**
     * Sets cookie with expiration time
     * @param {string} name Cookie name
     * @param {number} days Days until expiration
     */
    setCookie(name, days) {
        if ( !name || !days ) return;
        const expirySec = Math.floor(Date.now() / 1000) + (days * 24 * 60 * 60);
        const expiryDate = new Date(expirySec * 1000);
        document.cookie = `${name}=${expirySec};expires=${expiryDate.toUTCString()};path=/`;
    }

    /**
     * Checks if cookie exists
     * @param {string} name Cookie name
     * @return {boolean} True if cookie exists
     */
    getCookie(name) {
        if ( !name ) return;
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? true : false;
    }

    /**
     * Removes cookie if it exists
     * @param {string} name Cookie name
     */
    unsetCookie(name){
        if ( !name ) return;
        if ( this.getCookie(name) ) {
            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
        }
    }

    /**
     * Extracts data attributes and settings from link element
     * @param {HTMLElement} element Link element
     * @return {Object} Extracted and validated data
     */
    extractData(element) {
        const url = element.href;

        let autoplay_status;
        if ( element.classList.contains('vp-a') ) {
            autoplay_status = 'true (vp-a class)';
        } else if ( element.classList.contains('vp-s') ) {
            autoplay_status = 'false (vp-s class)';
        } else {
            autoplay_status = 'the link are not contains vp-s or vp-a class!';
        }

        const noCookie = this.trimIfExists(element.getAttribute('data-cookie'));
        const wrapClose = this.trimIfExists(element.getAttribute('data-wrap-c'));

        return {
            url: url,
            noCookie: this.validateBoolean(noCookie, this.defaults.noCookie),
            wrapClose: this.validateBoolean(wrapClose, this.defaults.wrapClose),

            videoType: this.detectVideoType(url),
            videoMimeType: this.getDirectVideoMimeType(url),

            isAutoplay: autoplay_status,
            mute: this.trimIfExists(element.getAttribute('data-mute')),

            linkClasses: element.classList,
            handleVars: this.defaults.handleVars,
            onPageLoad: this.defaults.onPageLoad,
            oplCookie: this.defaults.oplCookie
        };
    }

    /**
     * Generates appropriate embed code based on URL type
     * @param {Object} data Video data object
     * @param {boolean} autoplay Whether video should autoplay
     * @return {string|number|null} HTML embed code or error indicator
     */
    generateEmbed(data, autoplay) {
        const url = data.url ? data.url.trim() : null;
        if ( !url ) return null;
        
        if (this.isYouTube(url)) {
            return this.createYouTubeEmbed(url, data, autoplay);
        } else if (this.isVimeo(url)) {
            return this.createVimeoEmbed(url, data, autoplay);
        } else if (this.isDirectVideo(url)) {
            return this.createDirectVideoEmbed(url, data, autoplay);
        }
        
        return 0;
    }

    /**
     * Checks if URL is from YouTube
     * @param {string} url URL to check
     * @return {boolean} True if YouTube URL
     */
    isYouTube(url) {
        return /youtube\.com|youtu\.be|youtube-nocookie\.com/i.test(url);
    }

    /**
     * Checks if URL is from Vimeo
     * @param {string} url URL to check
     * @return {boolean} True if Vimeo URL
     */
    isVimeo(url) {
        return /vimeo\.com/i.test(url);
    }

    /**
     * Checks if URL is direct video file
     * @param {string} url URL to check
     * @return {boolean} True if direct video file
     */
    isDirectVideo(url) {
        return /\.(mp4|webm)(\?.*)?$/i.test(url);
    }

    /**
     * Determines MIME type for direct video files
     * @param {string} url Video URL
     * @return {string|null} MIME type or null
     */
    getDirectVideoMimeType(url) {
        if (/\.mp4(\?.*)?$/i.test(url)) {
            return 'video/mp4';
        }
        else if (/\.webm(\?.*)?$/i.test(url)) {
            return 'video/webm';
        }
        else {
            return null;
        }
    }

    /**
     * Detects video provider type from URL
     * @param {string} url Video URL
     * @return {string|boolean} CSS class name for video type or false
     */
    detectVideoType(url) {
        if (this.isYouTube(url)) {
            return 'vp-video-type-youtube';
        } 

        else if (this.isVimeo(url)) {
            return 'vp-video-type-vimeo';
        } 

        else if (this.isDirectVideo(url)) {
            const getVideoMimeType = this.getDirectVideoMimeType(url);

            if ( getVideoMimeType == 'video/mp4') {
                return 'vp-video-type-mp4';
            }
            else if ( getVideoMimeType == 'video/webm' ) {
                return 'vp-video-type-webm';
            }
            return null;
        }

        return false;
    }

    /**
     * Extracts YouTube video ID from URL
     * @param {string} url YouTube URL
     * @return {string|null} Video ID or null
     */
    extractYouTubeId(url) {
        const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/|youtube-nocookie\.com\/(?:watch\?v=|embed\/|v\/)|m\.youtube\.com\/watch\?v=)([^&\n?#]+)/);
        return match ? match[1] : null;
    }

    /**
     * Extracts Vimeo video ID from URL
     * @param {string} url Vimeo URL
     * @return {string|null} Video ID or null
     */
    extractVimeoId(url) {
        const match = url.match(/vimeo\.com\/(?:channels\/[^\/]+\/|video\/)?(\d+)(?:\/|\?|$)/);
        return match ? match[1] : null;
    }

    /**
     * Creates YouTube embed HTML
     * @param {string} url YouTube URL
     * @param {Object} data Video settings
     * @param {boolean} autoplay Whether video should autoplay
     * @return {string|boolean|null} Embed HTML or false/null on error
     */
    createYouTubeEmbed(url, data, autoplay) {
        if ( !url ) return null;

        url = url.trim();
        const videoId = this.extractYouTubeId(url);
        if ( !videoId ) return false;

        const youtubeDomain = data.noCookie === true || data.noCookie == 1 ? 'youtube-nocookie.com' : 'youtube.com';

        let embedUrl = `https://www.${youtubeDomain}/embed/${videoId}?`;

        embedUrl += `autoplay=${autoplay ? 1 : 0}`;

        if (data.mute == 1) embedUrl += `&mute=1`;

        return `<iframe src="${embedUrl}" frameborder="0" allow="autoplay; fullscreen"></iframe>`;
    }

    /**
     * Creates Vimeo embed HTML
     * @param {string} url Vimeo URL
     * @param {Object} data Video settings
     * @param {boolean} autoplay Whether video should autoplay
     * @return {string|boolean|null} Embed HTML or false/null on error
     */
    createVimeoEmbed(url, data, autoplay) {
        if ( !url ) return null;

        url = url.trim();
        const videoId = this.extractVimeoId(url);
        if ( !videoId ) return false;

        let embedUrl = `https://player.vimeo.com/video/${videoId}?`;

        embedUrl += `autoplay=${autoplay ? 1 : 0}`;

        if (data.mute == 1) embedUrl += `&muted=1`;
        if (data.noCookie === true || data.noCookie == 1) embedUrl += `&dnt=1`;

        return `<iframe src="${embedUrl}" frameborder="0" allow="autoplay; fullscreen"></iframe>`;
    }

    /**
     * Creates direct video embed HTML
     * @param {string} url Direct video URL
     * @param {Object} data Video settings
     * @param {boolean} autoplay Whether video should autoplay
     * @return {string|boolean|null} Embed HTML or false/null on error
     */
    createDirectVideoEmbed(url, data, autoplay) {
        if ( !url ) return null;

        url = url.trim();

        if ( !this.isDirectVideo(url) ) return false;

        const autoplayAttr = autoplay ? ' autoplay' : '';
        const mutedAttr = data.mute ? ' muted' : '';
        
        const getVideoMimeType = this.getDirectVideoMimeType(url) || 'video/mp4';
        
        return `<video${mutedAttr}${autoplayAttr} controls playsInline controlslist="nodownload" disablePictureInPicture="true">
                    <source src="${url}" type="${getVideoMimeType}">
                    Your browser does not support the video tag.
                 </video>`;
    }

    /**
     * Removes any existing popup from the page
     */
    deleteCurrentPopup() {
        const currentOldPopup = document.querySelector('.YouTubePopUp-Wrap');
        if ( currentOldPopup ) {
            currentOldPopup.remove();
        }
        
        const currentPopup = document.querySelector('.vp-show');
        if ( currentPopup ) {
            currentPopup.classList.remove('vp-show');
            currentPopup.remove();
        }
    }

    /**
     * Creates and displays the popup with video content
     * @param {string} embedHTML HTML for video embed
     * @param {Object} data Video settings
     */
    async createPopup(embedHTML, data) {
        this.deleteCurrentPopup();

        const popup = document.createElement('div');
        const modalType = this.defaults.onPageLoad ? 'vp-modal vp-modal_type_onpageload' : 'vp-modal vp-modal_type_click';
        popup.className = modalType;

        const videoTypeClass = data.videoType ? ' ' + data.videoType : '';
        const iframe = embedHTML ? embedHTML : '';
        
        popup.innerHTML = `
            <div class="vp-container${videoTypeClass}">
                <div class="vp-video" style="width: 800px; height: 450px;">
                    <span class="vp-close"></span>
                    ${iframe}
                </div>
            </div>
        `;

        const popupContainer = popup.querySelector('.vp-container');
        document.body.appendChild(popup);

        await new Promise(resolve => setTimeout(resolve, 30));
        this.deleteCurrentPopup();
        popup.classList.add('vp-show');
        popup.focus();
        data['iframe'] = iframe ? 'appear' : 'no iframe';

        if ( this.defaults.onPageLoad && data.oplCookie ) {
            const cookieName = data.oplCookie["name"];
            const cookieDays = data.oplCookie["days"];
            if ( cookieName && cookieDays ) {
                this.setCookie(cookieName, parseInt(cookieDays));
            }
        }
        
        popup.addEventListener('click', async (e) => {
            if ( data.wrapClose != 1 && data.wrapClose !== true ) {
                if ( popup && e.target === popup ) {
                    await this.closePopup();
                }

                if ( popupContainer && e.target === popupContainer ) {
                    await this.closePopup();
                }
            }
        });
        
        popup.querySelector('.vp-close').addEventListener('click', async () => {
            await this.closePopup();
        });

        // Allows closing popup by ESC key
        document.addEventListener('keydown', async (e) => {
            const isShowPopup = document.querySelector('.vp-show'); // Current Popup
            if ( e.key === 'Escape' && isShowPopup ) {
                await this.closePopup();
            }
        });

        this.debug(data);
    }

    /**
     * Closes and removes the popup
     */
    async closePopup() {
        const popup = document.querySelector('.vp-show');
        if (popup) {
            popup.classList.remove('vp-show');
            await new Promise(resolve => setTimeout(resolve, 500));
            popup.remove();
        }
    }

    /**
     * Outputs debug information to console
     * @param {Object} data Debug data to display
     */
    debug(data) {
        if ( !this.defaults.handleVars ) {
            console.log("videoPopup.js: General options object not found. Will use default options.");
        }

        const debugLogTitle = this.defaults.onPageLoad ? 'Video Popup - On Page Load Modal Debug' : 'Video Popup Modal Debug';

        if ( this.defaults.debug ) {
            const videoURL =  data.url ? data.url.trim() : null;
            if ( !videoURL ) {
                console.log(`=== ${debugLogTitle} - Start ===`);
                console.log('   No video URL! Please enter a video URL to display the debug result.');
                console.log(`=== ${debugLogTitle} - End ===`);
                console.log(''); // an empty line
                return;
            } 

            console.log(`=== ${debugLogTitle} - Start ===`);
            
            console.log('Link Properties:');
            for (const [key, value] of Object.entries(data)) {
                if (key === 'oplCookie' && typeof value === 'object' && value !== null) {
                    console.log(`   ${key}: {`);
                    for (const [cookieKey, cookieValue] of Object.entries(value)) {
                        console.log(`           ${cookieKey}: "${cookieValue}"`);
                    }
                    console.log(`       }`);
                } else {
                    console.log(`   ${key}: ${value}`);
                }
            }

            console.log('Functions Status:');

            console.log('   If YouTube Video:');
                const isYouTubeResult = this.isYouTube(videoURL);
                console.log(`      isYouTube: ${isYouTubeResult ? 'true' : isYouTubeResult}`);
                const youtubeVideoId = this.extractYouTubeId(videoURL);
                console.log(`      extractYouTubeId: ${youtubeVideoId ? `Works Good - ${youtubeVideoId}` : youtubeVideoId}`);
                const youtubeEmbedResult = this.createYouTubeEmbed(videoURL, data, true);
                console.log(`      createYouTubeEmbed: ${youtubeEmbedResult ? 'Works Good' : youtubeEmbedResult}`);    

            console.log('   If Vimeo Video:');
                const isVimeoResult = this.isVimeo(videoURL);
                console.log(`      isVimeo: ${isVimeoResult ? 'true' : isVimeoResult}`);
                const vimeoVideoId = this.extractVimeoId(videoURL);
                console.log(`      extractVimeoId: ${vimeoVideoId ? `Works Good - ${vimeoVideoId}` : vimeoVideoId}`);
                const vimeoEmbedResult = this.createVimeoEmbed(videoURL, data, true);
                console.log(`      createVimeoEmbed: ${vimeoEmbedResult ? 'Works Good' : vimeoEmbedResult}`);

            console.log('   If Direct Video:');
                const isDirectVideoResult = this.isDirectVideo(videoURL);
                console.log(`      isDirectVideo: ${isDirectVideoResult ? 'true' : isDirectVideoResult}`);
                const directEmbedResult = this.createDirectVideoEmbed(videoURL, data, true);
                console.log(`      createDirectVideoEmbed: ${directEmbedResult ? 'Works Good' : directEmbedResult}`);

            const generateEmbedResult = this.generateEmbed(data, true);
            console.log(`   generateEmbed: ${generateEmbedResult ? 'Works Good' : generateEmbedResult}`);

            console.log(`=== ${debugLogTitle} - End ===`);
            console.log(''); // an empty line
        }
    }
}


/**
 * VideoPopupOnPageLoad class extends core popup functionality
 * with specialized behavior for automatic page load display
 * 
 * @extends VideoPopup
 */
class VideoPopupOnPageLoad extends VideoPopup {
    constructor(options = {}) {
        super(options);
    }

    init(){}
    
    async closePopup() {
        await super.closePopup();
        delete this;
    }
}


/**
 * Auto-initialization when DOM is ready
 * Creates VideoPopup instance with default or custom settings
 */
document.addEventListener('DOMContentLoaded', () => {
    if ( typeof theVideoPopupGeneralOptions === 'undefined' ) {
        new VideoPopup( { handleVars: false } );
        return;
    }

    new VideoPopup({
        autoplay: true,
        onPageLoad: false,
        oplCookie: false,
        wrapClose: theVideoPopupGeneralOptions.wrap_close,
        noCookie: theVideoPopupGeneralOptions.no_cookie,
        handleVars: true,
        debug: theVideoPopupGeneralOptions.debug
    });
});