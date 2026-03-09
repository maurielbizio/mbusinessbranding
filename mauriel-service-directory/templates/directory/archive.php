<?php
defined('ABSPATH') || exit;

/**
 * Directory Archive Template
 *
 * @var array  $listings  Array of formatted listing data objects.
 * @var array  $params    Current search/filter parameters.
 * @var string $view      Current view mode: 'grid' | 'list' | 'map'.
 * @var int    $total     Total number of matched listings.
 * @var int    $current_page Current page number.
 * @var int    $total_pages  Total number of pages.
 */

$view         = isset($view) ? sanitize_key($view) : 'grid';
$listings     = isset($listings) && is_array($listings) ? $listings : [];
$total        = isset($total) ? absint($total) : count($listings);
$current_page = isset($current_page) ? absint($current_page) : 1;
$total_pages  = isset($total_pages) ? absint($total_pages) : 1;
$per_page     = isset($per_page) ? absint($per_page) : 12;
$start_count  = $total > 0 ? ( ($current_page - 1) * $per_page ) + 1 : 0;
$end_count    = min($current_page * $per_page, $total);
?>
<div class="mauriel-directory__results" id="mauriel-results-container" data-view="<?php echo esc_attr($view); ?>">

    <!-- Results Header -->
    <div class="mauriel-results-header" id="mauriel-results-header">
        <div class="mauriel-results-header__count" id="mauriel-results-count" aria-live="polite" aria-atomic="true">
            <?php if ($total > 0) : ?>
                <?php
                printf(
                    esc_html__('Showing %1$s–%2$s of %3$s listings', 'mauriel-service-directory'),
                    number_format_i18n($start_count),
                    number_format_i18n($end_count),
                    number_format_i18n($total)
                );
                ?>
            <?php else : ?>
                <?php esc_html_e('0 listings found', 'mauriel-service-directory'); ?>
            <?php endif; ?>
        </div>

        <div class="mauriel-results-header__actions">
            <!-- Active filters summary -->
            <?php
            $active_filters = [];
            if ( ! empty($params['keyword']) ) {
                $active_filters[] = sprintf(
                    '<span class="mauriel-active-filter">"%s" <button type="button" class="mauriel-active-filter__remove" data-param="keyword" aria-label="%s">&#215;</button></span>',
                    esc_html($params['keyword']),
                    esc_attr__('Remove keyword filter', 'mauriel-service-directory')
                );
            }
            if ( ! empty($params['open_now']) ) {
                $active_filters[] = '<span class="mauriel-active-filter">' . esc_html__('Open Now', 'mauriel-service-directory') . ' <button type="button" class="mauriel-active-filter__remove" data-param="open_now" aria-label="' . esc_attr__('Remove open now filter', 'mauriel-service-directory') . '">&#215;</button></span>';
            }
            if ( ! empty($params['featured_only']) ) {
                $active_filters[] = '<span class="mauriel-active-filter">' . esc_html__('Featured Only', 'mauriel-service-directory') . ' <button type="button" class="mauriel-active-filter__remove" data-param="featured_only" aria-label="' . esc_attr__('Remove featured filter', 'mauriel-service-directory') . '">&#215;</button></span>';
            }
            if (!empty($active_filters)) :
            ?>
                <div class="mauriel-active-filters" aria-label="<?php esc_attr_e('Active Filters', 'mauriel-service-directory'); ?>">
                    <?php echo implode(' ', $active_filters); // already escaped above ?>
                    <button type="button" id="mauriel-clear-all" class="mauriel-btn mauriel-btn-outline mauriel-btn--xs">
                        <?php esc_html_e('Clear All', 'mauriel-service-directory'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Spinner (hidden by default) -->
    <div class="mauriel-loading" id="mauriel-loading" aria-live="polite" aria-label="<?php esc_attr_e('Loading listings…', 'mauriel-service-directory'); ?>" hidden>
        <div class="mauriel-spinner" aria-hidden="true">
            <div class="mauriel-spinner__ring"></div>
        </div>
        <span class="mauriel-loading__text"><?php esc_html_e('Loading listings…', 'mauriel-service-directory'); ?></span>
    </div>

    <!-- Map View (conditionally rendered) -->
    <?php if ($view === 'map') : ?>
        <div class="mauriel-map-view" id="mauriel-map-view-wrapper">
            <?php
            $map_data = [];
            foreach ($listings as $ld) {
                if ( ! empty($ld['lat']) && ! empty($ld['lng']) ) {
                    $map_data[] = [
                        'id'        => absint($ld['id'] ?? 0),
                        'title'     => $ld['business_name'] ?? '',
                        'address'   => trim(($ld['address'] ?? '') . ' ' . ($ld['city'] ?? '') . ', ' . ($ld['state'] ?? '')),
                        'lat'       => floatval($ld['lat']),
                        'lng'       => floatval($ld['lng']),
                        'rating'    => floatval($ld['avg_rating'] ?? 0),
                        'permalink' => $ld['permalink'] ?? '',
                        'logo_url'  => $ld['logo_url'] ?? '',
                        'featured'  => ! empty($ld['featured']),
                    ];
                }
            }
            include __DIR__ . '/map-view.php';
            ?>
        </div>
    <?php endif; ?>

    <!-- Listings Grid / List -->
    <div
        id="mauriel-listings-grid"
        class="mauriel-listings-grid mauriel-view-<?php echo esc_attr($view); ?>"
        data-current-page="<?php echo esc_attr($current_page); ?>"
        data-total-pages="<?php echo esc_attr($total_pages); ?>"
        aria-label="<?php esc_attr_e('Business Listings', 'mauriel-service-directory'); ?>"
    >
        <?php if ( ! empty($listings) ) : ?>
            <?php foreach ($listings as $listing_data) : ?>
                <?php
                // Make listing_data available to the card partial.
                include __DIR__ . '/listing-card.php';
                ?>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Empty State -->
            <div class="mauriel-empty-state" id="mauriel-empty-state" role="status">
                <div class="mauriel-empty-state__icon" aria-hidden="true">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <h3 class="mauriel-empty-state__heading">
                    <?php esc_html_e('No listings found', 'mauriel-service-directory'); ?>
                </h3>
                <p class="mauriel-empty-state__message">
                    <?php esc_html_e('Try adjusting your filters or expanding your search radius to find more businesses.', 'mauriel-service-directory'); ?>
                </p>
                <button type="button" id="mauriel-reset-search" class="mauriel-btn mauriel-btn-primary">
                    <?php esc_html_e('Reset Search', 'mauriel-service-directory'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div id="mauriel-pagination-wrapper">
            <?php include __DIR__ . '/pagination.php'; ?>
        </div>
    <?php endif; ?>

</div>
