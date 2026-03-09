<?php
defined('ABSPATH') || exit;

/**
 * AJAX Pagination Template
 *
 * @var int $current_page  Current active page number.
 * @var int $total_pages   Total number of pages.
 */

$current_page = isset($current_page) ? absint($current_page) : 1;
$total_pages  = isset($total_pages)  ? absint($total_pages)  : 1;

if ($total_pages <= 1) {
    return;
}

// How many page buttons to show around the current page.
$range = 2;
?>
<nav class="mauriel-pagination" role="navigation" aria-label="<?php esc_attr_e('Listings Pagination', 'mauriel-service-directory'); ?>">

    <div class="mauriel-pagination__info" aria-live="polite">
        <?php
        printf(
            esc_html__('Page %1$s of %2$s', 'mauriel-service-directory'),
            '<strong>' . number_format_i18n($current_page) . '</strong>',
            '<strong>' . number_format_i18n($total_pages) . '</strong>'
        );
        ?>
    </div>

    <div class="mauriel-pagination__buttons">

        <!-- Previous Button -->
        <button
            type="button"
            class="mauriel-pagination__btn mauriel-pagination__btn--prev<?php echo ($current_page <= 1) ? ' is-disabled' : ''; ?>"
            data-page="<?php echo esc_attr($current_page - 1); ?>"
            <?php echo ($current_page <= 1) ? 'disabled aria-disabled="true"' : ''; ?>
            aria-label="<?php esc_attr_e('Go to previous page', 'mauriel-service-directory'); ?>"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            <span><?php esc_html_e('Previous', 'mauriel-service-directory'); ?></span>
        </button>

        <!-- Page Number Buttons -->
        <div class="mauriel-pagination__pages" role="group" aria-label="<?php esc_attr_e('Page numbers', 'mauriel-service-directory'); ?>">
            <?php
            // Always show first page.
            if ($current_page > $range + 1) {
                echo '<button type="button" class="mauriel-pagination__page-btn" data-page="1" aria-label="' . esc_attr__('Page 1', 'mauriel-service-directory') . '">1</button>';
                if ($current_page > $range + 2) {
                    echo '<span class="mauriel-pagination__ellipsis" aria-hidden="true">&hellip;</span>';
                }
            }

            // Pages around current.
            for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
                $is_current = ($i === $current_page);
                printf(
                    '<button type="button" class="mauriel-pagination__page-btn%s" data-page="%d" aria-label="%s"%s>%s</button>',
                    $is_current ? ' is-active' : '',
                    $i,
                    esc_attr(sprintf(__('Page %d', 'mauriel-service-directory'), $i)),
                    $is_current ? ' aria-current="page"' : '',
                    number_format_i18n($i)
                );
            }

            // Always show last page.
            if ($current_page < $total_pages - $range) {
                if ($current_page < $total_pages - $range - 1) {
                    echo '<span class="mauriel-pagination__ellipsis" aria-hidden="true">&hellip;</span>';
                }
                printf(
                    '<button type="button" class="mauriel-pagination__page-btn" data-page="%d" aria-label="%s">%s</button>',
                    $total_pages,
                    esc_attr(sprintf(__('Page %d', 'mauriel-service-directory'), $total_pages)),
                    number_format_i18n($total_pages)
                );
            }
            ?>
        </div>

        <!-- Next Button -->
        <button
            type="button"
            class="mauriel-pagination__btn mauriel-pagination__btn--next<?php echo ($current_page >= $total_pages) ? ' is-disabled' : ''; ?>"
            data-page="<?php echo esc_attr($current_page + 1); ?>"
            <?php echo ($current_page >= $total_pages) ? 'disabled aria-disabled="true"' : ''; ?>
            aria-label="<?php esc_attr_e('Go to next page', 'mauriel-service-directory'); ?>"
        >
            <span><?php esc_html_e('Next', 'mauriel-service-directory'); ?></span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>

    </div>
</nav>
