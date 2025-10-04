/**
 * External Dependencies
 */
import React from 'react';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal Dependencies
 */
import { AI_COLORS } from '../constants';
import { Sparkles, Spinner } from '../icons';

/**
 * LoadingMessage component displays a loading state for AI operations.
 */
export function LoadingMessage() {
	return (
		<div
			style={{
				display: 'flex',
				flexDirection: 'column',
				alignItems: 'center',
				gap: '1em',
				padding: '2em',
			}}
		>
			<div
				style={{
					display: 'flex',
					alignItems: 'center',
					gap: '0.5em',
					padding: '0.75em',
					background: AI_COLORS.iconBackground,
					borderRadius: '8px',
				}}
			>
				<Sparkles />
				<Spinner />
			</div>
			<p style={{ margin: 0, color: AI_COLORS.text }}>
				{__(
					'PRC Nexus is working...',
					'prc-block-library'
				)}
			</p>
		</div>
	);
}

export function LoadingIcon() {
	return (
		<Spinner />
	);
}
