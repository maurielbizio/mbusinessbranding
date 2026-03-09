/* Mauriel Service Directory — Dashboard JS */
/* globals maurielDashData, wp, Chart */
(function () {
	'use strict';

	var cfg = window.maurielDashData || {};
	var restUrl = cfg.rest_url || '/wp-json/';
	var nonce = cfg.nonce || '';
	var listingId = cfg.listing_id || 0;
	var saveTimeout = null;

	/* ═══════════════════════════════════════════
	   INIT
	═══════════════════════════════════════════ */
	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
		initAutoSave();
		initMediaUploads();
		initGalleryManage();
		initVideoRows();
		initHoursEditor();
		initAnalyticsChart();
		initAIDescription();
		initAIReviewResponse();
		initReviewActions();
		initCouponForm();
		initGoogleImport();
		initBillingToggle();
		initPlanUpgrade();
	});

	/* ═══════════════════════════════════════════
	   TABS
	═══════════════════════════════════════════ */
	function initTabs() {
		var btns = document.querySelectorAll('.mauriel-dashboard-tab-btn[data-tab]');
		if (!btns.length) return;

		btns.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var tab = btn.dataset.tab;
				btns.forEach(function (b) { b.classList.remove('active'); });
				btn.classList.add('active');
				document.querySelectorAll('.mauriel-tab-panel').forEach(function (p) {
					p.style.display = p.dataset.tab === tab ? '' : 'none';
				});
				if (tab === 'analytics') loadAnalytics(cfg.analytics_days || 30);
			});
		});
	}

	/* ═══════════════════════════════════════════
	   AUTO SAVE INDICATOR
	═══════════════════════════════════════════ */
	function initAutoSave() {
		var form = document.getElementById('mauriel-listing-form');
		if (!form) return;

		var indicator = document.getElementById('mauriel-save-indicator');

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			clearTimeout(saveTimeout);
			saveListing(form, indicator);
		});
	}

	function saveListing(form, indicator) {
		if (indicator) { indicator.textContent = 'Saving…'; indicator.className = 'mauriel-save-indicator saving'; }

		var data = new FormData(form);
		data.append('_wpnonce', cfg.form_nonce || '');
		data.append('listing_id', listingId);

		fetch(cfg.rest_url + 'mauriel/v1/listings/' + listingId, {
			method: 'POST',
			headers: { 'X-WP-Nonce': nonce },
			body: data
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (indicator) {
				indicator.textContent = res.success ? 'Saved!' : 'Error saving';
				indicator.className = 'mauriel-save-indicator ' + (res.success ? 'saved' : 'error');
				setTimeout(function () { indicator.textContent = ''; indicator.className = 'mauriel-save-indicator'; }, 3000);
			}
			if (res.success && res.data && res.data.redirect) {
				window.location.href = res.data.redirect;
			}
		})
		.catch(function () {
			if (indicator) { indicator.textContent = 'Save failed'; indicator.className = 'mauriel-save-indicator error'; }
		});
	}

	/* ═══════════════════════════════════════════
	   WP MEDIA UPLOADER — LOGO / COVER
	═══════════════════════════════════════════ */
	function initMediaUploads() {
		initMediaButton('mauriel-upload-logo-btn', 'mauriel-logo-id', 'mauriel-logo-preview-img', 'image');
		initMediaButton('mauriel-upload-cover-btn', 'mauriel-cover-id', 'mauriel-cover-preview-img', 'image');
		initRemoveMedia('mauriel-remove-logo-btn', 'mauriel-logo-id', 'mauriel-logo-preview-img');
		initRemoveMedia('mauriel-remove-cover-btn', 'mauriel-cover-id', 'mauriel-cover-preview-img');
	}

	function initMediaButton(btnId, inputId, previewId, mediaType) {
		var btn = document.getElementById(btnId);
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (typeof wp === 'undefined' || !wp.media) return;
			var frame = wp.media({
				title: 'Select Image',
				button: { text: 'Use this image' },
				multiple: false,
				library: { type: mediaType }
			});
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				var input = document.getElementById(inputId);
				var preview = document.getElementById(previewId);
				if (input) input.value = att.id;
				if (preview) { preview.src = att.url; preview.style.display = ''; }
			});
			frame.open();
		});
	}

	function initRemoveMedia(btnId, inputId, previewId) {
		var btn = document.getElementById(btnId);
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var input = document.getElementById(inputId);
			var preview = document.getElementById(previewId);
			if (input) input.value = '';
			if (preview) { preview.src = ''; preview.style.display = 'none'; }
		});
	}

	/* ═══════════════════════════════════════════
	   GALLERY
	═══════════════════════════════════════════ */
	function initGalleryManage() {
		var addBtn = document.getElementById('mauriel-add-gallery-btn');
		if (!addBtn) return;

		addBtn.addEventListener('click', function (e) {
			e.preventDefault();
			if (typeof wp === 'undefined' || !wp.media) return;
			var frame = wp.media({
				title: 'Select Gallery Images',
				button: { text: 'Add to Gallery' },
				multiple: 'add',
				library: { type: 'image' }
			});
			frame.on('select', function () {
				var selection = frame.state().get('selection').toArray();
				selection.forEach(function (att) {
					addGalleryThumb(att.toJSON());
				});
				syncGalleryInput();
			});
			frame.open();
		});

		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-remove-gallery-img')) return;
			var thumb = e.target.closest('.mauriel-gallery-thumb');
			if (thumb) { thumb.remove(); syncGalleryInput(); }
		});
	}

	function addGalleryThumb(att) {
		var grid = document.getElementById('mauriel-gallery-manage');
		if (!grid) return;
		var thumb = document.createElement('div');
		thumb.className = 'mauriel-gallery-thumb';
		thumb.dataset.id = att.id;
		thumb.innerHTML = '<img src="' + att.url + '" alt="">' +
			'<button type="button" class="mauriel-remove-gallery-img" title="Remove">&times;</button>';
		grid.appendChild(thumb);
	}

	function syncGalleryInput() {
		var input = document.getElementById('mauriel-gallery-ids');
		if (!input) return;
		var ids = Array.from(document.querySelectorAll('.mauriel-gallery-thumb[data-id]')).map(function (el) { return el.dataset.id; });
		input.value = JSON.stringify(ids);
	}

	/* ═══════════════════════════════════════════
	   VIDEO ROWS
	═══════════════════════════════════════════ */
	function initVideoRows() {
		var addBtn = document.getElementById('mauriel-add-video-btn');
		if (!addBtn) return;
		addBtn.addEventListener('click', function (e) {
			e.preventDefault();
			var input = document.getElementById('mauriel-new-video-url');
			if (!input || !input.value.trim()) return;
			addVideoRow(input.value.trim());
			input.value = '';
			syncVideoInput();
		});

		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-remove-video-btn')) return;
			var row = e.target.closest('.mauriel-video-row');
			if (row) { row.remove(); syncVideoInput(); }
		});
	}

	function addVideoRow(url) {
		var list = document.getElementById('mauriel-video-list');
		if (!list) return;
		var row = document.createElement('div');
		row.className = 'mauriel-video-row';
		row.dataset.url = url;
		row.innerHTML = '<span>' + escHtml(url) + '</span><button type="button" class="button-link mauriel-remove-video-btn">Remove</button>';
		list.appendChild(row);
	}

	function syncVideoInput() {
		var input = document.getElementById('mauriel-video-urls');
		if (!input) return;
		var urls = Array.from(document.querySelectorAll('.mauriel-video-row[data-url]')).map(function (el) { return el.dataset.url; });
		input.value = JSON.stringify(urls);
	}

	/* ═══════════════════════════════════════════
	   HOURS EDITOR
	═══════════════════════════════════════════ */
	function initHoursEditor() {
		var setWeekdays = document.getElementById('mauriel-hours-set-weekdays');
		var openAll = document.getElementById('mauriel-hours-open-all');
		var closeAll = document.getElementById('mauriel-hours-close-all');

		if (setWeekdays) {
			setWeekdays.addEventListener('click', function (e) {
				e.preventDefault();
				// Mon-Fri (day index 1-5)
				document.querySelectorAll('.mauriel-hours-row').forEach(function (row) {
					var day = parseInt(row.dataset.day, 10);
					var openChk = row.querySelector('.mauriel-day-open');
					var openTime = row.querySelector('.mauriel-open-time');
					var closeTime = row.querySelector('.mauriel-close-time');
					if (day >= 1 && day <= 5) {
						if (openChk) openChk.checked = true;
						if (openTime) openTime.value = '09:00';
						if (closeTime) closeTime.value = '17:00';
						toggleHoursRow(row, true);
					} else {
						if (openChk) openChk.checked = false;
						toggleHoursRow(row, false);
					}
				});
			});
		}

		if (openAll) {
			openAll.addEventListener('click', function (e) {
				e.preventDefault();
				document.querySelectorAll('.mauriel-hours-row').forEach(function (row) {
					var openChk = row.querySelector('.mauriel-day-open');
					var openTime = row.querySelector('.mauriel-open-time');
					var closeTime = row.querySelector('.mauriel-close-time');
					if (openChk) openChk.checked = true;
					if (openTime && !openTime.value) openTime.value = '09:00';
					if (closeTime && !closeTime.value) closeTime.value = '17:00';
					toggleHoursRow(row, true);
				});
			});
		}

		if (closeAll) {
			closeAll.addEventListener('click', function (e) {
				e.preventDefault();
				document.querySelectorAll('.mauriel-hours-row').forEach(function (row) {
					var openChk = row.querySelector('.mauriel-day-open');
					if (openChk) openChk.checked = false;
					toggleHoursRow(row, false);
				});
			});
		}

		document.addEventListener('change', function (e) {
			if (!e.target.matches('.mauriel-day-open')) return;
			var row = e.target.closest('.mauriel-hours-row');
			if (row) toggleHoursRow(row, e.target.checked);
		});
	}

	function toggleHoursRow(row, isOpen) {
		var timeInputs = row.querySelectorAll('.mauriel-open-time, .mauriel-close-time, .mauriel-24hr');
		timeInputs.forEach(function (el) { el.disabled = !isOpen; });
	}

	/* ═══════════════════════════════════════════
	   ANALYTICS CHART
	═══════════════════════════════════════════ */
	var analyticsChart = null;

	function initAnalyticsChart() {
		var daysFilter = document.querySelectorAll('.mauriel-days-filter .button');
		daysFilter.forEach(function (btn) {
			btn.addEventListener('click', function () {
				daysFilter.forEach(function (b) { b.classList.remove('button-primary'); });
				btn.classList.add('button-primary');
				var days = parseInt(btn.dataset.days, 10) || 30;
				loadAnalytics(days);
			});
		});

		if (document.getElementById('mauriel-analytics-chart')) {
			loadAnalytics(cfg.analytics_days || 30);
		}
	}

	function loadAnalytics(days) {
		if (!listingId) return;
		fetch(restUrl + 'mauriel/v1/analytics/' + listingId + '?days=' + days, {
			headers: { 'X-WP-Nonce': nonce }
		})
		.then(function (r) { return r.json(); })
		.then(function (data) {
			renderAnalyticsCards(data.summary || {});
			renderAnalyticsChart(data.trend || []);
		})
		.catch(function () {});
	}

	function renderAnalyticsCards(summary) {
		var map = { views: 'mauriel-stat-views', leads: 'mauriel-stat-leads', phone_clicks: 'mauriel-stat-phone', website_clicks: 'mauriel-stat-website', impressions: 'mauriel-stat-impressions', reviews: 'mauriel-stat-reviews' };
		Object.keys(map).forEach(function (key) {
			var el = document.getElementById(map[key]);
			if (el) el.textContent = (summary[key] || 0).toLocaleString();
		});
	}

	function renderAnalyticsChart(trend) {
		var canvas = document.getElementById('mauriel-analytics-chart');
		if (!canvas || typeof Chart === 'undefined') return;

		var labels = trend.map(function (d) { return d.date; });
		var views = trend.map(function (d) { return d.views || 0; });
		var leads = trend.map(function (d) { return d.leads || 0; });

		if (analyticsChart) analyticsChart.destroy();
		analyticsChart = new Chart(canvas, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{ label: 'Views', data: views, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.08)', tension: 0.3, fill: true },
					{ label: 'Leads', data: leads, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.3, fill: true }
				]
			},
			options: {
				responsive: true,
				plugins: { legend: { position: 'top' } },
				scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
			}
		});
	}

	/* ═══════════════════════════════════════════
	   AI — DESCRIPTION GENERATOR
	═══════════════════════════════════════════ */
	function initAIDescription() {
		var btn = document.getElementById('mauriel-ai-description-btn');
		if (!btn) return;

		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var originalText = btn.textContent;
			btn.textContent = 'Generating…';
			btn.disabled = true;

			var name = val('_mauriel_business_name') || val('post_title') || '';
			var category = val('_mauriel_category') || '';
			var city = val('_mauriel_city') || '';
			var tagline = val('_mauriel_tagline') || '';

			fetch(restUrl + 'mauriel/v1/ai/generate-description', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify({ business_name: name, category: category, city: city, tagline: tagline, listing_id: listingId })
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				btn.textContent = originalText;
				btn.disabled = false;
				if (res.success && res.data && res.data.content) {
					var descEl = document.getElementById('mauriel-description') || document.querySelector('[name="_mauriel_description"]');
					if (descEl) {
						descEl.value = res.data.content;
						if (window.tinymce) {
							var editor = tinymce.get(descEl.id);
							if (editor) editor.setContent(res.data.content);
						}
					}
				} else {
					alert(res.message || 'AI generation failed. Check your OpenAI API key in settings.');
				}
			})
			.catch(function () {
				btn.textContent = originalText;
				btn.disabled = false;
				alert('Network error during AI generation.');
			});
		});
	}

	/* ═══════════════════════════════════════════
	   AI — REVIEW RESPONSE SUGGESTER
	═══════════════════════════════════════════ */
	function initAIReviewResponse() {
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-ai-respond-btn')) return;
			var btn = e.target;
			var card = btn.closest('.mauriel-review-manage-card');
			if (!card) return;
			var reviewText = card.dataset.reviewText || '';
			var rating = card.dataset.rating || 5;
			var textarea = card.querySelector('.mauriel-response-textarea');
			var originalText = btn.textContent;
			btn.textContent = 'Generating…';
			btn.disabled = true;

			fetch(restUrl + 'mauriel/v1/ai/suggest-response', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify({ review_text: reviewText, rating: rating, listing_id: listingId })
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				btn.textContent = originalText;
				btn.disabled = false;
				if (res.success && res.data && res.data.content) {
					if (textarea) {
						textarea.value = res.data.content;
						var responseForm = card.querySelector('.mauriel-response-form');
						if (responseForm) responseForm.style.display = '';
					}
				} else {
					alert(res.message || 'AI suggestion failed.');
				}
			})
			.catch(function () {
				btn.textContent = originalText;
				btn.disabled = false;
			});
		});
	}

	/* ═══════════════════════════════════════════
	   REVIEW ACTIONS (Moderate / Respond)
	═══════════════════════════════════════════ */
	function initReviewActions() {
		// Show/hide response form
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-respond-toggle-btn')) return;
			var card = e.target.closest('.mauriel-review-manage-card');
			if (!card) return;
			var form = card.querySelector('.mauriel-response-form');
			if (form) form.style.display = form.style.display === 'none' ? '' : 'none';
		});

		// Submit owner response
		document.addEventListener('submit', function (e) {
			if (!e.target.matches('.mauriel-response-form')) return;
			e.preventDefault();
			var form = e.target;
			var card = form.closest('.mauriel-review-manage-card');
			var reviewId = card ? card.dataset.reviewId : null;
			var textarea = form.querySelector('.mauriel-response-textarea');
			if (!reviewId || !textarea) return;

			fetch(restUrl + 'mauriel/v1/reviews/' + reviewId + '/respond', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify({ response: textarea.value, listing_id: listingId })
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					form.style.display = 'none';
					var existingResp = card.querySelector('.mauriel-existing-response p');
					var existingWrap = card.querySelector('.mauriel-existing-response');
					if (existingResp) existingResp.textContent = textarea.value;
					else if (existingWrap) existingWrap.style.display = '';
				} else {
					alert(res.message || 'Failed to save response.');
				}
			})
			.catch(function () {});
		});

		// Trash review
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-trash-review-btn')) return;
			if (!confirm('Delete this review?')) return;
			var card = e.target.closest('.mauriel-review-manage-card');
			var reviewId = card ? card.dataset.reviewId : null;
			if (!reviewId) return;

			fetch(restUrl + 'mauriel/v1/reviews/' + reviewId, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
				body: JSON.stringify({ listing_id: listingId })
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success && card) card.remove();
				else alert(res.message || 'Delete failed.');
			})
			.catch(function () {});
		});
	}

	/* ═══════════════════════════════════════════
	   COUPONS
	═══════════════════════════════════════════ */
	function initCouponForm() {
		var form = document.getElementById('mauriel-coupon-add-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var data = Object.fromEntries(new FormData(form));
			data.listing_id = listingId;

			fetch(restUrl + 'mauriel/v1/coupons', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify(data)
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					window.location.reload();
				} else {
					alert(res.message || 'Failed to create coupon.');
				}
			})
			.catch(function () {});
		});

		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-delete-coupon-btn')) return;
			if (!confirm('Delete this coupon?')) return;
			var couponId = e.target.dataset.couponId;
			if (!couponId) return;

			fetch(restUrl + 'mauriel/v1/coupons/' + couponId + '?listing_id=' + listingId, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': nonce }
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					var card = e.target.closest('.mauriel-coupon-manage-card');
					if (card) card.remove();
				} else {
					alert(res.message || 'Delete failed.');
				}
			})
			.catch(function () {});
		});
	}

	/* ═══════════════════════════════════════════
	   GOOGLE PLACES IMPORT
	═══════════════════════════════════════════ */
	function initGoogleImport() {
		var btn = document.getElementById('mauriel-google-import-btn');
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var placeIdInput = document.getElementById('mauriel-place-id-input');
			var placeId = placeIdInput ? placeIdInput.value.trim() : '';
			if (!placeId) { alert('Enter a Google Place ID first.'); return; }

			var result = document.getElementById('mauriel-import-result');
			btn.disabled = true;
			btn.textContent = 'Importing…';

			fetch(restUrl + 'mauriel/v1/reviews/import-google', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify({ listing_id: listingId, place_id: placeId })
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				btn.disabled = false;
				btn.textContent = 'Import Google Reviews';
				if (result) {
					result.textContent = res.success ? (res.data.imported + ' reviews imported.') : (res.message || 'Import failed.');
					result.style.color = res.success ? '#065f46' : '#7f1d1d';
				}
			})
			.catch(function () {
				btn.disabled = false;
				btn.textContent = 'Import Google Reviews';
			});
		});
	}

	/* ═══════════════════════════════════════════
	   BILLING TOGGLE (Monthly / Yearly)
	═══════════════════════════════════════════ */
	function initBillingToggle() {
		var toggleBtns = document.querySelectorAll('.mauriel-billing-toggle .button');
		toggleBtns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				toggleBtns.forEach(function (b) { b.classList.remove('button-primary'); });
				btn.classList.add('button-primary');
				var interval = btn.dataset.interval;
				document.querySelectorAll('.mauriel-plan-price-wrap').forEach(function (wrap) {
					var monthly = wrap.querySelector('.mauriel-price-monthly');
					var yearly = wrap.querySelector('.mauriel-price-yearly');
					if (monthly) monthly.style.display = interval === 'monthly' ? '' : 'none';
					if (yearly) yearly.style.display = interval === 'yearly' ? '' : 'none';
				});
				document.querySelectorAll('.mauriel-upgrade-btn').forEach(function (upgradeBtn) {
					var url = interval === 'yearly' ? upgradeBtn.dataset.yearlyUrl : upgradeBtn.dataset.monthlyUrl;
					if (url) upgradeBtn.href = url;
					upgradeBtn.dataset.interval = interval;
				});
			});
		});
	}

	/* ═══════════════════════════════════════════
	   PLAN UPGRADE
	═══════════════════════════════════════════ */
	function initPlanUpgrade() {
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-upgrade-btn')) return;
			// Standard link navigation handles it; just add loading state
			var btn = e.target;
			if (btn.href && btn.href !== '#') {
				btn.textContent = 'Redirecting…';
				btn.style.pointerEvents = 'none';
			}
		});

		var portalBtn = document.getElementById('mauriel-billing-portal-btn');
		if (portalBtn) {
			portalBtn.addEventListener('click', function (e) {
				e.preventDefault();
				portalBtn.textContent = 'Loading…';
				portalBtn.disabled = true;

				fetch(restUrl + 'mauriel/v1/stripe/portal', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
					body: JSON.stringify({ listing_id: listingId })
				})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res.success && res.data && res.data.url) {
						window.location.href = res.data.url;
					} else {
						alert(res.message || 'Could not open billing portal.');
						portalBtn.textContent = 'Manage Billing';
						portalBtn.disabled = false;
					}
				})
				.catch(function () {
					portalBtn.textContent = 'Manage Billing';
					portalBtn.disabled = false;
				});
			});
		}
	}

	/* ═══════════════════════════════════════════
	   HELPERS
	═══════════════════════════════════════════ */
	function val(nameOrId) {
		var el = document.getElementById(nameOrId) || document.querySelector('[name="' + nameOrId + '"]');
		return el ? el.value.trim() : '';
	}

	function escHtml(str) {
		if (!str) return '';
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
})();
