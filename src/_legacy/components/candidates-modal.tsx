/* eslint-disable max-lines */
/**
 * External Dependencies
 */
import React from 'react';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
} from '@wordpress/components';

/**
 * Internal Dependencies
 */
import { ArrowsRotate, MagicWand, Sparkles } from '../icons';
import { LoadingMessage, TrackingMessage, CandidatesPicker, Modal } from './index';

export function CandidatesModal({
	title = 'Candidates from AI request',
	candidates = [],
	isOpen = false,
	onClose = () => {},
	onSelect = (candidate: string) => {},
	onClear = () => {},
}) {
	if (!isOpen) return null;

	const arrowsRotateIcon = <ArrowsRotate />;

	return (
		<Modal
			title={title}
			onClose={onClose}
		>
			<div>
				{candidates.length === 0 ? (
					<LoadingMessage />
				) : (
					<>
						<CandidatesPicker
							candidates={candidates}
							onSelect={onSelect}
						/>
						<TrackingMessage />
						<Button
							onClick={onClear}
							icon={arrowsRotateIcon}
							iconSize={1}
						>
							Refresh Candidates
						</Button>
					</>
				)}
			</div>
		</Modal>
	);
}