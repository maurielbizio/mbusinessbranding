<?php
defined('ABSPATH') || exit;

/**
 * Listing Card Template
 *
 * @var WP_Post $listing  The listing post object.
 * @var array   $meta     Associative array of listing meta values.
 */

// Accept either a WP_Post + meta array, or a pre-formatted data array (from AJAX renderListings).
if ( isset( $listing_data ) && is_array( $listing_data ) ) {
    $listing_id    = absint( $listing_data['id'] ?? 0 );
    $business_name = $listing_data['business_name'] ?? '';
    $tagline       = $listing_data['tagline'] ?? '';
    $phone         = $listing_data['phone'] ?? '';
    $city          = $listing_data['city'] ?? '';
    $state         = $listing_data['state'] ?? '';
    $avg_rating    = floatval( $listing_data['avg_rating'] ?? 0 );
    $review_count  = absint( $listing_data['review_count'] ?? 0 );
    $is_featured   = ! empty( $listing_data['featured'] );
    $is_open       = ! empty( $listing_data['is_open'] );
    $package_name  = $listing_data['package'] ?? 'Basic';
    $category_name = $listing_data['category'] ?? '';
    $logo_url      = $listing_data['logo_url'] ?? '';
    $permalink     = $listing_data['permalink'] ?? '#';
} else {
    // Direct template include with $listing (WP_Post) and $meta (array).
    if ( ! isset( $listing ) || ! ( $listing instanceof WP_Post ) ) {
        return;
    }

    $listing_id    = $listing->ID;
    $meta          = $meta ?? get_post_meta( $listing_id );

    // Flatten single-value meta.
    $get_meta = function( $key, $default = '' ) use ( $listing_id ) {
        $val = get_post_meta( $listing_id, '_mauriel_' . $key, true );
        return ( $val !== '' && $val !== false ) ? $val : $default;
    };

    $business_name = $get_meta('business_name', $listing->post_title );
    $tagline       = $get_meta('tagline');
    $phone         = $get_meta('phone');
    $city          = $get_meta('city');
    $state         = $get_meta('state');
    $avg_rating    = floatval( $get_meta('avg_rating', 0) );
    $review_count  = absint( $get_meta('review_count', 0) );
    $is_featured   = $get_meta('featured') == '1';
    $package_name  = $get_meta('package', 'Basic');
    $permalink     = get_permalink( $listing_id );

    // Open/closed status.
    $is_open = class_exists('Mauriel_DB_Hours') ? Mauriel_DB_Hours::is_open_now( $listing_id ) : false;

    // Logo URL.
    $logo_url = class_exists('Mauriel_Media') ? Mauriel_Media::get_logo_url( $listing_id ) : '';

    // Category.
    $cats          = get_the_terms( $listing_id, 'mauriel_category' );
    $category_name = ( $cats && ! is_wp_error($cats) ) ? $cats[0]->name : '';
}

// Placeholder logo.
if ( empty( $logo_url ) ) {
    $logo_url = plugins_url( 'assets/images/placeholder-logo.svg', dirname( dirname( __FILE__ ) ) . '/mauriel-service-directory.php' );
}

// Truncate tagline.
$short_desc = $tagline;
if ( mb_strlen( $short_desc ) > 120 ) {
    $short_desc = mb_substr( $short_desc, 0, 117 ) . '…';
}

// Package CSS modifier.
$package_slug = strtolower( sanitize_html_class( $package_name ) );

// Star display helper.
$full_stars  = floor( $avg_rating );
$half_star   = ( $avg_rating - $full_stars ) >= 0.5;
$empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
?>
<article
    class="mauriel-listing-card mauriel-listing-card--<?php echo esc_attr($package_slug); ?><?php echo $is_featured ? ' mauriel-listing-card--featured' : ''; ?>"
    data-listing-id="<?php echo esc_attr($listing_id); ?>"
    itemscope
    itemtype="https://schema.org/LocalBusiness"
