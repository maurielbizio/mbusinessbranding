<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0}.email-wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}.email-header{background:#10b981;color:#fff;padding:30px;text-align:center}.email-body{padding:30px}.lead-detail{background:#f9f9f9;border-left:4px solid #10b981;padding:15px;margin:15px 0;border-radius:4px}.email-footer{background:#f9f9f9;padding:20px;text-align:center;font-size:12px;color:#999}.btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none}</style></head>
<body><div class="email-wrap">
<div class="email-header"><h1>&#128229; <?php esc_html_e( 'New Lead!', 'mauriel-service-directory' ); ?></h1></div>
<div class="email-body">
<p><?php printf( esc_html__( 'Hi %s,', 'mauriel-service-directory' ), esc_html( $owner_name ) ); ?></p>
<p><?php printf( esc_html__( 'You have a new %s lead for "%s".', 'mauriel-service-directory' ), esc_html( $lead_type ), esc_html( $listing_name ) ); ?></p>
<div class="lead-detail">
<?php if ( $lead_name ) : ?><p><strong><?php esc_html_e( 'Name:', 'mauriel-service-directory' ); ?></strong> <?php echo esc_html( $lead_name ); ?></p><?php endif; ?>
<?php if ( $lead_email ) : ?><p><strong><?php esc_html_e( 'Email:', 'mauriel-service-directory' ); ?></strong> <a href="mailto:<?php echo esc_attr( $lead_email ); ?>"><?php echo esc_html( $lead_email ); ?></a></p><?php endif; ?>
<?php if ( $lead_phone ) : ?><p><strong><?php esc_html_e( 'Phone:', 'mauriel-service-directory' ); ?></strong> <?php echo esc_html( $lead_phone ); ?></p><?php endif; ?>
<?php if ( $lead_message ) : ?><p><strong><?php esc_html_e( 'Message:', 'mauriel-service-directory' ); ?></strong><br><?php echo nl2br( esc_html( $lead_message ) ); ?></p><?php endif; ?>
</div>
<p style="text-align:center"><a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn"><?php esc_html_e( 'View in Dashboard', 'mauriel-service-directory' ); ?></a></p>
</div>
<div class="email-footer"><?php echo esc_html( get_bloginfo('name') ); ?></div>
</div></body></html>
