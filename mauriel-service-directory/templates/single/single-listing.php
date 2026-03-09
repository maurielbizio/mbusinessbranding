<?php
defined('ABSPATH') || exit;

/**
 * Single Listing Detail Page Template
 *
 * @var WP_Post $post  The mauriel_listing post object (also available as $listing).
 */

if ( ! isset($post) || ! ($post instanceof WP_Post) ) {
    $post = get_queried_object();
}

if ( ! $post || $post->post_type !== 'mauriel_listing' ) {
    wp_die(esc_html__('Listing not found.', 'mauriel-service-directory'));
}

$listing    = $post;
$listing_id = $listing->ID;

// ── Meta retrieval helper ──────────────────────────────────────────────
$get_meta = function( $key, $default = '' ) use ($listing_id) {
    $val = get_post_meta($listing_id, '_mauriel_' . $key, true);
    return ($val !== '' && $val !== false) ? $val : $default;
};

// ── Core meta ──────────────────────────────────────────────────────────
$business_name = $get_meta('business_name', $listing->post_title);
$tagline       = $get_meta('tagline');
$description   = $get_meta('description', $listing->post_content);
$phone         = $get_meta('phone');
$email         = $get_meta('email');
$website       = $get_meta('website');
$address       = $get_meta('address');
$city          = $get_meta('city');
$state         = $get_meta('state');
$zip           = $get_meta('zip');
$service_area  = $get_meta('service_area');
$lat           = $get_meta('lat');
$lng           = $get_meta('lng');
$avg_rating    = floatval($get_meta('avg_rating', 0));
$review_count  = absint($get_meta('review_count', 0));
$is_featured   = $get_meta('featured') == '1';
$is_verified   = $get_meta('verified') == '1';
$package_name  = $get_meta('package', 'Basic');
$booking_url   = $get_meta('booking_url');
$facebook      = $get_meta('facebook');
$instagram     = $get_meta('instagram');
$twitter       = $get_meta('twitter');
$linkedin      = $get_meta('linkedin');
$tiktok        = $get_meta('tiktok');

// ── Media ──────────────────────────────────────────────────────────────
$logo_url  = class_exists('Mauriel_Media') ? Mauriel_Media::get_logo_url($listing_id) : '';
$cover_url = class_exists('Mauriel_Media') ? Mauriel_Media::get_cover_url($listing_id) : '';
$gallery_urls = class_exists('Mauriel_Media') ? Mauriel_Media::get_gallery_urls($listing_id) : [];

// Placeholder fallbacks.
$plugin_url = plugins_url('', dirname(__FILE__) . '/../mauriel-service-directory.php');
if (empty($logo_url))  $logo_url  = $plugin_url . '/assets/images/placeholder-logo.svg';
if (empty($cover_url)) $cover_url = $plugin_url . '/assets/images/placeholder-cover.jpg';

// ── Hours ──────────────────────────────────────────────────────────────
$hours   = class_exists('Mauriel_DB_Hours') ? Mauriel_DB_Hours::get_hours($listing_id) : [];
$is_open = class_exists('Mauriel_DB_Hours') ? Mauriel_DB_Hours::is_open_now($listing_id) : false;

// ── Reviews ────────────────────────────────────────────────────────────
$reviews     = class_exists('Mauriel_DB_Reviews') ? Mauriel_DB_Reviews::get_reviews($listing_id, ['status' => 'approved']) : [];
$total_reviews = count($reviews);

// ── Subscription / Package ─────────────────────────────────────────────
$subscription = class_exists('Mauriel_Subscriptions') ? Mauriel_Subscriptions::get_listing_subscription($listing_id) : null;
$package      = $package_name;

// ── Coupons ────────────────────────────────────────────────────────────
$coupons = class_exists('Mauriel_DB_Coupons') ? Mauriel_DB_Coupons::get_active($listing_id) : [];

// ── Booking embed ──────────────────────────────────────────────────────
$booking_embed = class_exists('Mauriel_Booking') ? Mauriel_Booking::get_embed_code($listing_id) : '';

// ── Category ──────────────────────────────────────────────────────────
$cats          = get_the_terms($listing_id, 'mauriel_category');
$category_name = ($cats && ! is_wp_error($cats)) ? $cats[0]->name : '';

