<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Onboarding
 *
 * Tracks multi-step registration progress for users.
 */
class Mauriel_Onboarding {

    /**
     * Returns the current registration step for a user based on stored meta.
     *
     * Returns 1 if no steps complete, 2 if step 1 is done, 3 if step 2 is done,
     * or 3 (final) if all steps are done.
     *
     * @param int $user_id WP user ID.
     *
     * @return int Current step (1, 2, or 3).
     */
    public static function get_current_step( int $user_id ): int {
        if ( ! $user_id ) {
            return 1;
        }

        $step1 = (bool) get_user_meta( $user_id, '_mauriel_step_1_complete', true );
        $step2 = (bool) get_user_meta( $user_id, '_mauriel_step_2_complete', true );
        $step3 = (bool) get_user_meta( $user_id, '_mauriel_step_3_complete', true );

        if ( $step3 ) {
            return 3;
        }

        if ( $step2 ) {
            return 3;
        }

        if ( $step1 ) {
            return 2;
        }

        return 1;
    }

    /**
     * Marks a registration step as complete for a user.
     *
     * @param int $user_id WP user ID.
     * @param int $step    Step number (1, 2, or 3).
     *
     * @return void
     */
    public static function mark_step_complete( int $user_id, int $step ): void {
        if ( ! $user_id || $step < 1 || $step > 3 ) {
            return;
        }

        update_user_meta( $user_id, "_mauriel_step_{$step}_complete", '1' );
        update_user_meta( $user_id, "_mauriel_step_{$step}_complete_time", current_time( 'mysql' ) );
    }

    /**
     * Resets all registration progress user meta for a given user.
     *
     * @param int $user_id WP user ID.
     *
     * @return void
     */
    public static function reset_registration( int $user_id ): void {
        if ( ! $user_id ) {
            return;
        }

        delete_user_meta( $user_id, '_mauriel_step_1_complete' );
        delete_user_meta( $user_id, '_mauriel_step_1_complete_time' );
        delete_user_meta( $user_id, '_mauriel_step_2_complete' );
        delete_user_meta( $user_id, '_mauriel_step_2_complete_time' );
        delete_user_meta( $user_id, '_mauriel_step_3_complete' );
        delete_user_meta( $user_id, '_mauriel_step_3_complete_time' );
        delete_user_meta( $user_id, '_mauriel_pending_listing_id' );
    }

    /**
     * Returns the redirect URL for a given step.
     *
     * @param int $step Step number (1, 2, or 3).
     *
     * @return string Full URL for the step.
     */
    public static function get_redirect_url( int $step ): string {
        $register_page_id = (int) get_option( 'mauriel_register_page_id', 0 );
        $base_url         = $register_page_id ? get_permalink( $register_page_id ) : home_url( '/register/' );

        switch ( $step ) {
            case 1:
                return add_query_arg( 'step', '1', $base_url );

            case 2:
                return add_query_arg( 'step', '2', $base_url );

            case 3:
                return add_query_arg( 'step', '3', $base_url );

            default:
                return $base_url;
        }
    }
}
