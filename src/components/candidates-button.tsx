/**
 * External Dependencies
 */
import React from 'react';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';


/**
 * Internal Dependencies
 */
import { Sparkles } from '../icons';
import { CandidatesModal } from './candidates-modal';
import { LoadingIcon } from './loading';
import { AI_COLORS } from '../constants';

export function CandidatesButton({
	label = 'Generate with ✨ AI',
	doRequest = async () => {},
	onSelect = (candidate: string, metadata: any) => {},
	isGenerating = false,
	setIsGenerating = (boolean: boolean) => {
		console.log('isGenerating', boolean);
	},
}) {
	const [processing, setProcessing] = useState(false);
	const [generatedCandidates, setGeneratedCandidates] = useState([]);
	const [generatedMetadata, setGeneratedMetadata] = useState([]);
	const [isCandidatesModalOpen, setIsCandidatesModalOpen] = useState(false);

	const pilotLight = useSelect(
		(select) => {
			const { hasAvailableServices } = select('ai-services/ai');
			const aiServicesReady = hasAvailableServices();
			return aiServicesReady;
		},
		[]
	);

	const doGeneration = useCallback(async () => {
		setProcessing(true);
		setIsGenerating(true);

		try {
			const { data, metadata } = await doRequest();
			setGeneratedCandidates(data);
			setGeneratedMetadata(metadata);
			setIsCandidatesModalOpen(true);
		} catch (error) {
			console.error(error);
		} finally {
			setProcessing(false);
			setIsGenerating(false);
		}
	}, [setIsGenerating, doRequest]);

	const buttonIcon = <Sparkles />;

	if (!pilotLight || processing) {
		return(
			<Button
				disabled={true}
				variant="tertiary"
				isBusy={true}
				iconSize={1}
				label={label}
			>
				<LoadingIcon />
			</Button>
		);
	}
	return (
		<>
			<Button
				onClick={doGeneration}
				disabled={processing}
				isBusy={processing}
				isPressed={processing}
				variant="tertiary"
				icon={buttonIcon}
				iconSize={1}
				label={label}
				showTooltip={true}
			/>
			<CandidatesModal
				title="Table Caption Candidates"
				candidates={generatedCandidates}
				isOpen={isCandidatesModalOpen}
				onClose={() => setIsCandidatesModalOpen(false)}
				onClear={() => {
					doGeneration();
				}}
				onSelect={(candidate) => {
					onSelect(candidate, generatedMetadata);
					setIsCandidatesModalOpen(false);
				}}
			/>
		</>
	);
}
