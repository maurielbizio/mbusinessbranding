<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_SEO
 *
 * Outputs on-page SEO meta tags and JSON-LD structured data (LocalBusiness
 * schema) for individual mauriel_listing posts.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_SEO {

	/**
	 * Registers all WordPress hooks.
	 */
	public function __construct() {
		add_action( 'wp_head',              array( $this, 'output_meta_tags' ), 5 );
		add_filter( 'document_title_parts', array( $this, 'filter_title' ),    10, 1 );
	}

	// -------------------------------------------------------------------------
	// Meta tags
	// -------------------------------------------------------------------------

	/**
	 * Outputs SEO meta tags and optionally JSON-LD for listing single pages.
	 *
	 * @return void
	 */
	public function output_meta_tags() {
		if ( ! is_singular( 'mauriel_listing' ) ) {
			return;
		}

		$listing_id = get_the_ID();
		if ( ! $listing_id ) {
			return;
		}

		// Record a page view (session-deduplicated).
		if ( class_exists( 'Mauriel_Analytics' ) ) {
			Mauriel_Analytics::record_view( $listing_id );
		}

		// ------------------------------------------------------------------
		// Collect listing meta.
		// ------------------------------------------------------------------
		$meta = $this->get_listing_meta( $listing_id );

		// ------------------------------------------------------------------
		// SEO title.
		// ------------------------------------------------------------------
		$seo_title = $this->build_title( $meta );

		// ------------------------------------------------------------------
		// Meta description.
		// ------------------------------------------------------------------
		$description = $meta['short_description'];
		if ( '' === $description ) {
			// Fall back to a truncated version of the main description.
			$description = wp_trim_words( $meta['description'], 30, '…' );
		}

		// ------------------------------------------------------------------
		// OG image — prefer cover, then logo, then placeholder.
		// ------------------------------------------------------------------
		$og_image = '';
		if ( class_exists( 'Mauriel_Media' ) ) {
			$og_image = Mauriel_Media::get_cover_url( $listing_id, 'large' );
			if ( ! $og_image ) {
				$og_image = Mauriel_Media::get_logo_url( $listing_id, 'medium' );
			}
			if ( ! $og_image ) {
				$og_image = Mauriel_Media::get_placeholder_url();
			}
		}

		// ------------------------------------------------------------------
		// Noindex for pending / unapproved listings.
		// ------------------------------------------------------------------
		$approval_status = $meta['approval_status'];
		$noindex_pending = (bool) get_option( 'mauriel_noindex_pending', 1 );

		if ( $noindex_pending && 'approved' !== $approval_status ) {
			echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
		}

		// ------------------------------------------------------------------
		// Output tags.
		// ------------------------------------------------------------------
		$canonical = get_permalink( $listing_id );

		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";

		// Open Graph.
		echo '<meta property="og:title" content="'       . esc_attr( $seo_title )   . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta property="og:url" content="'         . esc_url( $canonical )    . '" />' . "\n";
		echo '<meta property="og:type" content="business.local" />'                           . "\n";
		echo '<meta property="og:site_name" content="'   . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

		if ( $og_image ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		}

		// Twitter card.
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="'       . esc_attr( $seo_title )   . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
		if ( $og_image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		}

		// ------------------------------------------------------------------
		// JSON-LD.
		// ------------------------------------------------------------------
		if ( (bool) get_option( 'mauriel_schema_enabled', 1 ) ) {
			$this->output_schema_json_ld( $listing_id, $meta, $og_image );
		}
	}

	// -------------------------------------------------------------------------
	// JSON-LD / Schema
	// -------------------------------------------------------------------------

	/**
	 * Outputs a LocalBusiness JSON-LD script block for a listing.
	 *
	 * @param  int    $listing_id
	 * @param  array  $meta
	 * @param  string $og_image
	 * @return void
	 */
	public function output_schema_json_ld( $listing_id, array $meta, $og_image = '' ) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'LocalBusiness',
		);

		// Name.
		$schema['name'] = get_the_title( $listing_id );

		// Description.
		if ( '' !== $meta['description'] ) {
			$schema['description'] = wp_strip_all_tags( $meta['description'] );
		}

		// Image.
		if ( $og_image ) {
			$schema['image'] = esc_url_raw( $og_image );
		}

		// URL.
		$business_url = $meta['website'];
		if ( '' !== $business_url ) {
			$schema['url'] = esc_url_raw( $business_url );
		}

		// Telephone.
		if ( '' !== $meta['phone'] ) {
			$schema['telephone'] = $meta['phone'];
		}

		// Address.
		$address_parts = array_filter( array(
			'streetAddress'   => $meta['address'],
			'addressLocality' => $meta['city'],
			'addressRegion'   => $meta['state'],
			'postalCode'      => $meta['zip'],
			'addressCountry'  => $meta['country'] ? $meta['country'] : 'US',
		) );

		if ( ! empty( $address_parts ) ) {
			$schema['address'] = array_merge(
				array( '@type' => 'PostalAddress' ),
				$address_parts
			);
		}

		// Geo coordinates.
		$lat = (float) $meta['lat'];
		$lng = (float) $meta['lng'];
		if ( $lat && $lng ) {
			$schema['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $lat,
				'longitude' => $lng,
			);
		}

		// Opening hours specification.
		$hours = $meta['hours'];
		if ( ! empty( $hours ) && is_array( $hours ) ) {
			$day_map = array(
				'monday'    => 'Monday',
				'tuesday'   => 'Tuesday',
				'wednesday' => 'Wednesday',
				'thursday'  => 'Thursday',
				'friday'    => 'Friday',
				'saturday'  => 'Saturday',
				'sunday'    => 'Sunday',
			);

			$hours_specs = array();
			foreach ( $hours as $day_slug => $day_hours ) {
				if ( empty( $day_hours['open'] ) || empty( $day_hours['opens'] ) || empty( $day_hours['closes'] ) ) {
					continue;
				}
				if ( isset( $day_map[ $day_slug ] ) ) {
					$hours_specs[] = array(
						'@type'     => 'OpeningHoursSpecification',
						'dayOfWeek' => 'https://schema.org/' . $day_map[ $day_slug ],
						'opens'     => $day_hours['opens'],
						'closes'    => $day_hours['closes'],
					);
				}
			}

			if ( ! empty( $hours_specs ) ) {
				$schema['openingHoursSpecification'] = $hours_specs;
			}
		}

		// Aggregate rating.
		$avg_rating    = (float) $meta['avg_rating'];
		$review_count  = (int)   $meta['review_count'];

		if ( $review_count > 0 && $avg_rating > 0 ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $avg_rating,
				'reviewCount' => $review_count,
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		// Price range.
		if ( '' !== $meta['price_range'] ) {
			$schema['priceRange'] = $meta['price_range'];
		}

		// Category → additional type.
		$categories = wp_get_post_terms( $listing_id, 'mauriel_category', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$schema['@type'] = $this->get_schema_type( $categories[0] );
		}

		echo '<script type="application/ld+json">'
			. wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. '</script>' . "\n";
	}

	// -------------------------------------------------------------------------
	// Title filter
	// -------------------------------------------------------------------------

	/**
	 * Replaces the document title for listing pages using the configured
	 * SEO title pattern.
	 *
	 * @param  array $title_parts  WordPress title parts array.
	 * @return array
	 */
	public function filter_title( array $title_parts ) {
		if ( ! is_singular( 'mauriel_listing' ) ) {
			return $title_parts;
		}

		$listing_id = get_the_ID();
		if ( ! $listing_id ) {
			return $title_parts;
		}

		$meta      = $this->get_listing_meta( $listing_id );
		$seo_title = $this->build_title( $meta );

		$title_parts['title'] = $seo_title;

		return $title_parts;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Collects all relevant meta for a listing into a flat associative array.
	 *
	 * @param  int $listing_id
	 * @return array
	 */
	private function get_listing_meta( $listing_id ) {
		$hours_raw = get_post_meta( $listing_id, '_mauriel_hours', true );
		$hours     = is_array( $hours_raw ) ? $hours_raw : array();

		return array(
			'business_name'     => get_the_title( $listing_id ),
			'description'       => (string) get_post_meta( $listing_id, '_mauriel_description',       true ),
			'short_description' => (string) get_post_meta( $listing_id, '_mauriel_short_description', true ),
			'phone'             => (string) get_post_meta( $listing_id, '_mauriel_phone',             true ),
			'website'           => (string) get_post_meta( $listing_id, '_mauriel_website',           true ),
			'address'           => (string) get_post_meta( $listing_id, '_mauriel_address',           true ),
			'city'              => (string) get_post_meta( $listing_id, '_mauriel_city',              true ),
			'state'             => (string) get_post_meta( $listing_id, '_mauriel_state',             true ),
			'zip'               => (string) get_post_meta( $listing_id, '_mauriel_zip',               true ),
			'country'           => (string) get_post_meta( $listing_id, '_mauriel_country',           true ),
			'lat'               => (float)  get_post_meta( $listing_id, '_mauriel_lat',              true ),
			'lng'               => (float)  get_post_meta( $listing_id, '_mauriel_lng',              true ),
			'price_range'       => (string) get_post_meta( $listing_id, '_mauriel_price_range',       true ),
			'avg_rating'        => (float)  get_post_meta( $listing_id, '_mauriel_avg_rating',       true ),
			'review_count'      => (int)    get_post_meta( $listing_id, '_mauriel_review_count',     true ),
			'approval_status'   => (string) get_post_meta( $listing_id, '_mauriel_approval_status',  true ),
			'hours'             => $hours,
			'category'          => $this->get_primary_category( $listing_id ),
		);
	}

	/**
	 * Returns the name of the first assigned mauriel_category term.
	 *
	 * @param  int $listing_id
	 * @return string
	 */
	private function get_primary_category( $listing_id ) {
		$terms = wp_get_post_terms( $listing_id, 'mauriel_category', array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}
		return (string) $terms[0];
	}

	/**
	 * Builds the SEO title string by replacing tokens in the configured pattern.
	 *
	 * Default pattern: {business_name} | {category} in {city}, {state}
	 *
	 * @param  array $meta
	 * @return string
	 */
	private function build_title( array $meta ) {
		$pattern = (string) get_option(
			'mauriel_seo_title_pattern',
			'{business_name} | {category} in {city}, {state}'
		);

		$replacements = array(
			'{business_name}' => $meta['business_name'],
			'{category}'      => $meta['category'],
			'{city}'          => $meta['city'],
			'{state}'         => $meta['state'],
		);

		$title = str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$pattern
		);

		// Collapse any double separators left by empty tokens.
		$title = preg_replace( '/\s*\|\s*\|+\s*/', ' | ', $title );
		$title = preg_replace( '/\s*,\s*,+\s*/',   ', ',   $title );
		$title = preg_replace( '/\s+in\s*,/',       ' in',  $title );
		$title = trim( $title, ' |,' );

		return $title;
	}

	/**
	 * Attempts to map a category name to a more specific schema.org type.
	 *
	 * Falls back to 'LocalBusiness' if no match is found.
	 *
	 * @param  string $category_name
	 * @return string  Schema.org @type value.
	 */
	private function get_schema_type( $category_name ) {
		$map = array(
			'plumbing'      => 'Plumber',
			'electrician'   => 'Electrician',
			'hvac'          => 'HVACBusiness',
			'roofing'       => 'RoofingContractor',
			'landscaping'   => 'LandscapingBusiness',
			'cleaning'      => 'HousePainter',
			'painter'       => 'HousePainter',
			'locksmith'     => 'Locksmith',
			'moving'        => 'MovingCompany',
			'pest control'  => 'PestControlBusiness',
			'pool'          => 'EntertainmentBusiness',
			'garage door'   => 'HomeAndConstructionBusiness',
			'handyman'      => 'GeneralContractor',
			'contractor'    => 'GeneralContractor',
		);

		$lower = strtolower( $category_name );
		foreach ( $map as $keyword => $type ) {
			if ( false !== strpos( $lower, $keyword ) ) {
				return $type;
			}
		}

		return 'LocalBusiness';
	}
}
