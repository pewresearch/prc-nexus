/**
 * Main entry point for the Abilities Dashboard
 *
 * This file initializes the React application and renders it to the DOM.
 */

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { App } from './components/App';

// Import styles
import './style.scss';

/**
 * Initialize the application when DOM is ready
 */
domReady(() => {
	const root = document.getElementById('ai-studio-root');
	if (root) {
		createRoot(root).render(<App />);
	}
});
