/**
 * External Dependencies
 */
import React from 'react';

/**
 * Internal Dependencies
 */

export function TrackingMessage() {
	return(
		<p style={{ fontSize: '0.8em', color: 'darkgray' }}>
			* This block will include metadata to identify that the option was
			generated with AI assistance, the user who requested it, and the number
			of tokens used.
		</p>
	);
}