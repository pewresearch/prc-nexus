<?php
// Create a WP CLI command that will Trending_News_Analysis->run() with args from the command line.
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WPCOM_VIP_CLI_Command' ) ) {
	/**
	 * Class Trending_News_Analysis_Command
	 */
	class Trending_News_Analysis_Command extends \WPCOM_VIP_CLI_Command {
		/**
		 * Run the trending news analysis.
		 *
		 * ## OPTIONS
		 *
		 * [--category=<category>]
		 * : News category (e.g., nation, world, business, technology, sports, science, health, entertainment). Default: nation
		 *
		 * [--total=<total>]
		 * : Number of articles to fetch (1-100). Default: 5
		 *
		 * [--from=<from>]
		 * : Start date in YYYY-MM-DD format (defaults to yesterday).
		 *
		 * [--to=<to>]
		 * : End date in YYYY-MM-DD format (defaults to today).
		 *
		 * [--query=<query>]
		 * : Search query to filter articles by keywords.
		 *
		 * [--output_format=<output_format>]
		 * : Output format: json (structured data) or markdown (formatted text). Default: markdown
		 *
		 * ## EXAMPLES
		 *
		 *     wp trending-news-analysis run --category=nation --total=5 --output_format=markdown
		 *
		 * @when after_wp_load
		 *
		 * @param array $args The positional arguments.
		 * @param array $assoc_args The associative arguments.
		 */
		public function run( $args, $assoc_args ) {
			$ability = new \PRC\Platform\Nexus\Abilities\Trending_News_Analysis( null );

			$input = wp_parse_args(
				$assoc_args,
				array(
					'category'      => 'nation',
					'total'         => 5,
					'from'          => '',
					'to'            => '',
					'query'         => '',
					'output_format' => 'markdown',
				)
			);

			$result = $ability->run( $input );

			if ( isset( $result['error'] ) ) {
				WP_CLI::error( $result['error'] );
			} else {
				if ( 'json' === $input['output_format'] ) {
					WP_CLI::line( $result['response'] );
				} else {
					// For markdown, output as-is.
					WP_CLI::line( $result['response'] );
				}
				WP_CLI::success( 'Trending news analysis completed.' );
			}
		}
	}
	\WP_CLI::add_command( 'prc trending-news-analysis', __NAMESPACE__ . '\Trending_News_Analysis_Command' );
}
