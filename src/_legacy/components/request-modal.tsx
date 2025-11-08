/* eslint-disable max-lines */
/**
 * External Dependencies
 */
import React, { useState, useCallback, useEffect } from 'react';
import styled from '@emotion/styled';
import { useDebounce } from '@prc/hooks';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
// @ts-ignore
import { useDispatch } from '@wordpress/data';
import {
	Button,
	TextareaControl,
	KeyboardShortcuts,
} from '@wordpress/components';
import { store as noticeStore } from '@wordpress/notices';

/**
 * Internal Dependencies
 */
import { LoadingMessage, TrackingMessage, Modal } from './index';
import { Sparkles } from '../icons';

interface NoticeFunctions {
	createSuccessNotice: (object: {
		message: string;
		type: 'snackbar' | 'default';
	}) => void;
	createErrorNotice: (object: {
		message: string;
		type: 'snackbar' | 'default';
	}) => void;
}

export interface RequestModalProps {
	title?: string;
	description?: string;
	tool?: string | null;
	attributes?: Record<string, unknown>;
	isOpen?: boolean;
	clientId?: string;
	allowAdditionalInstructions?: boolean;
	onClose?: () => void;
	onRequest?: (
		request: string,
		instructions: string,
		tool: string | null,
		clientId: string,
		notices?: NoticeFunctions,
	) => Promise<void> | null;
}

/**
 * RequestModal component
 *
 * @param {RequestModalProps} props - The component props
 */
export function RequestModal({
	title = 'Request data from AI',
	description = '',
	tool = '',
	isOpen = false,
	allowAdditionalInstructions = false,
	onClose = () => {},
	clientId,
	onRequest = async (
		request: string,
		instructions: string,
		tool: string,
		clientId: string,
		notices?: NoticeFunctions,
	) => {
		console.log('onRequest ==', request, instructions, tool, clientId);
	},
}: RequestModalProps) {
	const [isGenerating, setIsGenerating] = useState<boolean>(false);
	const [request, setRequest] = useState<string>('');
	const [instructions, setInstructions] = useState<string>('');

	useEffect(() => {
		if (isOpen) {
			console.log('isOpen = clientId', clientId);
			setRequest('');
			setInstructions('');
		}
	}, [isOpen]);

	const { createSuccessNotice, createErrorNotice } = useDispatch(noticeStore) as NoticeFunctions;

	const doRequest = useCallback(async () => {
		setIsGenerating(true);
		try {
			await onRequest(request, instructions, tool, clientId, {
				createSuccessNotice,
				createErrorNotice,
			});
		} catch (error) {
			createErrorNotice(error?.message || String(error));
		}
		setIsGenerating(false);
		onClose();
	}, [request, instructions, tool, onRequest, createSuccessNotice, createErrorNotice, onClose, clientId]);

	const icon = <Sparkles />;

	if (!isOpen) return null;

	return (
		<Modal
			title={title}
			onClose={onClose}
		>
			<KeyboardShortcuts shortcuts={{
				'mod+enter': doRequest,
			}}>
				{isGenerating ? (
					<LoadingMessage />
				) : (
					<>
						<TextareaControl
							label="Request"
							onChange={(value: string) => setRequest(value)}
							help={description}
						/>
						{allowAdditionalInstructions && (
							<TextareaControl
								label="Instructions"
								help="Provide additional instructions for the AI to follow."
								value={instructions}
								onChange={(value: string) => setInstructions(value)}
							/>
						)}
						<TrackingMessage />
						<div
							style={{
								display: 'flex',
								alignItems: 'center',
								gap: '1em',
							}}
						>
							<Button
								onClick={doRequest}
								isBusy={isGenerating}
								variant="primary"
								icon={icon}
								iconSize={1}
							>
								Generate
							</Button>
						</div>
					</>
				)}
			</KeyboardShortcuts>
		</Modal>
	);
}
