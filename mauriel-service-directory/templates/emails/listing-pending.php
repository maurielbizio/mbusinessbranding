<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0}.email-wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:#2563eb;color:#fff;padding:30px;text-align:center}.email-body{padding:30px}.email-footer{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999}.btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none}</style></head>
<body><div class="email-wrap">
<div class="email-header"><h1><?php echo esc_html( get_bloginfo('name') ); ?> &mdash; <?php esc_html_e( 'Admin', 'mauriel-service-directory' ); ?></h1></div>
<div class="email-body">
<h2><?php esc_html_e( 'New Listing Pending Review', 'mauriel-service-directory' ); ?></h2>
<p><?php printf( esc_html__( 'A new listing "%s" has been submitted and is waiting for your approval.', 'mauriel-service-directory' ), esc_html( $listing_name ) ); ?></p>
<p><?php printf( esc_html__( 'Owner: %s', 'mauriel-service-directory' ), esc_html( $owner_email ) ); ?></p>
<p style="text-align:center;margin:20px 0"><a href="<?php echo esc_url( admin_url('admin.php?page=mauriel-listings') ); ?>" class="btn"><?php esc_html_e( 'Review Listings', 'mauriel-service-directory' ); ?></a></p>
</div>
<div class="email-footer"><?php echo esc_html( get_bloginfo('name') ); ?></div>
</div></body></html>