// ── Record analytics view ─────────────────────────────────────────────
if (class_exists('Mauriel_Analytics')) {
    Mauriel_Analytics::record($listing_id, 'view');
}

// ── Stars helper ──────────────────────────────────────────────────────
$full_stars  = floor($avg_rating);
$half_star   = ($avg_rating - $full_stars) >= 0.5;
$empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

$package_slug = strtolower(sanitize_html_class($package_name));
?>
<div class="mauriel-listing-single" id="mauriel-listing-<?php echo esc_attr($listing_id); ?>" itemscope itemtype="https://schema.org/LocalBusiness">
    <meta itemprop="name" content="<?php echo esc_attr($business_name); ?>" />
    <?php if (!empty($phone)) : ?><meta itemprop="telephone" content="<?php echo esc_attr($phone); ?>" /><?php endif; ?>

    <!-- ── Cover Image ───────────────────────────────────────────────── -->
    <div class="mauriel-listing-cover" role="img" aria-label="<?php printf(esc_attr__('Cover image for %s', 'mauriel-service-directory'), esc_attr($business_name)); ?>">
        <img
            src="<?php echo esc_url($cover_url); ?>"
            alt=""
            class="mauriel-listing-cover__img"
            loading="eager"
            itemprop="image"
        />
        <div class="mauriel-listing-cover__overlay">
            <h1 class="mauriel-listing-cover__title"><?php echo esc_html($business_name); ?></h1>
            <?php if (!empty($tagline)) : ?>
                <p class="mauriel-listing-cover__tagline"><?php echo esc_html($tagline); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Listing Header Bar ────────────────────────────────────────── -->
    <div class="mauriel-listing-header">
        <div class="mauriel-listing-header__inner mauriel-container">

            <!-- Logo + Identity -->
            <div class="mauriel-listing-identity">
                <div class="mauriel-listing-logo">
                    <img
                        src="<?php echo esc_url($logo_url); ?>"
                        alt="<?php printf(esc_attr__('%s logo', 'mauriel-service-directory'), esc_attr($business_name)); ?>"
                        class="mauriel-listing-logo__img"
                        width="80"
                        height="80"
                    />
                </div>
                <div class="mauriel-listing-identity__info">
                    <div class="mauriel-listing-identity__badges">
                        <?php if (!empty($category_name)) : ?>
                            <span class="mauriel-badge mauriel-badge--category"><?php echo esc_html($category_name); ?></span>
                        <?php endif; ?>
                        <?php if ($is_featured) : ?>
                            <span class="mauriel-badge mauriel-badge--featured">&#9733; <?php esc_html_e('Featured', 'mauriel-service-directory'); ?></span>
                        <?php endif; ?>
                        <?php if ($is_verified) : ?>
                            <span class="mauriel-badge mauriel-badge--verified" title="<?php esc_attr_e('Verified Business', 'mauriel-service-directory'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?php esc_html_e('Verified', 'mauriel-service-directory'); ?>
                            </span>
                        <?php endif; ?>
                        <span class="mauriel-badge mauriel-badge--package mauriel-badge--package-<?php echo esc_attr($package_slug); ?>"><?php echo esc_html($package_name); ?></span>
                    </div>

                    <!-- Rating -->
                    <div class="mauriel-listing-identity__rating">
                        <div class="mauriel-stars mauriel-stars--lg" role="img" aria-label="<?php printf(esc_attr__('Rated %.1f out of 5', 'mauriel-service-directory'), $avg_rating); ?>">
                            <?php for ($i = 0; $i < $full_stars; $i++) : ?><span class="mauriel-star mauriel-star--full" aria-hidden="true">&#9733;</span><?php endfor; ?>
                            <?php if ($half_star) : ?><span class="mauriel-star mauriel-star--half" aria-hidden="true">&#9734;</span><?php endif; ?>
                            <?php for ($i = 0; $i < $empty_stars; $i++) : ?><span class="mauriel-star mauriel-star--empty" aria-hidden="true">&#9734;</span><?php endif; ?>
                        </div>
                        <span class="mauriel-listing-identity__rating-score"><?php echo esc_html(number_format($avg_rating, 1)); ?></span>
                        <a href="#mauriel-reviews" class="mauriel-listing-identity__review-count">
                            (<?php printf(esc_html(_n('%s review', '%s reviews', $review_count, 'mauriel-service-directory')), number_format_i18n($review_count)); ?>)
                        </a>
                        <span class="mauriel-badge <?php echo $is_open ? 'mauriel-badge--open' : 'mauriel-badge--closed'; ?>">
                            <span class="mauriel-badge__dot" aria-hidden="true"></span>
                            <?php echo $is_open ? esc_html__('Open Now', 'mauriel-service-directory') : esc_html__('Closed', 'mauriel-service-directory'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mauriel-listing-header__actions" role="group" aria-label="<?php esc_attr_e('Contact Actions', 'mauriel-service-directory'); ?>">
                <?php if (!empty($phone)) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^+\d]/', '', $phone)); ?>"
                       class="mauriel-btn mauriel-btn-primary mauriel-phone-click-track"
                       data-listing-id="<?php echo esc_attr($listing_id); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.09-1.09a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
                        <?php esc_html_e('Call Now', 'mauriel-service-directory'); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($email)) : ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>"
                       class="mauriel-btn mauriel-btn-outline mauriel-email-click-track"
                       data-listing-id="<?php echo esc_attr($listing_id); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php esc_html_e('Email', 'mauriel-service-directory'); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($website)) : ?>
                    <a href="<?php echo esc_url($website); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="mauriel-btn mauriel-btn-outline mauriel-website-click-track"
                       data-listing-id="<?php echo esc_attr($listing_id); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                        <?php esc_html_e('Visit Website', 'mauriel-service-directory'); ?>
                    </a>
                <?php endif; ?>

                <a href="#mauriel-lead-forms" class="mauriel-btn mauriel-btn-secondary">
                    <?php esc_html_e('Get Quote', 'mauriel-service-directory'); ?>
                </a>

                <a href="#mauriel-lead-forms" class="mauriel-btn mauriel-btn-outline">
                    <?php esc_html_e('Contact', 'mauriel-service-directory'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- ── Main Content ───────────────────────────────────────────────── -->
    <div class="mauriel-listing-body mauriel-container">

        <!-- Content Column -->
        <div class="mauriel-listing-content">

            <!-- Tab Navigation -->
            <nav class="mauriel-listing-tabs" aria-label="<?php esc_attr_e('Listing Sections', 'mauriel-service-directory'); ?>">
                <button type="button" class="mauriel-tab-btn is-active" data-tab="about" aria-selected="true"><?php esc_html_e('About', 'mauriel-service-directory'); ?></button>
                <?php if (!empty($gallery_urls)) : ?>
                    <button type="button" class="mauriel-tab-btn" data-tab="photos" aria-selected="false"><?php esc_html_e('Photos', 'mauriel-service-directory'); ?></button>
                <?php endif; ?>
                <button type="button" class="mauriel-tab-btn" data-tab="hours" aria-selected="false"><?php esc_html_e('Hours', 'mauriel-service-directory'); ?></button>
                <button type="button" class="mauriel-tab-btn" data-tab="reviews" aria-selected="false" id="mauriel-tab-reviews-btn">
                    <?php esc_html_e('Reviews', 'mauriel-service-directory'); ?>
                    <?php if ($review_count > 0) : ?>
                        <span class="mauriel-tab-btn__count"><?php echo esc_html(number_format_i18n($review_count)); ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="mauriel-tab-btn" data-tab="location" aria-selected="false"><?php esc_html_e('Location', 'mauriel-service-directory'); ?></button>
                <?php if (!empty($coupons)) : ?>
                    <button type="button" class="mauriel-tab-btn" data-tab="deals" aria-selected="false">
                        <?php esc_html_e('Deals', 'mauriel-service-directory'); ?>
                        <span class="mauriel-tab-btn__count mauriel-tab-btn__count--accent"><?php echo esc_html(count($coupons)); ?></span>
                    </button>
                <?php endif; ?>
            </nav>

            <!-- ── About ──────────────────────────────────────────────── -->
            <section class="mauriel-tab-content is-active" id="mauriel-tab-about" data-tab="about">
                <h2 class="mauriel-section-title"><?php esc_html_e('About This Business', 'mauriel-service-directory'); ?></h2>

                <?php if (!empty($description)) : ?>
                    <div class="mauriel-listing-description" itemprop="description">
                        <?php echo wp_kses_post(wpautop($description)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($service_area)) : ?>
                    <div class="mauriel-listing-service-area">
                        <h3><?php esc_html_e('Service Area', 'mauriel-service-directory'); ?></h3>
                        <p><?php echo esc_html($service_area); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Social Media Links -->
                <?php
                $socials = array_filter([
                    'facebook'  => ['url' => $facebook,  'label' => __('Facebook', 'mauriel-service-directory'),  'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
                    'instagram' => ['url' => $instagram, 'label' => __('Instagram', 'mauriel-service-directory'), 'icon' => 'M16 4H8a4 4 0 00-4 4v8a4 4 0 004 4h8a4 4 0 004-4V8a4 4 0 00-4-4zm-4 10a3 3 0 110-6 3 3 0 010 6zm5.5-9a1 1 0 110 2 1 1 0 010-2z'],
                    'twitter'   => ['url' => $twitter,   'label' => __('Twitter / X', 'mauriel-service-directory'), 'icon' => 'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z'],
                    'linkedin'  => ['url' => $linkedin,  'label' => __('LinkedIn', 'mauriel-service-directory'),  'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z'],
                    'tiktok'    => ['url' => $tiktok,    'label' => __('TikTok', 'mauriel-service-directory'),    'icon' => 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.77 1.52V6.77a4.85 4.85 0 01-1-.08z'],
                ], function($s) { return !empty($s['url']); });
                ?>

                <?php if (!empty($socials)) : ?>
                    <div class="mauriel-social-links">
                        <h3><?php esc_html_e('Find Us Online', 'mauriel-service-directory'); ?></h3>
                        <div class="mauriel-social-links__list">
                            <?php foreach ($socials as $network => $data) : ?>
                                <a
                                    href="<?php echo esc_url($data['url']); ?>"
                                    class="mauriel-social-link mauriel-social-link--<?php echo esc_attr($network); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="<?php echo esc_attr($data['label']); ?>"
                                >
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="<?php echo esc_attr($data['icon']); ?>"/>
                                    </svg>
                                    <span><?php echo esc_html($data['label']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ── Photos ─────────────────────────────────────────────── -->
            <?php if (!empty($gallery_urls)) : ?>
                <section class="mauriel-tab-content" id="mauriel-tab-photos" data-tab="photos" hidden>
                    <h2 class="mauriel-section-title"><?php esc_html_e('Photos', 'mauriel-service-directory'); ?></h2>
                    <?php include __DIR__ . '/gallery.php'; ?>
                </section>
            <?php endif; ?>

            <!-- ── Hours ──────────────────────────────────────────────── -->
            <section class="mauriel-tab-content" id="mauriel-tab-hours" data-tab="hours" hidden>
                <h2 class="mauriel-section-title"><?php esc_html_e('Business Hours', 'mauriel-service-directory'); ?></h2>
                <?php include __DIR__ . '/hours-display.php'; ?>
            </section>

            <!-- ── Reviews ────────────────────────────────────────────── -->
            <section class="mauriel-tab-content" id="mauriel-tab-reviews" data-tab="reviews" hidden>
                <h2 class="mauriel-section-title" id="mauriel-reviews"><?php esc_html_e('Reviews', 'mauriel-service-directory'); ?></h2>
                <?php include __DIR__ . '/reviews-section.php'; ?>
            </section>

            <!-- ── Location ───────────────────────────────────────────── -->
            <section class="mauriel-tab-content" id="mauriel-tab-location" data-tab="location" hidden>
                <h2 class="mauriel-section-title"><?php esc_html_e('Location', 'mauriel-service-directory'); ?></h2>
                <?php include __DIR__ . '/map-embed.php'; ?>
            </section>

            <!-- ── Deals ──────────────────────────────────────────────── -->
            <?php if (!empty($coupons)) : ?>
                <section class="mauriel-tab-content" id="mauriel-tab-deals" data-tab="deals" hidden>
                    <h2 class="mauriel-section-title"><?php esc_html_e('Deals &amp; Coupons', 'mauriel-service-directory'); ?></h2>
                    <?php include __DIR__ . '/coupons.php'; ?>
                </section>
            <?php endif; ?>

        </div><!-- .mauriel-listing-content -->

        <!-- Sidebar -->
        <aside class="mauriel-listing-sidebar" aria-label="<?php esc_attr_e('Listing Sidebar', 'mauriel-service-directory'); ?>">

            <!-- Lead Forms -->
            <div id="mauriel-lead-forms">
                <?php include __DIR__ . '/lead-forms.php'; ?>
            </div>

            <!-- Booking Widget -->
            <?php if (!empty($booking_url) || !empty($booking_embed)) : ?>
                <div class="mauriel-sidebar-widget">
                    <?php include __DIR__ . '/booking-widget.php'; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Info Card -->
            <div class="mauriel-sidebar-widget mauriel-quick-info">
                <h3 class="mauriel-sidebar-widget__title"><?php esc_html_e('Business Info', 'mauriel-service-directory'); ?></h3>
                <dl class="mauriel-quick-info__list">
                    <?php if (!empty($address)) : ?>
                        <dt><?php esc_html_e('Address', 'mauriel-service-directory'); ?></dt>
                        <dd itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                            <span itemprop="streetAddress"><?php echo esc_html($address); ?></span><br>
                            <span itemprop="addressLocality"><?php echo esc_html($city); ?></span><?php echo (!empty($city) && !empty($state)) ? ', ' : ''; ?><span itemprop="addressRegion"><?php echo esc_html($state); ?></span> <span itemprop="postalCode"><?php echo esc_html($zip); ?></span>
                        </dd>
                    <?php endif; ?>
                    <?php if (!empty($phone)) : ?>
                        <dt><?php esc_html_e('Phone', 'mauriel-service-directory'); ?></dt>
                        <dd><a href="tel:<?php echo esc_attr(preg_replace('/[^+\d]/', '', $phone)); ?>" itemprop="telephone"><?php echo esc_html($phone); ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($email)) : ?>
                        <dt><?php esc_html_e('Email', 'mauriel-service-directory'); ?></dt>
                        <dd><a href="mailto:<?php echo esc_attr($email); ?>" itemprop="email"><?php echo esc_html($email); ?></a></dd>
                    <?php endif; ?>
                    <?php if (!empty($website)) : ?>
                        <dt><?php esc_html_e('Website', 'mauriel-service-directory'); ?></dt>
                        <dd><a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer" class="mauriel-website-click-track" data-listing-id="<?php echo esc_attr($listing_id); ?>"><?php echo esc_html(preg_replace('#^https?://#', '', rtrim($website, '/'))); ?></a></dd>
                    <?php endif; ?>
                </dl>
            </div>

        </aside>

    </div><!-- .mauriel-listing-body -->
</div><!-- .mauriel-listing-single -->

<!-- Lightbox (CSS-driven) -->
<div class="mauriel-lightbox" id="mauriel-lightbox" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Image Lightbox', 'mauriel-service-directory'); ?>" hidden>
    <div class="mauriel-lightbox__backdrop" id="mauriel-lightbox-backdrop"></div>
    <div class="mauriel-lightbox__content">
        <button type="button" class="mauriel-lightbox__close" id="mauriel-lightbox-close" aria-label="<?php esc_attr_e('Close lightbox', 'mauriel-service-directory'); ?>">&#215;</button>
        <button type="button" class="mauriel-lightbox__prev" id="mauriel-lightbox-prev" aria-label="<?php esc_attr_e('Previous image', 'mauriel-service-directory'); ?>">&#10094;</button>
        <img src="" alt="" class="mauriel-lightbox__img" id="mauriel-lightbox-img" />
        <button type="button" class="mauriel-lightbox__next" id="mauriel-lightbox-next" aria-label="<?php esc_attr_e('Next image', 'mauriel-service-directory'); ?>">&#10095;</button>
    </div>
</div>

<!-- Analytics data for JS -->
<script type="text/javascript">
    window.maurielListingData = <?php echo wp_json_encode([
        'listing_id' => $listing_id,
        'nonce'      => wp_create_nonce('wp_rest'),
        'rest_url'   => esc_url_raw(rest_url()),
    ]); ?>;
</script>
