/**
 * External Dependencies
 */
import React from 'react';

/**
 * WordPress Dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Internal Dependencies
 */

import { MagicWand } from '../icons';

export function CandidatesPicker({
	candidates = [],
	onSelect = (candidate: string) => {},
}) {
	const wandIcon = <MagicWand />;

	return (
		<>
			<div>
				<h3>
					Choose from the following AI generated candidates:
			</h3>
		</div>
		<div
			style={{
				display: 'flex',
				flexDirection: 'column',
				gap: '10px',
			}}
		>
			{candidates.map((candidate, index) => (
				<Button
					key={`candidate-${index}`}
					variant="secondary"
					onClick={() => onSelect(candidate)}
					icon={wandIcon}
					iconSize={1}
					style={{
						height: 'max-content',
						whiteSpace: 'normal',
						textAlign: 'left',
						gap: '1em',
					}}
				>
					{candidate}
				</Button>
			))}
		</div>
		</>
	);
}