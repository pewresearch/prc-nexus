/**
 * External Dependencies
 */
import React from 'react';
import { Icon } from '@prc/icons';

/**
 * WordPress Dependencies
 */
import { Spinner as WPSpinner, __experimentalStyleProvider as StyleProvider } from '@wordpress/components';

/**
 * Internal Dependencies
 */
import { AI_COLORS } from './constants';

export const Spinner = () => (
	<WPSpinner
		style={{
			// color: AI_COLORS.primary,
			marginTop: 0,
			marginBottom: 0,
		}}
	/>
);

export const Sparkles = ({purple = false}: {purple?: boolean}) => {
	if (purple) {
		return (
			<Icon
				icon="sparkles"
				library="solid"
				size="1"
				color={AI_COLORS.primary}
			/>
		);
	}
	return (
		<Icon
			icon="sparkles"
			library="solid"
			size="1"
		/>
	);
}

export const MagicWand = ({purple = false}: {purple?: boolean}) => {
	if (purple) {
		return (
			<Icon
				icon="wand-magic-sparkles"
				library="solid"
				size="1"
				color={AI_COLORS.primary}
			/>
		);
	}
	return (
		<Icon
			icon="arrows-rotate"
			library="solid"
			size="1"
		/>
	);
};

export const ArrowsRotate = ({purple = false}: {purple?: boolean}) => {
	if (purple) {
		return (
			<Icon
				icon="arrows-rotate"
				library="solid"
				size="1"
				color={AI_COLORS.primary}
			/>
		);
	}
	return (
		<Icon
			icon="arrows-rotate"
			library="solid"
			size="1"
		/>
	);
};
