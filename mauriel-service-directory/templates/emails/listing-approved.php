<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title><?php esc_html_e( 'Listing Approved', 'mauriel-service-directory' ); ?></title>
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}.email-wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:#2563eb;color:#fff;padding:30px;text-align:center}.email-body{padding:30px}.email-footer{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999}.btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold}</style></head>
<body><div class="email-wrap">
<div class="email-header"><h1><?php echo esc_html( get_bloginfo('name') ); ?></h1></div>
<div class="email-body">
<h2>&#127881; <?php esc_html_e( 'Your listing is approved!', 'mauriel-service-directory' ); ?></h2>
<p><?php printf( esc_html__( 'Hi %s,', 'mauriel-service-directory' ), esc_html( $owner_name ) ); ?></p>
<p><?php printf( esc_html__( 'Great news! Your listing "%s" has been approved and is now live on our directory.', 'mauriel-service-directory' ), esc_html( $listing_name ) ); ?></p>
<p style="text-align:center;margin:30px 0">
<a href="<?php echo esc_url( $listing_url ); ?>" class="btn"><?php esc_html_e( 'View Your Listing', 'mauriel-service-directory' ); ?></a>&nbsp;&nbsp;
<a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn" style="background:#1e40af"><?php esc_html_e( 'Go to Dashboard', 'mauriel-service-directory' ); ?></a>
</p>
<p><?php esc_html_e( 'Start receiving leads and growing your business!', 'mauriel-service-directory' ); ?></p>
</div>
<div class="email-footer"><?php echo esc_html( get_bloginfo('name') ); ?> &mdash; <?php echo esc_html( home_url() ); ?></div>
</div></body></html>