>
    <!-- Card Header: Logo + Badges -->
    <div class="mauriel-card-header">
        <div class="mauriel-card-logo">
            <a href="<?php echo esc_url($permalink); ?>" tabindex="-1" aria-hidden="true">
                <img
                    src="<?php echo esc_url($logo_url); ?>"
                    alt="<?php echo esc_attr($business_name); ?>"
                    class="mauriel-card-logo__img"
                    loading="lazy"
                    width="64"
                    height="64"
                    itemprop="image"
                />
            </a>
        </div>

        <div class="mauriel-card-badges">
            <?php if ( $is_featured ) : ?>
                <span class="mauriel-badge mauriel-badge--featured" title="<?php esc_attr_e('Featured Listing', 'mauriel-service-directory'); ?>">
                    &#9733; <?php esc_html_e('Featured', 'mauriel-service-directory'); ?>
                </span>
            <?php endif; ?>

            <span class="mauriel-badge mauriel-badge--package mauriel-badge--package-<?php echo esc_attr($package_slug); ?>">
                <?php echo esc_html($package_name); ?>
            </span>

            <span class="mauriel-badge <?php echo $is_open ? 'mauriel-badge--open' : 'mauriel-badge--closed'; ?>" aria-live="polite">
                <span class="mauriel-badge__dot" aria-hidden="true"></span>
                <?php echo $is_open ? esc_html__('Open', 'mauriel-service-directory') : esc_html__('Closed', 'mauriel-service-directory'); ?>
            </span>
        </div>
    </div>

    <!-- Card Body -->
    <div class="mauriel-card-body">
        <h3 class="mauriel-card-body__name" itemprop="name">
            <a href="<?php echo esc_url($permalink); ?>" class="mauriel-card-body__name-link">
                <?php echo esc_html($business_name); ?>
            </a>
        </h3>

        <!-- Rating -->
        <div class="mauriel-card-body__rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
            <meta itemprop="ratingValue" content="<?php echo esc_attr(number_format($avg_rating, 1)); ?>" />
            <meta itemprop="reviewCount" content="<?php echo esc_attr($review_count); ?>" />

            <div class="mauriel-stars" role="img" aria-label="<?php printf(esc_attr__('Rated %s out of 5 stars', 'mauriel-service-directory'), number_format($avg_rating, 1)); ?>">
                <?php for ($i = 0; $i < $full_stars; $i++) : ?>
                    <span class="mauriel-star mauriel-star--full" aria-hidden="true">&#9733;</span>
                <?php endfor; ?>
                <?php if ($half_star) : ?>
                    <span class="mauriel-star mauriel-star--half" aria-hidden="true">&#9734;</span>
                <?php endif; ?>
                <?php for ($i = 0; $i < $empty_stars; $i++) : ?>
                    <span class="mauriel-star mauriel-star--empty" aria-hidden="true">&#9734;</span>
                <?php endfor; ?>
            </div>

            <span class="mauriel-card-body__rating-score">
                <?php echo esc_html(number_format($avg_rating, 1)); ?>
            </span>
            <span class="mauriel-card-body__review-count">
                (<?php printf(esc_html(_n('%s review', '%s reviews', $review_count, 'mauriel-service-directory')), number_format_i18n($review_count)); ?>)
            </span>
        </div>

        <!-- Category & Location -->
        <div class="mauriel-card-body__meta">
            <?php if ( ! empty($category_name) ) : ?>
                <span class="mauriel-card-body__category">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    <?php echo esc_html($category_name); ?>
                </span>
            <?php endif; ?>

            <?php if ( ! empty($city) || ! empty($state) ) : ?>
                <span class="mauriel-card-body__location" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span itemprop="addressLocality"><?php echo esc_html($city); ?></span><?php echo (!empty($city) && !empty($state)) ? ', ' : ''; ?><span itemprop="addressRegion"><?php echo esc_html($state); ?></span>
                </span>
            <?php endif; ?>
        </div>

        <!-- Phone -->
        <?php if ( ! empty($phone) ) : ?>
            <div class="mauriel-card-body__phone">
                <a
                    href="tel:<?php echo esc_attr(preg_replace('/[^+\d]/', '', $phone)); ?>"
                    class="mauriel-card-body__phone-link mauriel-phone-click-track"
                    data-listing-id="<?php echo esc_attr($listing_id); ?>"
                    itemprop="telephone"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.09-1.09a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
                    <?php echo esc_html($phone); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Short Description -->
        <?php if ( ! empty($short_desc) ) : ?>
            <p class="mauriel-card-body__tagline" itemprop="description">
                <?php echo esc_html($short_desc); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Card Footer: Actions -->
    <div class="mauriel-card-footer">
        <a
            href="<?php echo esc_url($permalink); ?>"
            class="mauriel-btn mauriel-btn-primary mauriel-btn--sm"
        >
            <?php esc_html_e('View Listing', 'mauriel-service-directory'); ?>
        </a>

        <a
            href="<?php echo esc_url(add_query_arg('action', 'contact', $permalink)); ?>"
            class="mauriel-btn mauriel-btn-outline mauriel-btn--sm mauriel-card-footer__contact"
        >
            <?php esc_html_e('Contact', 'mauriel-service-directory'); ?>
        </a>

        <a
            href="<?php echo esc_url(add_query_arg('action', 'quote', $permalink)); ?>"
            class="mauriel-btn mauriel-btn-secondary mauriel-btn--sm mauriel-card-footer__quote"
        >
            <?php esc_html_e('Get Quote', 'mauriel-service-directory'); ?>
        </a>
    </div>
</article>
