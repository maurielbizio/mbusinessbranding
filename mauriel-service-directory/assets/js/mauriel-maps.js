/* Mauriel Service Directory — Google Maps */
/* globals google, maurielDirectoryData, maurielSingleData */

var MaurielMaps = (function () {
	'use strict';

	var directoryMap = null;
	var singleMap = null;
	var markers = [];
	var infoWindow = null;

	/* ── Directory map ── */
	function initDirectoryMap() {
		var el = document.getElementById('mauriel-map');
		if (!el) return;

		directoryMap = new google.maps.Map(el, {
			center: { lat: 39.5, lng: -98.35 },
			zoom: 4,
			mapTypeControl: false,
			fullscreenControl: true,
			streetViewControl: false,
			styles: [{ featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }]
		});

		infoWindow = new google.maps.InfoWindow();

		var mapData = window.maurielMapMarkers || [];
		if (mapData.length) {
			updateMapMarkers(mapData);
		}
	}

	/* ── Single listing map ── */
	function initSingleMap() {
		var el = document.getElementById('mauriel-single-map');
		if (!el) return;

		var lat = parseFloat(el.dataset.lat);
		var lng = parseFloat(el.dataset.lng);
		if (isNaN(lat) || isNaN(lng)) return;

		var center = { lat: lat, lng: lng };

		singleMap = new google.maps.Map(el, {
			center: center,
			zoom: 15,
			mapTypeControl: false,
			streetViewControl: false
		});

		var marker = new google.maps.Marker({
			position: center,
			map: singleMap,
			title: el.dataset.name || ''
		});

		addDirectionsLink(lat, lng);

		marker.addListener('click', function () {
			var iw = new google.maps.InfoWindow({
				content: '<strong>' + (el.dataset.name || '') + '</strong>'
			});
			iw.open(singleMap, marker);
		});
	}

	/* ── Update directory markers ── */
	function updateMapMarkers(mapData) {
		clearMarkers();
		if (!directoryMap || !mapData || !mapData.length) return;

		var bounds = new google.maps.LatLngBounds();

		mapData.forEach(function (item) {
			var lat = parseFloat(item.lat);
			var lng = parseFloat(item.lng);
			if (isNaN(lat) || isNaN(lng)) return;

			var position = { lat: lat, lng: lng };
			var marker = new google.maps.Marker({
				position: position,
				map: directoryMap,
				title: item.name || '',
				icon: item.featured ? {
					url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
						'<svg xmlns="http://www.w3.org/2000/svg" width="32" height="40" viewBox="0 0 32 40">' +
						'<path d="M16 0C7.2 0 0 7.2 0 16c0 11.2 16 24 16 24S32 27.2 32 16C32 7.2 24.8 0 16 0z" fill="#f59e0b"/>' +
						'<circle cx="16" cy="16" r="8" fill="white"/>' +
						'</svg>'
					),
					scaledSize: new google.maps.Size(32, 40),
					anchor: new google.maps.Point(16, 40)
				} : undefined
			});

			marker.listingData = item;
			markers.push(marker);
			bounds.extend(position);

			marker.addListener('click', function () {
				openInfoWindow(marker, directoryMap);
			});
		});

		if (markers.length === 1) {
			directoryMap.setCenter(bounds.getCenter());
			directoryMap.setZoom(14);
		} else if (markers.length > 1) {
			directoryMap.fitBounds(bounds);
		}
	}

	/* ── Info window content ── */
	function createInfoWindowContent(item) {
		var stars = '';
		var rating = parseFloat(item.avg_rating) || 0;
		for (var i = 1; i <= 5; i++) {
			stars += i <= Math.round(rating) ? '★' : '☆';
		}

		var logoHtml = item.logo_url
			? '<img src="' + escHtml(item.logo_url) + '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">'
			: '<div style="width:40px;height:40px;border-radius:50%;background:#e5e7eb;flex-shrink:0"></div>';

		return '<div style="max-width:220px;font-family:Arial,sans-serif">' +
			'<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">' +
			logoHtml +
			'<div><strong style="font-size:14px">' + escHtml(item.name) + '</strong>' +
			(item.category ? '<div style="font-size:11px;color:#6b7280">' + escHtml(item.category) + '</div>' : '') +
			'</div></div>' +
			(rating > 0 ? '<div style="color:#f59e0b;font-size:13px">' + stars + ' <span style="color:#6b7280">(' + item.review_count + ')</span></div>' : '') +
			(item.city ? '<div style="font-size:12px;color:#6b7280;margin-top:4px">' + escHtml(item.city) + (item.state ? ', ' + escHtml(item.state) : '') + '</div>' : '') +
			'<div style="margin-top:10px"><a href="' + escHtml(item.url) + '" style="background:#2563eb;color:#fff;padding:5px 12px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600">View Listing</a></div>' +
			'</div>';
	}

	function openInfoWindow(marker, map) {
		if (!infoWindow) infoWindow = new google.maps.InfoWindow();
		infoWindow.setContent(createInfoWindowContent(marker.listingData));
		infoWindow.open(map, marker);
	}

	/* ── Directions link ── */
	function addDirectionsLink(lat, lng) {
		var link = document.getElementById('mauriel-directions-link');
		if (!link) return;
		link.href = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
	}

	/* ── Helpers ── */
	function clearMarkers() {
		markers.forEach(function (m) { m.setMap(null); });
		markers = [];
	}

	function escHtml(str) {
		if (!str) return '';
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	/* Public API */
	return {
		initDirectoryMap: initDirectoryMap,
		initSingleMap: initSingleMap,
		updateMapMarkers: updateMapMarkers,
		createInfoWindowContent: createInfoWindowContent,
		addDirectionsLink: addDirectionsLink
	};
})();

/* Google Maps async callback */
window.maurielMapInit = function () {
	MaurielMaps.initDirectoryMap();
	MaurielMaps.initSingleMap();
};
