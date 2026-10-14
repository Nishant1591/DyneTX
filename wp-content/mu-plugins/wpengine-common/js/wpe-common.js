var url = window.location.pathname
var filename = url.substring(url.lastIndexOf('/')+1);
var WPE_BACKUP_DISMISS_TIMESTAMP_KEY = 'wpe_backup_dismiss_timestamp';

wpe.updates = {}; // wpe is initialized via wp_localize_script().

// Runtime jQuery
jQuery(document).ready(function($) {

	$('a[href*="wpe-user-portal"]').click(function(e){
		e.preventDefault();
		window.open("https://my.wpengine.com");
	});

	/**
	 * Bind the appropriate buttons and links to the update confirm modal.
	 */
	if( filename == 'update-core.php' && $('form.upgrade').length > 0 && wpe.popup_disabled != 1 ) {
		var $element = $('#upgrade, #upgrade-plugins, #upgrade-themes, #upgrade-plugins-2, #upgrade-themes-2');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmButton( $element );
	} else if( filename == 'plugins.php' && wpe.popup_disabled !=  1 ) {
		var $element = $('#doaction, .update-link');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmButton( $element );
	} else if( filename == 'plugin-install.php' && wpe.popup_disabled != 1 ) {
		var $element = $('a.install-now, a.update-now');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmLink( $element );
	} else if( filename == 'index.php' && wpe.popup_disabled != 1 ) {
		var $element = $('a.install-now');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmLink( $element );
	}
});

/*
 * Class for managing the Deploy from staging response
 */
(function($) {

	/**
	 * Sets the initial state of the element before user interaction with the modal.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmInit = function( $element ) {
		// Initialize buttons and links with a non-confirmed status
		$element.data('confirmChange', false);
	}

	/**
	 * Checks if the dismiss-for-today timestamp is still valid (same day).
	 *
	 * @return {Boolean} True if the dismiss is still valid for today, false otherwise.
	 */
	wpe.updates.isDismissedForToday = function() {
		const dismissTimestamp = localStorage.getItem(WPE_BACKUP_DISMISS_TIMESTAMP_KEY);
		if (dismissTimestamp) {
			const dismissedDate = new Date(parseInt(dismissTimestamp, 10));
			const today = new Date();
			if (dismissedDate.toDateString() === today.toDateString()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Intercepts the click event handler for Buttons and Links.
	 *
	 * @param  {[type]}  $element    jQuery element that stores the state.
	 * @param  {Boolean} actLikeLink Should we resume the click action or redirect to the href attribute?
	 */
	wpe.updates.confirmElement = function( $element, actLikeLink ) {
		// Intercept the click handler
		$element.click(function(e) {
			// Check if dismiss-for-today is still valid for today
			if (wpe.updates.isDismissedForToday()) {
				return;
			}

			if( false === $(this).data('confirmChange') ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}

			wpe.updates.confirmChange( $(this), actLikeLink );
		});
	}

	/**
	 * Intercepts the click event handler for Buttons.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmButton = function( $element ) {
		wpe.updates.confirmElement( $element, false );
	}

	/**
	 * Intercepts the click event handler for Links.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmLink = function( $element ) {
		wpe.updates.confirmElement( $element, true );
	}

	/**
	 * Displays the apprise modal and prompts the user to create a backup.
	 *
	 * @param  {[type]}  $element    The jQuery element being clicked upon.
	 * @param  {Boolean} actLikeLink Should we resume the click action or redirect to the href attribute?
	 */
	wpe.updates.confirmChange = function($element, actLikeLink) {
		// Set false as the default.
		var actLikeLink = typeof actLikeLink !== 'undefined' ?  actLikeLink : false;
		if( $element.data('confirmChange') === false ) {
			const wpeBackupPointsUrl = `https://my.wpengine.com/installs/${wpe.account}/backup_points`;
			wpe.backupReminderModal.show(()=>{
				if( 'function' === typeof wp.updates.installPlugin ) {
					$element.data('confirmChange', true);
					if ( $element[0].className.includes('activate-now') ) {
						window.top.location.href = $element.attr('href');
					} else {
						$element.click();
					}
				} else {
					if( true === actLikeLink ) {
						window.location.href = $element.attr('href');
					} else {
						$element.data('confirmChange', true);
						$element.click();
					}
				}
			}, wpeBackupPointsUrl)
		} else {
			// Reset the button/link state.
			$element.data('confirmChange', false);
		}
	}

})(jQuery);

/**
 * Determines whether query args are present
 *
 * @param  {[type]}  str
 * @return {Boolean}
 */
function has_args(str) {
	var querystring = window.location.href.split('?',2);
	var querystring = querystring[1];
	if ( !querystring ) {
		return false;
	} else {
		if( querystring.indexOf(str) != '-1' )
		{
			return true;
		} else {
			return false;
		}
	}
}

wpe.backupReminderModal = {
	id: 'backup-confirmation-modal',
	backupSkipId: 'modal-backup-skip',
	goToWpeId: 'modal-goto-wpe',
	getOrCreate: function() {
		const $ = jQuery.noConflict();
		let $modal = $('#'+this.id);

		if ($modal.length === 0) {
			const modalHtml = `
				<dialog id="${this.id}">
					<h2>
						Create backup before continuing?
						<button class="backup-confirmation-close-button" onclick="this.closest('dialog').close()" aria-label="Close modal">&#x2715;</button>
					</h2>
					<p>Creating a backup in WP Engine Portal now will protect changes made since the last automated backup.</p>
					<label><input type="checkbox" id="dismiss-for-today" name="dismiss-for-today"> Dismiss for today</label>
					<div class="actions">
					  <button id="${this.backupSkipId}" class="button button-secondary" autofocus>Skip backup</button>
					  <a id="${this.goToWpeId}" class="button button-primary" target="_blank" rel="noopener">Open WP Engine Portal</a>
					</div>
				</dialog>`;

			$('body').append(modalHtml);
			$modal = $('#'+this.id);
		}

		return $modal;
	},
	show: function(onBackupSkip, wpeBackupPointsUrl) {
		if (typeof HTMLDialogElement !== 'function') {
			onBackupSkip();
			return;
		}

		const $ = jQuery.noConflict();
		const $modal = this.getOrCreate();

		$modal.find('#' + this.backupSkipId).off('click').on('click', function() {
			if ($modal.find('#dismiss-for-today').is(':checked')) {
				localStorage.setItem(WPE_BACKUP_DISMISS_TIMESTAMP_KEY, Date.now());
			}
			onBackupSkip();
			$modal[0].close();
		});

		$modal.find('#' + this.goToWpeId).attr('href', wpeBackupPointsUrl);

		$modal.find('#dismiss-for-today')[0].checked = false;

		$modal[0].showModal();
	}
}