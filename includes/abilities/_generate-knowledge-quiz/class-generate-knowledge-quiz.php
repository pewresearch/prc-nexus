<?php
/**
 * Generate Knowledge Quiz Tool
 *
 * @package PRC\Platform\Nexus\Abilities
 */

namespace PRC\Platform\Nexus\Abilities;

/**
 * Class Generate_Knowledge_Quiz
 */
class Generate_Knowledge_Quiz {

	/**
	 * The name of the feature.
	 *
	 * @var string
	 */
	protected static $feature_name = 'generate-knowledge-quiz';

	/**
	 * The loader.
	 *
	 * @var mixed
	 */
	protected $loader;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param mixed $loader The loader.
	 */
	public function __construct( $loader ) {
		$this->loader = $loader;
		$this->init();
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		if ( null !== $this->loader ) {
			// $this->loader->add_action( 'wp_feature_api_init', $this, 'register_feature' );
			// $this->loader->add_action( 'ai_services_model_params', $this, 'system_instruction', 10, 2 );
		}
	}

	/**
	 * Get the system instruction for generating knowledge quizzes.
	 *
	 * @return string The system instruction.
	 */
	protected function get_system_instruction() {
		return '{
  "task": "Generate WordPress Gutenberg block markup for Pew Research Center knowledge quizzes",
  "output_format": "HTML markup only, no explanations",
  "structure": {
    "root_block": "prc-quiz/controller",
    "required_sections": [
      {
        "name": "introduction_page",
        "block": "prc-quiz/page",
        "content": ["description", "start_button"],
        "title": "Introduction"
      },
      {
        "name": "question_pages",
        "block": "prc-quiz/page",
        "content": ["question_block", "answer_blocks", "navigation_buttons"],
        "title_format": "Question X of Y"
      },
      {
        "name": "results_section",
        "block": "prc-quiz/results",
        "content": ["histogram", "result_table"]
      }
    ]
  },
  "formatting_rules": {
    "uuids": {
      "format": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
      "description": "MD5-style random UUIDs for all blocks"
    },
    "answers": {
      "correct": {
        "points": 1,
        "correct": true
      },
      "incorrect": {
        "correct": false,
        "points": "omit"
      },
      "count_per_question": "3-4 answers, exactly 1 correct"
    },
    "buttons": {
      "intro": "prc-quiz-start-button",
      "questions": "prc-quiz-next-page-button + prc-quiz-previous-page-button + prc-quiz-reset-button",
      "final_question": "prc-quiz-submit-button + prc-quiz-previous-page-button + prc-quiz-reset-button"
    }
  },
  "template": "<!-- wp:prc-quiz/controller {\"demoBreakLabels\":\"[\\\"% answered correctly\\\"]\"} -->\\n<div class=\"wp-block-prc-quiz-controller\">\\n  <!-- wp:prc-quiz/pages -->\\n  <div class=\"wp-block-prc-quiz-pages\">\\n    <!-- INTRO PAGE -->\\n    <!-- wp:prc-quiz/page {\"title\":\"Introduction\",\"uuid\":\"UUID\"} -->\\n    <div class=\"wp-block-prc-quiz-page\">\\n      <!-- wp:paragraph --><p>Quiz description...</p><!-- /wp:paragraph -->\\n      <!-- wp:buttons {\"className\":\"prc-quiz-start-button-wrapper\"} -->\\n      <div class=\"wp-block-buttons prc-quiz-start-button-wrapper\">\\n        <!-- wp:button {\"className\":\"prc-quiz-start-button\",\"width\":100} -->\\n        <div class=\"wp-block-button prc-quiz-start-button\">\\n          <a class=\"wp-block-button__link\">Start</a>\\n        </div>\\n        <!-- /wp:button -->\\n      </div>\\n      <!-- /wp:buttons -->\\n    </div>\\n    <!-- /wp:prc-quiz/page -->\\n    <!-- QUESTION PAGES -->\\n    <!-- wp:prc-quiz/page {\"title\":\"Question 1 of X\",\"uuid\":\"UUID\"} -->\\n    <div class=\"wp-block-prc-quiz-page\">\\n      <!-- wp:prc-quiz/question {\"question\":\"Question text\",\"demoBreakValues\":\"[\\\"45\\\"]\",\"uuid\":\"UUID\"} -->\\n      <div class=\"wp-block-prc-quiz-question\">\\n        <!-- wp:paragraph {\"fontSize\":\"medium\"} --><p class=\"has-medium-font-size\"></p><!-- /wp:paragraph -->\\n        <!-- CORRECT -->\\n        <!-- wp:prc-quiz/answer {\"answer\":\"Correct answer\",\"points\":1,\"correct\":true,\"uuid\":\"UUID\"} -->\\n        <div class=\"wp-block-prc-quiz-answer\"><p></p></div>\\n        <!-- /wp:prc-quiz/answer -->\\n        <!-- INCORRECT -->\\n        <!-- wp:prc-quiz/answer {\"answer\":\"Wrong answer\",\"correct\":false,\"uuid\":\"UUID\"} -->\\n        <div class=\"wp-block-prc-quiz-answer\"><p></p></div>\\n        <!-- /wp:prc-quiz/answer -->\\n      </div>\\n      <!-- /wp:prc-quiz/question -->\\n      <!-- NAVIGATION -->\\n      <!-- wp:buttons {\"className\":\"prc-quiz-next-page-button-wrapper\"} -->\\n      <div class=\"wp-block-buttons\">\\n        <!-- wp:button {\"className\":\"prc-quiz-next-page-button\"} -->\\n        <div class=\"wp-block-button\"><a>Next</a></div>\\n        <!-- /wp:button -->\\n      </div>\\n      <!-- /wp:buttons -->\\n    </div>\\n    <!-- /wp:prc-quiz/page -->\\n  </div>\\n  <!-- /wp:prc-quiz/pages -->\\n  <!-- RESULTS -->\\n  <!-- wp:prc-quiz/results -->\\n  <div class=\"wp-block-prc-quiz-results\">\\n    <!-- wp:prc-quiz/result-histogram {\"histogramData\":\"[{\\\"x\\\":\\\"0\\\",\\\"y\\\":\\\"15\\\"},{\\\"x\\\":\\\"1\\\",\\\"y\\\":\\\"25\\\"}]\"} -->\\n    <!-- wp:prc-quiz/result-table /-->\\n  </div>\\n  <!-- /wp:prc-quiz/results -->\\n</div>\\n<!-- /wp:prc-quiz/controller -->",
  "generation_requirements": [
    "Generate unique UUIDs for every block",
    "Create 3-4 answers per question with only 1 correct",
    "Use realistic percentage values for demoBreakValues",
    "Generate histogram data matching question count",
    "Include engaging, educational content appropriate for topic"
  ]
}';
	}

	/**
	 * System instruction for the generate knowledge quiz feature.
	 *
	 * @param array  $params The input parameters.
	 * @param object $service The AI service instance.
	 * @return string The system instruction.
	 */
	public function system_instruction( $params, $service ) {
		if ( self::$feature_name === $params['feature'] ) {
			$params['systemInstruction'] .= $this->get_system_instruction();
		}
		return $params;
	}

	/**
	 * Register the feature with AI Services.
	 *
	 * This method will be implemented later.
	 */
	public function register_feature() {
		// TODO: Implementation will be added later.
	}

	/**
	 * Generate knowledge quiz handler.
	 *
	 * This method will be implemented later.
	 *
	 * @param array $input_params The input parameters.
	 */
	public function generate_knowledge_quiz( $input_params ) {
		// TODO: Implementation will be added later.
	}
}
