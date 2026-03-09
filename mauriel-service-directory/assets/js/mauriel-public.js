/* Mauriel Service Directory — Public JS */
/* globals maurielDirectoryData, maurielPublicData, MaurielMaps */
(function () {
	'use strict';

	var cfg = window.maurielDirectoryData || {};
	var pub = window.maurielPublicData || {};
	var searchTimeout = null;
	var currentPage = 1;
	var currentParams = {};
	var currentView = 'grid';

	/* ═══════════════════════════════════════════
	   INIT
	═══════════════════════════════════════════ */
	document.addEventListener('DOMContentLoaded', function () {
		initSearch();
		initViewToggle();
		initSingleListing();
		initStarSelector();
		initLeadForms();
		initReviewForm();
		initGalleryLightbox();
		initCouponReveal();
		initAnalytics();
		loadFromURL();
	});

	/* ═══════════════════════════════════════════
	   SEARCH
	═══════════════════════════════════════════ */
	function initSearch() {
		var form = document.getElementById('mauriel-search-form');
		if (!form) return;

		var inputs = form.querySelectorAll('input, select');
		inputs.forEach(function (el) {
			el.addEventListener('change', function () { debounceSearch(); });
			if (el.tagName === 'INPUT' && el.type === 'text') {
				el.addEventListener('input', function () { debounceSearch(); });
			}
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			clearTimeout(searchTimeout);
			doSearch(1);
		});

		document.addEventListener('click', function (e) {
			if (e.target.matches('.mauriel-page-btn')) {
				e.preventDefault();
				var page = parseInt(e.target.dataset.page, 10);
				if (page) doSearch(page);
			}
		});
	}

	function debounceSearch() {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(function () { doSearch(1); }, 400);
	}

	function collectParams() {
		var form = document.getElementById('mauriel-search-form');
		if (!form) return {};
		var data = new FormData(form);
		var params = {};
		data.forEach(function (val, key) {
			if (val !== '') params[key] = val;
		});
		return params;
	}

	function doSearch(page) {
		currentPage = page || 1;
		currentParams = collectParams();
		currentParams.page = currentPage;
		currentParams.view = currentView;

		pushState(currentParams);
		showSpinner(true);

		fetch(cfg.rest_url + 'mauriel/v1/search', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify(currentParams)
		})
		.then(function (r) { return r.json(); })
		.then(function (data) {
			showSpinner(false);
			if (data.listings) {
				renderListings(data.listings, data.total, data.pages);
				if (currentView === 'map' && typeof MaurielMaps !== 'undefined') {
					MaurielMaps.updateMapMarkers(data.map_markers || []);
				}
			} else {
				renderEmpty();
			}
		})
		.catch(function () {
			showSpinner(false);
			renderEmpty();
		});
	}

	/* ═══════════════════════════════════════════
	   RENDER
	═══════════════════════════════════════════ */
	function renderListings(listings, total, pages) {
		var grid = document.getElementById('mauriel-listings-grid');
		var countEl = document.getElementById('mauriel-results-count');
		if (!grid) return;

		if (countEl) countEl.textContent = total + ' result' + (total !== 1 ? 's' : '');

		if (!listings || !listings.length) {
			renderEmpty();
			return;
		}

		grid.innerHTML = listings.map(renderListingCard).join('');
		renderPagination(pages);
	}

	function renderListingCard(listing) {
		var stars = buildStarHtml(listing.avg_rating);
		var openBadge = listing.is_open_now === true
			? '<span class="mauriel-open-badge">Open Now</span>'
			: listing.is_open_now === false
				? '<span class="mauriel-closed-badge">Closed</span>'
				: '';

		var featuredBadge = listing.is_featured ? '<span class="mauriel-featured-badge">Featured</span>' : '';

		var logo = listing.logo_url
			? '<img src="' + escHtml(listing.logo_url) + '" class="mauriel-card-logo" alt="' + escHtml(listing.name) + ' logo" loading="lazy">'
			: '<div class="mauriel-card-logo mauriel-logo-placeholder"></div>';

		return '<div class="mauriel-listing-card' + (listing.is_featured ? ' mauriel-card-featured' : '') + '">' +
			'<a href="' + escHtml(listing.url) + '" class="mauriel-card-link">' +
			(listing.cover_url ? '<div class="mauriel-card-cover" style="background-image:url(' + escHtml(listing.cover_url) + ')"></div>' : '') +
			'</a>' +
			'<div class="mauriel-card-body">' +
			'<div class="mauriel-card-logo-wrap">' + logo + '</div>' +
			'<div class="mauriel-card-info">' +
			featuredBadge + openBadge +
			'<h3 class="mauriel-card-title"><a href="' + escHtml(listing.url) + '">' + escHtml(listing.name) + '</a></h3>' +
			(listing.category ? '<div class="mauriel-card-category">' + escHtml(listing.category) + '</div>' : '') +
			(listing.avg_rating > 0 ? '<div class="mauriel-card-stars">' + stars + ' <span class="mauriel-card-review-count">(' + (listing.review_count || 0) + ')</span></div>' : '') +
			(listing.city ? '<div class="mauriel-card-location">' + escHtml(listing.city) + (listing.state ? ', ' + escHtml(listing.state) : '') + '</div>' : '') +
			(listing.phone ? '<div class="mauriel-card-phone"><a href="tel:' + escHtml(listing.phone) + '" class="mauriel-phone-link" data-id="' + listing.id + '">' + escHtml(listing.phone) + '</a></div>' : '') +
			(listing.excerpt ? '<p class="mauriel-card-excerpt">' + escHtml(listing.excerpt) + '</p>' : '') +
			'<a href="' + escHtml(listing.url) + '" class="mauriel-btn mauriel-btn-sm">View Details</a>' +
			'</div></div></div>';
	}

	function renderPagination(pages) {
		var el = document.getElementById('mauriel-pagination');
		if (!el) return;
		if (!pages || pages <= 1) { el.innerHTML = ''; return; }

		var html = '<div class="mauriel-pagination">';
		if (currentPage > 1) html += '<button class="mauriel-page-btn" data-page="' + (currentPage - 1) + '">&laquo; Prev</button>';
		for (var i = 1; i <= pages; i++) {
			html += '<button class="mauriel-page-btn' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
		}
		if (currentPage < pages) html += '<button class="mauriel-page-btn" data-page="' + (currentPage + 1) + '">Next &raquo;</button>';
		html += '</div>';
		el.innerHTML = html;
	}

	function renderEmpty() {
		var grid = document.getElementById('mauriel-listings-grid');
		var countEl = document.getElementById('mauriel-results-count');
		if (countEl) countEl.textContent = '0 results';
		if (grid) grid.innerHTML = '<div class="mauriel-empty-state"><div class="mauriel-empty-icon">🔍</div><h3>No listings found</h3><p>Try adjusting your search or expanding the radius.</p></div>';
		var pg = document.getElementById('mauriel-pagination');
		if (pg) pg.innerHTML = '';
	}

	/* ═══════════════════════════════════════════
	   VIEW TOGGLE
	═══════════════════════════════════════════ */
	function initViewToggle() {
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-view-btn')) return;
			var view = e.target.dataset.view;
			if (!view) return;
			currentView = view;
			document.querySelectorAll('.mauriel-view-btn').forEach(function (b) { b.classList.remove('active'); });
			e.target.classList.add('active');
			document.querySelectorAll('.mauriel-view-pane').forEach(function (p) { p.style.display = 'none'; });
			var pane = document.getElementById('mauriel-view-' + view);
			if (pane) pane.style.display = '';
			if (view === 'map' && typeof MaurielMaps !== 'undefined') {
				MaurielMaps.updateMapMarkers(window.maurielMapMarkers || []);
			}
		});
	}

	/* ═══════════════════════════════════════════
	   SINGLE LISTING — TABS
	═══════════════════════════════════════════ */
	function initSingleListing() {
		var tabBtns = document.querySelectorAll('.mauriel-listing-tab-btn');
		if (!tabBtns.length) return;

		tabBtns.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var target = btn.dataset.tab;
				tabBtns.forEach(function (b) { b.classList.remove('active'); });
				btn.classList.add('active');
				document.querySelectorAll('.mauriel-listing-tab-panel').forEach(function (p) {
					p.style.display = p.dataset.tab === target ? '' : 'none';
				});
				if (target === 'location' && typeof MaurielMaps !== 'undefined') {
					MaurielMaps.initSingleMap();
				}
			});
		});

		// Phone / website click tracking
		document.addEventListener('click', function (e) {
			var link = e.target.closest('.mauriel-phone-cta, .mauriel-website-cta, .mauriel-phone-link');
			if (!link) return;
			var listingId = link.dataset.id || (pub.listing_id || null);
			if (!listingId) return;
			var type = link.classList.contains('mauriel-website-cta') ? 'website_click' : 'phone_click';
			recordAnalytics(listingId, type);
		});
	}

	/* ═══════════════════════════════════════════
	   STAR SELECTOR
	═══════════════════════════════════════════ */
	function initStarSelector() {
		var selectors = document.querySelectorAll('.mauriel-star-selector');
		selectors.forEach(function (sel) {
			var stars = sel.querySelectorAll('.mauriel-star-btn');
			var input = sel.nextElementSibling;
			stars.forEach(function (star, idx) {
				star.addEventListener('mouseenter', function () { highlightStars(stars, idx); });
				star.addEventListener('mouseleave', function () {
					var val = parseInt(input && input.value, 10) || 0;
					highlightStars(stars, val - 1);
				});
				star.addEventListener('click', function () {
					var val = idx + 1;
					if (input) input.value = val;
					highlightStars(stars, idx);
					stars.forEach(function (s) { s.dataset.selected = '0'; });
					stars[idx].dataset.selected = '1';
				});
			});
		});
	}

	function highlightStars(stars, upTo) {
		stars.forEach(function (s, i) { s.classList.toggle('active', i <= upTo); });
	}

	/* ═══════════════════════════════════════════
	   LEAD FORMS
	═══════════════════════════════════════════ */
	function initLeadForms() {
		document.addEventListener('submit', function (e) {
			if (!e.target.matches('.mauriel-lead-form')) return;
			e.preventDefault();
			var form = e.target;
			var btn = form.querySelector('[type=submit]');
			var notice = form.querySelector('.mauriel-form-notice');
			var data = formToJSON(form);

			if (btn) btn.disabled = true;

			fetch(cfg.rest_url + 'mauriel/v1/leads', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify(data)
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (btn) btn.disabled = false;
				if (res.success) {
					showNotice(notice, 'Message sent! We\'ll be in touch soon.', 'success');
					form.reset();
				} else {
					showNotice(notice, res.message || 'Something went wrong. Please try again.', 'error');
				}
			})
			.catch(function () {
				if (btn) btn.disabled = false;
				showNotice(notice, 'Network error. Please try again.', 'error');
			});
		});

		// Lead form tabs (Contact / Quote)
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-lead-tab-btn')) return;
			var tab = e.target.dataset.tab;
			document.querySelectorAll('.mauriel-lead-tab-btn').forEach(function (b) { b.classList.remove('active'); });
			e.target.classList.add('active');
			document.querySelectorAll('.mauriel-lead-tab-panel').forEach(function (p) {
				p.style.display = p.dataset.tab === tab ? '' : 'none';
			});
		});
	}

	/* ═══════════════════════════════════════════
	   REVIEW FORM
	═══════════════════════════════════════════ */
	function initReviewForm() {
		var form = document.getElementById('mauriel-review-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var btn = form.querySelector('[type=submit]');
			var notice = form.querySelector('.mauriel-form-notice');
			var data = formToJSON(form);

			if (!data.rating || parseInt(data.rating, 10) < 1) {
				showNotice(notice, 'Please select a star rating.', 'error');
				return;
			}

			if (btn) btn.disabled = true;

			fetch(cfg.rest_url + 'mauriel/v1/reviews', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify(data)
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (btn) btn.disabled = false;
				if (res.success) {
					showNotice(notice, 'Thank you for your review! It will appear after moderation.', 'success');
					form.reset();
					document.querySelectorAll('.mauriel-star-btn').forEach(function (s) { s.classList.remove('active'); });
				} else {
					showNotice(notice, res.message || 'Something went wrong.', 'error');
				}
			})
			.catch(function () {
				if (btn) btn.disabled = false;
				showNotice(notice, 'Network error. Please try again.', 'error');
			});
		});
	}

	/* ═══════════════════════════════════════════
	   GALLERY LIGHTBOX
	═══════════════════════════════════════════ */
	function initGalleryLightbox() {
		var overlay = document.getElementById('mauriel-lightbox');
		if (!overlay) return;
		var img = overlay.querySelector('.mauriel-lightbox-img');
		var closeBtn = overlay.querySelector('.mauriel-lightbox-close');
		var prevBtn = overlay.querySelector('.mauriel-lightbox-prev');
		var nextBtn = overlay.querySelector('.mauriel-lightbox-next');
		var galleryImgs = [];
		var current = 0;

		function buildGallery() {
			galleryImgs = Array.from(document.querySelectorAll('.mauriel-gallery-item img'));
		}

		function open(idx) {
			if (!galleryImgs[idx]) return;
			current = idx;
			img.src = galleryImgs[idx].src;
			overlay.classList.add('active');
			overlay.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}

		function close() {
			overlay.classList.remove('active');
			overlay.style.display = 'none';
			document.body.style.overflow = '';
			img.src = '';
		}

		document.addEventListener('click', function (e) {
			var item = e.target.closest('.mauriel-gallery-item');
			if (item) {
				buildGallery();
				var idx = galleryImgs.indexOf(item.querySelector('img'));
				open(idx >= 0 ? idx : 0);
			}
		});

		if (closeBtn) closeBtn.addEventListener('click', close);
		if (prevBtn) prevBtn.addEventListener('click', function () { open((current - 1 + galleryImgs.length) % galleryImgs.length); });
		if (nextBtn) nextBtn.addEventListener('click', function () { open((current + 1) % galleryImgs.length); });

		overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
		document.addEventListener('keydown', function (e) {
			if (!overlay.classList.contains('active')) return;
			if (e.key === 'Escape') close();
			if (e.key === 'ArrowLeft') prevBtn && prevBtn.click();
			if (e.key === 'ArrowRight') nextBtn && nextBtn.click();
		});
	}

	/* ═══════════════════════════════════════════
	   COUPON REVEAL
	═══════════════════════════════════════════ */
	function initCouponReveal() {
		document.addEventListener('click', function (e) {
			if (!e.target.matches('.mauriel-reveal-code-btn')) return;
			var btn = e.target;
			var card = btn.closest('.mauriel-coupon-card');
			if (!card) return;
			var codeEl = card.querySelector('.mauriel-coupon-code');
			if (codeEl) {
				codeEl.style.display = 'block';
				btn.textContent = 'Code: ' + codeEl.textContent.trim();
				btn.classList.add('mauriel-btn-outline');
			}
		});
	}

	/* ═══════════════════════════════════════════
	   ANALYTICS
	═══════════════════════════════════════════ */
	function initAnalytics() {
		if (!pub.listing_id) return;
		// view is recorded server-side via SEO class; record via REST for JS-rendered pages
		if (pub.record_view) {
			recordAnalytics(pub.listing_id, 'view');
		}
	}

	function recordAnalytics(listingId, eventType) {
		if (!cfg.rest_url) return;
		fetch(cfg.rest_url + 'mauriel/v1/analytics/record', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify({ listing_id: listingId, event_type: eventType })
		}).catch(function () {});
	}

	/* ═══════════════════════════════════════════
	   URL PARAMS
	═══════════════════════════════════════════ */
	function loadFromURL() {
		var params = new URLSearchParams(window.location.search);
		if (!params.toString()) return;
		var form = document.getElementById('mauriel-search-form');
		if (!form) return;
		params.forEach(function (val, key) {
			var el = form.querySelector('[name="' + key + '"]');
			if (el) el.value = val;
		});
		doSearch(parseInt(params.get('page'), 10) || 1);
	}

	function pushState(params) {
		if (!window.history || !window.history.pushState) return;
		var qs = new URLSearchParams();
		Object.keys(params).forEach(function (k) {
			if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
				qs.set(k, params[k]);
			}
		});
		var url = window.location.pathname + (qs.toString() ? '?' + qs.toString() : '');
		window.history.pushState({}, '', url);
	}

	/* ═══════════════════════════════════════════
	   HELPERS
	═══════════════════════════════════════════ */
	function showSpinner(show) {
		var spinner = document.getElementById('mauriel-spinner');
		if (spinner) spinner.style.display = show ? 'flex' : 'none';
	}

	function showNotice(el, msg, type) {
		if (!el) return;
		el.textContent = msg;
		el.className = 'mauriel-form-notice mauriel-notice-' + type;
		el.style.display = 'block';
		setTimeout(function () { if (type === 'success') el.style.display = 'none'; }, 5000);
	}

	function buildStarHtml(rating) {
		var r = Math.round(parseFloat(rating) || 0);
		var html = '';
		for (var i = 1; i <= 5; i++) html += '<span class="mauriel-star' + (i <= r ? ' filled' : '') + '">★</span>';
		return html;
	}

	function formToJSON(form) {
		var data = {};
		new FormData(form).forEach(function (val, key) { data[key] = val; });
		return data;
	}

	function escHtml(str) {
		if (!str) return '';
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	// Expose for use by inline scripts if needed
	window.MaurielPublic = { doSearch: doSearch, recordAnalytics: recordAnalytics };
})();
