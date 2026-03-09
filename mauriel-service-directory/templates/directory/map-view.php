<?php
defined('ABSPATH') || exit;

/**
 * Directory Map View Template
 *
 * @var array $map_data  Array of marker objects: id, title, address, lat, lng, rating, permalink, logo_url, featured.
 */

$map_data = isset($map_data) && is_array($map_data) ? $map_data : [];
?>
<div class="mauriel-map-container" id="mauriel-map-container" style="height:500px; position:relative;">

    <div
        id="mauriel-map"
        class="mauriel-map"
        style="width:100%; height:100%;"
        role="application"
        aria-label="<?php esc_attr_e('Interactive business listings map', 'mauriel-service-directory'); ?>"
    ></div>

    <!-- Map controls overlay -->
    <div class="mauriel-map-controls" id="mauriel-map-controls" aria-label="<?php esc_attr_e('Map Controls', 'mauriel-service-directory'); ?>">
        <button
            type="button"
            class="mauriel-map-control__btn"
            id="mauriel-map-fit-bounds"
            title="<?php esc_attr_e('Fit all markers in view', 'mauriel-service-directory'); ?>"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/>
            </svg>
            <span class="screen-reader-text"><?php esc_html_e('Fit all markers in view', 'mauriel-service-directory'); ?></span>
        </button>
    </div>

    <!-- No map API key notice (shown via JS if API key missing) -->
    <div class="mauriel-map-error" id="mauriel-map-error" hidden>
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p><?php esc_html_e('Map could not be loaded. Please check your Google Maps API key in the plugin settings.', 'mauriel-service-directory'); ?></p>
    </div>

</div>

<!-- Marker data for Google Maps JS -->
<script type="text/javascript">
    window.maurielMapMarkers = <?php echo wp_json_encode( $map_data ); ?>;
</script>
