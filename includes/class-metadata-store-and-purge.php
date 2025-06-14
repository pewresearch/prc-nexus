<?php
/**
 * Metadata Store and Purge
 *
 * This will go through posts, gather up all the attributes.metadata._copilot, store them in post meta, and then delete the attributes.metadata._copilot from the post.
 *
 * TODO: This is a work in progress!
 *
 * @package PRC Copilot
 */

namespace PRC\Platform\Copilot;

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
	 * Seek copilot metadata in post content.
	 *
	 * @param int $post_id The ID of the post to seek copilot metadata in.
	 * @return string|null The copilot metadata found in the post content, or null if no metadata is found.
	 */
	public function seek_copilot_metadata_in_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$post_content = $post->post_content;
		$pattern      = '/{{[^}]*metadata: {[^}]*\'_copilot\': \[[^\]]*\]/';
		preg_match( $pattern, $post_content, $matches );
		if ( count( $matches ) > 0 ) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * Purge copilot data from post content.
	 *
	 * @param int $post_id The ID of the post to purge copilot data from.
	 */
	public function purge_copilot_data_from_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$post_content       = $post->post_content;
		$post_content       = preg_replace( '/\'_copilot\': \[[^\]]*\]/', "'_copilot': []", $post_content );
		$post->post_content = $post_content;
		wp_update_post( $post );
	}

	/**
	 * Store copilot data to post meta.
	 *
	 * @param int $post_id The ID of the post to store copilot data to.
	 */
	public function store_copilot_data_to_post_meta( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		$copilot_data = $this->seek_copilot_metadata_in_post( $post_id );
		if ( $copilot_data ) {
			update_post_meta( $post_id, Post_Meta::$post_meta_key, $copilot_data );
		}
	}

	/**
	 * Store and purge copilot data from post content.
	 */
	public function store_and_purge() {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
			)
		);
		foreach ( $posts as $post ) {
			$this->store_copilot_data_to_post_meta( $post->ID );
			$this->purge_copilot_data_from_post( $post->ID );
		}
	}
}
