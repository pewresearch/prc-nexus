<?php
/**
 * Generate Tabular Data tool.
 *
 * @package PRC\Platform\Nexus\Abilities
 */

namespace PRC\Platform\Nexus\Abilities;

use WordPress\AiClient\AiClient;

/**
 * Class Generate_Tabular_Data
 */
class Notes {

	/**
	 * Ability name.
	 *
	 * @var string
	 */
	public static $ability_name = 'prc-nexus/notes';

	/**
	 * Blocks that are allowed to use this ability.
	 *
	 * @var array
	 */
	public static $allowed_blocks = array();

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'wp_abilities_api_init', $this, 'register_ability' );
	}

	/**
	 * Register the notes ability with WP abilities api.
	 *
	 * @hook abilities_api_init
	 */
	public function register_ability() {
		// Check if prc-notes plugin is active
		if ( ! is_plugin_active( 'prc-notes/prc-notes.php' ) ) {
			return;
		}
		$read_ability = wp_register_ability(
			self::$ability_name . '-read',
			array(
				'label'               => __( 'Notes', 'prc-nexus' ),
				'description'         => __( 'Allows reading a users private notes.', 'prc-nexus' ),
				'category'            => 'data-retrieval',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array(
							'type'        => 'string',
							'description' => 'The ID of the note to retrieve',
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'error' => array(
							'type'        => 'string',
							'description' => 'An error message, if any.',
						),
						'note'  => array(
							'type'        => 'array',
							'description' => 'An array of the note ID, content, and title, if successful.',
						),
					),
				),
				'execute_callback'    => array( $this, 'read_note' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'   => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest'  => true,
					'allowedBlocks' => self::$allowed_blocks,
				),
			)
		);

		$edit_ability = wp_register_ability(
			self::$ability_name . '-edit',
			array(
				'label'               => __( 'Notes', 'prc-nexus' ),
				'description'         => __( 'Allows editing, creating, and reading notes.', 'prc-nexus' ),
				'category'            => 'data-retrieval',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'      => array(
							'type'        => 'string',
							'description' => 'The ID of the note to retrieve or edit.',
						),
						'content' => array(
							'type'        => 'string',
							'description' => 'The content of the note to create or update.',
						),
						'title'   => array(
							'type'        => 'string',
							'description' => 'The title of the note to create or update.',
						),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'error' => array(
							'type'        => 'string',
							'description' => 'An error message, if any.',
						),
						'note'  => array(
							'type'        => 'array',
							'description' => 'An array of the note ID, content, and title, if successful.',
						),
					),
				),
				'execute_callback'    => array( $this, 'edit_note' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'   => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest'  => true,
					'allowedBlocks' => self::$allowed_blocks,
				),
			)
		);
	}

	/**
	 * Interact with a note: create, read, or update.
	 *
	 * @param array $input The input data.
	 * @return array The output data.
	 */
	public function edit_note( $input ) {
		$output = array();

		if ( isset( $input['id'] ) ) {
			$note_id = intval( $input['id'] );
			$note    = get_post( $note_id );
			if ( ! $note || 'note' !== $note->post_type ) {
				$output['error'] = 'Note not found.';
				return $output;
			}
			// Check ownership.
			if ( get_current_user_id() !== intval( $note->post_author ) ) {
				$output['error'] = 'You do not have permission to access this note.';
				return $output;
			}

			// Update note if content or title provided.
			$updated   = false;
			$note_data = array(
				'ID' => $note_id,
			);
			if ( isset( $input['content'] ) ) {
				$note_data['post_content'] = sanitize_textarea_field( $input['content'] );
				$updated                   = true;
			}
			if ( isset( $input['title'] ) ) {
				$note_data['post_title'] = sanitize_text_field( $input['title'] );
				$updated                 = true;
			}
			if ( $updated ) {
				wp_update_post( $note_data );
			}

			// Return the note data.
			$output['note'] = array(
				'id'      => strval( $note->ID ),
				'content' => $note->post_content,
				'title'   => $note->post_title,
			);
			return $output;
		}
		// Create a new note.
		$note_data = array(
			'post_type'    => 'note',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_title'   => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : 'Untitled Note',
			'post_content' => isset( $input['content'] ) ? sanitize_textarea_field( $input['content'] ) : '',
		);
		$note_id   = wp_insert_post( $note_data );
		if ( is_wp_error( $note_id ) ) {
			$output['error'] = 'Failed to create note.';
			return $output;
		}
		$output['note'] = array(
			'id'      => strval( $note_id ),
			'content' => $note_data['post_content'],
			'title'   => $note_data['post_title'],
		);
		return $output;
	}

	/**
	 * Read a note.
	 *
	 * @param array $input The input data.
	 * @return array The output data.
	 */
	public function read_note( $input ) {
		$output = array();
		if ( isset( $input['id'] ) ) {
			$note_id = intval( $input['id'] );
			$note    = get_post( $note_id );
			if ( ! $note || 'note' !== $note->post_type ) {
				$output['error'] = 'Note not found.';
				return $output;
			}
			// Check ownership.
			if ( get_current_user_id() !== intval( $note->post_author ) ) {
				$output['error'] = 'You do not have permission to access this note.';
				return $output;
			}

			// Return the note data.
			$output['note'] = array(
				'id'      => strval( $note->ID ),
				'content' => $note->post_content,
				'title'   => $note->post_title,
			);
			return $output;
		} else {
			$output['error'] = 'No note ID provided.';
			return $output;
		}
	}
}
