<?php
/**
 * Metadata Store and Purge
 *
 * This will go through posts, gather up all the attributes.metadata._nexus, store them in post meta, and then delete the attributes.metadata._nexus from the post.
 *
 * TODO: This is a work in progress!
 *
 * @package PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus;

/**
 * Metadata Store and Purge
 */
class Metadata_Store_And_Purge {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Seek Nexus metadata in post content.
	 *
	 * @param int $post_id The ID of the post to seek nexus metadata in.
	 * @return string|null The nexus metadata found in the post content, or null if no metadata is found.
	 */
	public function seek_nexus_metadata_in_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$post_content = $post->post_content;
		$pattern      = '/{{[^}]*metadata: {[^}]*\'_nexus\': \[[^\]]*\]/';
		preg_match( $pattern, $post_content, $matches );
		if ( count( $matches ) > 0 ) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * Purge nexus data from post content.
	 *
	 * @param int $post_id The ID of the post to purge nexus data from.
	 */
	public function purge_nexus_data_from_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$post_content       = $post->post_content;
		$post_content       = preg_replace( '/\'_nexus\': \[[^\]]*\]/', "'_nexus': []", $post_content );
		$post->post_content = $post_content;
		wp_update_post( $post );
	}

	/**
	 * Store nexus data to post meta.
	 *
	 * @param int $post_id The ID of the post to store nexus data to.
	 */
	public function store_nexus_data_to_post_meta( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$nexus_data = $this->seek_nexus_metadata_in_post( $post_id );
		if ( $nexus_data ) {
			update_post_meta( $post_id, Metadata_Log::$post_meta_key, $nexus_data );
		}
	}

	/**
	 * Store and purge nexus data from post content.
	 */
	public function store_and_purge() {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
			)
		);
		foreach ( $posts as $post ) {
			$this->store_nexus_data_to_post_meta( $post->ID );
			$this->purge_nexus_data_from_post( $post->ID );
		}
	}
}
