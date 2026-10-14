jQuery(document).ready(function($){

	'use strict';

	const POLL_INTERVAL = 5000; // ms
	const $log = $('#bulk-updater-log');

	if (!$log.length || typeof iaffpro_event_log === 'undefined') {
		return;
	}

	let pollTimer    = null;
	let lastRevision = 0;
	let logOffset    = $log.find('p.iaff-event-log-line').length;
	let completed    = false;

	/* ---------------------------------------------
	 * Utilities
	 * ------------------------------------------- */

	function shouldAutoScroll() {
		const el = $log[0];
		return (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 80);
	}

	function scrollToBottomSmooth() {
		const el = $log[0];
		$log.stop(true).animate(
			{ scrollTop: el.scrollHeight - el.clientHeight },
			120
		);
	}

	/* ---------------------------------------------
	 * Polling loop
	 * ------------------------------------------- */

	function checkLogRevision() {

		if (completed) {
			return;
		}

		$.post(iaffpro_event_log.ajaxUrl, {
			action: 'iaffpro_check_event_log_revision',
			security: iaffpro_event_log.nonce
		})
		.done(function (response) {

			if (!response || !response.success) {
				return;
			}

			const revision  = parseInt(response.data.revision, 10) || 0;
			const isDone    = response.data.completed === true;

			let fetchPromise = null;

			// New log entries
			if (revision > lastRevision) {
				lastRevision = revision;
				fetchPromise = fetchLogUpdates(); // returns a promise
			}

			// Bulk updater completed
			if (isDone) {
				// Wait for final logs before completing
				(fetchPromise || $.Deferred().resolve()).done(function () {
					onBulkUpdaterComplete();
				});
			}
		});
	}

	/* ---------------------------------------------
	 * Fetch new log entries
	 * ------------------------------------------- */

	function fetchLogUpdates() {

		return $.post(iaffpro_event_log.ajaxUrl, {
			action: 'iaffpro_fetch_new_event_log',
			security: iaffpro_event_log.nonce,
			offset: logOffset
		})
		.done(function (response) {

			if (!response || !response.success) {
				return;
			}

			appendLogLines(response.data.lines);
			logOffset = response.data.offset;
		});
	}

	/* ---------------------------------------------
	 * Append log lines (line-by-line fade)
	 * ------------------------------------------- */

	function appendLogLines(lines) {

		if (!lines || !lines.length) {
			return;
		}

		let delay = 0;
		const STEP = 120;

		lines.forEach(function (line) {

			const $entry = $('<p />', {
				class: 'iaff-event-log-line',
				html: line
			}).hide();

			$log.append($entry);

			$entry
				.delay(delay)
				.fadeIn(200, function () {
					if (shouldAutoScroll()) {
						scrollToBottomSmooth();
					}
				});

			delay = Math.min(delay + STEP, 3000);
		});
	}

	/* ---------------------------------------------
	 * Polling control
	 * ------------------------------------------- */

	function startPolling() {
		if (pollTimer) {
			return;
		}
		pollTimer = setInterval(checkLogRevision, POLL_INTERVAL);
	}

	function stopPolling() {
		if (!pollTimer) {
			return;
		}
		clearInterval(pollTimer);
		pollTimer = null;
	}

	/* ---------------------------------------------
	 * Completion UI
	 * ------------------------------------------- */

	function onBulkUpdaterComplete() {

		if (completed) {
			return;
		}

		completed = true;
		stopPolling();

		// Final fetch to catch last entries (safe even if none)
		fetchLogUpdates();

		$('.iaff-spinner').removeClass('spinner');

		$log.append(
			'<p class="iaff-event-log-line iaff-green">' +
			'<span class="dashicons dashicons-yes"></span> All done!' +
			'</p>'
		);

		scrollToBottomSmooth();

		$('.iaff_stop_bulk_updater_button')
			.removeClass('button-primary')
			.addClass('button-secondary')
			.prop('disabled', true);
	}

	/* ---------------------------------------------
	 * Reset on new run
	 * ------------------------------------------- */

	$(document).on('click', '.iaff_run_bulk_updater_button', function () {

		if (!completed) {
			return;
		}

		// Reset JS state
		completed    = false;
		lastRevision = 0;
		logOffset    = $log.find('p.iaff-event-log-line').length;

		// Restart polling cleanly
		stopPolling();
		startPolling();
	});

	/* ---------------------------------------------
	 * Init
	 * ------------------------------------------- */

	startPolling();
});