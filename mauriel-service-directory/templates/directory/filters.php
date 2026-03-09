<?php
defined('ABSPATH') || exit;
?>
<aside class="mauriel-filters" id="mauriel-filters-sidebar" role="search" aria-label="<?php esc_attr_e('Search Filters', 'mauriel-service-directory'); ?>">
    <div class="mauriel-filters__header">
        <h2 class="mauriel-filters__title"><?php esc_html_e('Search & Filter', 'mauriel-service-directory'); ?></h2>
        <button type="button" class="mauriel-filters__toggle" id="mauriel-filters-toggle" aria-expanded="true" aria-controls="mauriel-filters-body">
            <span class="screen-reader-text"><?php esc_html_e('Toggle Filters', 'mauriel-service-directory'); ?></span>
            <span class="mauriel-filters__toggle-icon" aria-hidden="true">&#9660;</span>
        </button>
    </div>

    <form id="mauriel-search-form" class="mauriel-filters__body" method="GET" role="search" aria-label="<?php esc_attr_e('Directory Search', 'mauriel-service-directory'); ?>">
        <?php wp_nonce_field('mauriel_search_nonce', 'mauriel_search_nonce'); ?>

        <!-- Keyword -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-keyword">
                <?php esc_html_e('Keyword', 'mauriel-service-directory'); ?>
            </label>
            <input
                type="text"
                id="mauriel-keyword"
                name="keyword"
                class="mauriel-form-control"
                placeholder="<?php esc_attr_e('Business name, service…', 'mauriel-service-directory'); ?>"
                value="<?php echo esc_attr(get_query_var('mauriel_keyword', '')); ?>"
                autocomplete="off"
            />
        </div>

        <!-- Category -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-category">
                <?php esc_html_e('Category', 'mauriel-service-directory'); ?>
            </label>
            <?php
            $selected_cat = isset($_GET['category']) ? absint($_GET['category']) : 0;
            wp_dropdown_categories([
                'taxonomy'         => 'mauriel_category',
                'name'             => 'category',
                'id'               => 'mauriel-category',
                'class'            => 'mauriel-form-control',
                'show_option_all'  => __('All Categories', 'mauriel-service-directory'),
                'selected'         => $selected_cat,
                'hide_empty'       => false,
                'hierarchical'     => true,
                'orderby'          => 'name',
                'show_count'       => true,
            ]);
            ?>
        </div>

        <!-- ZIP Code -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-zip">
                <?php esc_html_e('ZIP Code', 'mauriel-service-directory'); ?>
            </label>
            <input
                type="text"
                id="mauriel-zip"
                name="zip"
                class="mauriel-form-control"
                placeholder="<?php esc_attr_e('e.g. 90210', 'mauriel-service-directory'); ?>"
                maxlength="10"
                pattern="[0-9\-]{3,10}"
                value="<?php echo esc_attr(isset($_GET['zip']) ? sanitize_text_field($_GET['zip']) : ''); ?>"
            />
        </div>

        <!-- Radius -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-radius">
                <?php esc_html_e('Search Radius', 'mauriel-service-directory'); ?>
            </label>
            <select id="mauriel-radius" name="radius" class="mauriel-form-control">
                <?php
                $selected_radius = isset($_GET['radius']) ? absint($_GET['radius']) : 25;
                $radius_options  = [5, 10, 25, 50, 100];
                foreach ($radius_options as $miles) :
                ?>
                    <option value="<?php echo esc_attr($miles); ?>" <?php selected($selected_radius, $miles); ?>>
                        <?php printf(esc_html__('%d miles', 'mauriel-service-directory'), $miles); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Min Rating -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-rating">
                <?php esc_html_e('Minimum Rating', 'mauriel-service-directory'); ?>
            </label>
            <select id="mauriel-rating" name="min_rating" class="mauriel-form-control">
                <?php
                $selected_rating = isset($_GET['min_rating']) ? sanitize_text_field($_GET['min_rating']) : '';
                $rating_options  = [
                    ''  => __('Any Rating', 'mauriel-service-directory'),
                    '3' => __('3+ Stars', 'mauriel-service-directory'),
                    '4' => __('4+ Stars', 'mauriel-service-directory'),
                    '5' => __('5 Stars Only', 'mauriel-service-directory'),
                ];
                foreach ($rating_options as $val => $label) :
                ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($selected_rating, $val); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Checkboxes -->
        <div class="mauriel-form-group mauriel-form-group--checkboxes">
            <div class="mauriel-form-check">
                <input
                    type="checkbox"
                    id="mauriel-open-now"
                    name="open_now"
                    value="1"
                    class="mauriel-form-check__input"
                    <?php checked(isset($_GET['open_now']) && $_GET['open_now'] == '1'); ?>
                />
                <label class="mauriel-form-check__label" for="mauriel-open-now">
                    <span class="mauriel-badge mauriel-badge--open" aria-hidden="true"></span>
                    <?php esc_html_e('Open Now', 'mauriel-service-directory'); ?>
                </label>
            </div>

            <div class="mauriel-form-check">
                <input
                    type="checkbox"
                    id="mauriel-featured-only"
                    name="featured_only"
                    value="1"
                    class="mauriel-form-check__input"
                    <?php checked(isset($_GET['featured_only']) && $_GET['featured_only'] == '1'); ?>
                />
                <label class="mauriel-form-check__label" for="mauriel-featured-only">
                    <span class="mauriel-badge mauriel-badge--featured" aria-hidden="true">&#9733;</span>
                    <?php esc_html_e('Featured Only', 'mauriel-service-directory'); ?>
                </label>
            </div>
        </div>

        <!-- Sort By -->
        <div class="mauriel-form-group">
            <label class="mauriel-form-label" for="mauriel-sort">
                <?php esc_html_e('Sort By', 'mauriel-service-directory'); ?>
            </label>
            <select id="mauriel-sort" name="sort" class="mauriel-form-control">
                <?php
                $selected_sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'featured';
                $sort_options  = [
                    'featured' => __('Featured', 'mauriel-service-directory'),
                    'rating'   => __('Highest Rated', 'mauriel-service-directory'),
                    'newest'   => __('Newest', 'mauriel-service-directory'),
                    'alpha'    => __('A–Z', 'mauriel-service-directory'),
                ];
                foreach ($sort_options as $val => $label) :
                ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($selected_sort, $val); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- View Toggle -->
        <div class="mauriel-form-group">
            <span class="mauriel-form-label" id="mauriel-view-toggle-label">
                <?php esc_html_e('View', 'mauriel-service-directory'); ?>
            </span>
            <div class="mauriel-view-toggle" role="group" aria-labelledby="mauriel-view-toggle-label">
                <button
                    type="button"
                    class="mauriel-view-toggle__btn mauriel-view-toggle__btn--grid <?php echo (!isset($_GET['view']) || $_GET['view'] === 'grid') ? 'is-active' : ''; ?>"
                    data-view="grid"
                    aria-pressed="<?php echo (!isset($_GET['view']) || $_GET['view'] === 'grid') ? 'true' : 'false'; ?>"
                    title="<?php esc_attr_e('Grid View', 'mauriel-service-directory'); ?>"
                >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <rect x="0" y="0" width="7" height="7"/><rect x="9" y="0" width="7" height="7"/>
                        <rect x="0" y="9" width="7" height="7"/><rect x="9" y="9" width="7" height="7"/>
                    </svg>
                    <span class="screen-reader-text"><?php esc_html_e('Grid View', 'mauriel-service-directory'); ?></span>
                </button>
                <button
                    type="button"
                    class="mauriel-view-toggle__btn mauriel-view-toggle__btn--list <?php echo (isset($_GET['view']) && $_GET['view'] === 'list') ? 'is-active' : ''; ?>"
                    data-view="list"
                    aria-pressed="<?php echo (isset($_GET['view']) && $_GET['view'] === 'list') ? 'true' : 'false'; ?>"
                    title="<?php esc_attr_e('List View', 'mauriel-service-directory'); ?>"
                >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <rect x="0" y="0" width="16" height="3"/><rect x="0" y="6" width="16" height="3"/>
                        <rect x="0" y="12" width="16" height="3"/>
                    </svg>
                    <span class="screen-reader-text"><?php esc_html_e('List View', 'mauriel-service-directory'); ?></span>
                </button>
                <button
                    type="button"
                    class="mauriel-view-toggle__btn mauriel-view-toggle__btn--map <?php echo (isset($_GET['view']) && $_GET['view'] === 'map') ? 'is-active' : ''; ?>"
                    data-view="map"
                    aria-pressed="<?php echo (isset($_GET['view']) && $_GET['view'] === 'map') ? 'true' : 'false'; ?>"
                    title="<?php esc_attr_e('Map View', 'mauriel-service-directory'); ?>"
                >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8 0C5.24 0 3 2.24 3 5c0 3.75 5 11 5 11s5-7.25 5-11c0-2.76-2.24-5-5-5zm0 7.5C6.62 7.5 5.5 6.38 5.5 5S6.62 2.5 8 2.5 10.5 3.62 10.5 5 9.38 7.5 8 7.5z"/>
                    </svg>
                    <span class="screen-reader-text"><?php esc_html_e('Map View', 'mauriel-service-directory'); ?></span>
                </button>
            </div>
        </div>

        <!-- Search Button -->
        <div class="mauriel-form-group mauriel-form-group--actions">
            <button type="submit" id="mauriel-search-btn" class="mauriel-btn mauriel-btn-primary mauriel-btn--full">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <?php esc_html_e('Search Listings', 'mauriel-service-directory'); ?>
            </button>
            <button type="button" id="mauriel-clear-filters" class="mauriel-btn mauriel-btn-outline mauriel-btn--full" style="margin-top:8px;">
                <?php esc_html_e('Clear Filters', 'mauriel-service-directory'); ?>
            </button>
        </div>

        <!-- Hidden view field (updated by JS) -->
        <input type="hidden" id="mauriel-view-input" name="view" value="<?php echo esc_attr(isset($_GET['view']) ? sanitize_key($_GET['view']) : 'grid'); ?>" />

    </form>
</aside>
