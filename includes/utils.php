<?php
/**
 * Nexus/AI utility functions.
 *
 * @package PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus\Utils;

use WordPress\AiClient\AiClient;

/**
 * Get a dictionary of all top-level categories.
 *
 * @return string The dictionary of categories.
 */
function get_topic_dictionary() {
	$categories = get_categories(
		array(
			'hide_empty' => false,
		)
	);
	$dictionary = array();
	foreach ( $categories as $category ) {
		$dictionary[] = $category->name;
	}
	return 'DICTIONARY: ' . implode( ', ', $dictionary ) . '.\n';
}

/**
 * Refine a user input into a simplified search term.
 *
 * @TODO: Refine this to only use "mini" models.
 *
 * @param string $input The user input.
 * @return string The refined search term.
 */
function refine_search_term( $input, $restrict_to_dictionary = true ) {
	$instructions = 'You are a helpful assistant that extracts and simplifies search terms from user requests. Given a user request, return a concise search term that captures the essence of the request. Avoid including any brand names, like Pew Research Center, or unnecessary words. Keep the search term short, like a person would search on Google.\n';

	if ( $restrict_to_dictionary ) {
		$instructions .= 'Select words that are available in the following dictionary of topics. If the user request does not match any of these topics, return the most relevant word anyway.\n';
		$instructions .= get_topic_dictionary();
	}

	$result = AiClient::prompt( $instructions . ' Users prompt:' . $input )->generateText();
	$result = trim( $result );
	// @TODO: Sanitize the search term to remove any unwanted characters.
	// @TODO: Figure out caching of search terms.
	return $result;
}

/**
 * Refine a user input into a list of up to three topics from the top-level categories.
 *
 * @param string $input The user input.
 * @param int    $number_of_topics The number of topics to return. Default is 3.
 * @return array The list of topics.
 */
function refine_search_term_to_list_of_topics( $input, $number_of_topics = 3 ) {
	$search_term = refine_search_term( $input, true );
	// Get all categories as a comma-separated list, even the children.
	$categories    = get_categories(
		array(
			'hide_empty' => false,
			'number'     => $number_of_topics,
			'search'     => $search_term,
		)
	);
	$category_list = array();
	foreach ( $categories as $category ) {
		$category_list[] = array(
			'name' => $category->name,
			'id'   => $category->term_id,
		);
	}
	return $category_list;
}
