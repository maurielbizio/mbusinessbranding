<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0}.email-wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:#2563eb;color:#fff;padding:30px;text-align:center}.email-body{padding:30px}.email-footer{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999}.btn{display:inline-block;background:#2563eb;color:#fff;padding:15px 30px;border-radius:6px;text-decoration:none;font-size:16px}.expiry-notice{background:#fef3c7;border:1px solid #f59e0b;padding:10px 15px;border-radius:4px;font-size:14px}</style></head>
<body><div class="email-wrap">
<div class="email-header"><h1><?php echo esc_html( get_bloginfo('name') ); ?></h1></div>
<div class="email-body">
<h2><?php esc_html_e( 'Claim Your Business Listing', 'mauriel-service-directory' ); ?></h2>
<p><?php printf( esc_html__( 'You requested to claim the business listing for "%s".', 'mauriel-service-directory' ), esc_html( $business_name ) ); ?></p>
<p><?php esc_html_e( 'Click the button below to verify your ownership and take control of your listing.', 'mauriel-service-directory' ); ?></p>
<p style="text-align:center;margin:30px 0"><a href="<?php echo esc_url( $verify_url ); ?>" class="btn"><?php esc_html_e( 'Verify & Claim Listing', 'mauriel-service-directory' ); ?></a></p>
<div class="expiry-notice">&#9203; <?php printf( esc_html__( 'This link expires in %s. If you did not request this, please ignore this email.', 'mauriel-service-directory' ), esc_html( $expires_in ) ); ?></div>
<p style="margin-top:20px;font-size:12px;color:#999"><?php esc_html_e( 'Or copy this URL:', 'mauriel-service-directory' ); ?><br><a href="<?php echo esc_url( $verify_url ); ?>"><?php echo esc_html( $verify_url ); ?></a></p>
</div>
<div class="email-footer"><?php echo esc_html( get_bloginfo('name') ); ?></div>
</div></body></html>
