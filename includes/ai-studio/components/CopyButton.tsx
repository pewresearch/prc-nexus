/**
 * CopyButton component
 * 
 * A reusable button component that copies text to clipboard when clicked.
 */

import { Button } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import type { CopyButtonProps } from '../types';

/**
 * Button component with copy-to-clipboard functionality
 * 
 * @param props - Component props
 * @param props.text - Text to copy to clipboard
 * @param props.label - Button label (defaults to 'Copy')
 */
export const CopyButton = ({ text, label = 'Copy' }: CopyButtonProps) => {
	const ref = useCopyToClipboard(text, () => {
		console.log('Copied to clipboard');
	});

	// Don't render if there's no text to copy
	if (!text) {
		return null;
	}

	return (
		<Button ref={ref} variant="link" size="small">
			{label}
		</Button>
	);
};
