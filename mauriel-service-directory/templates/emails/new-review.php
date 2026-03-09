<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0}.email-wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:#f59e0b;color:#fff;padding:30px;text-align:center}.email-body{padding:30px}.review-box{background:#f9f9f9;border-left:4px solid #f59e0b;padding:15px;margin:15px 0;border-radius:4px}.email-footer{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999}.btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none}.star{color:#f59e0b;font-size:18px}</style></head>
<body><div class="email-wrap">
<div class="email-header"><h1>&#11088; <?php esc_html_e( 'New Review!', 'mauriel-service-directory' ); ?></h1></div>
<div class="email-body">
<p><?php printf( esc_html__( 'Hi %s,', 'mauriel-service-directory' ), esc_html( $owner_name ) ); ?></p>
<p><?php printf( esc_html__( '%s left a review on "%s".', 'mauriel-service-directory' ), esc_html( $reviewer_name ), esc_html( $listing_name ) ); ?></p>
<div class="review-box">
<p><?php for ( $i = 1; $i <= 5; $i++ ) echo '<span class="star">' . ($i <= $rating ? '&#9733;' : '&#9734;') . '</span>'; ?></p>
<p>"<?php echo esc_html( $review_text ); ?>"</p>
</div>
<p style="text-align:center"><a href="<?php echo esc_url( add_query_arg('tab','reviews',$dashboard_url) ); ?>" class="btn"><?php esc_html_e( 'Respond to Review', 'mauriel-service-directory' ); ?></a></p>
</div>
<div class="email-footer"><?php echo esc_html( get_bloginfo('name') ); ?></div>
</div></body></html>
