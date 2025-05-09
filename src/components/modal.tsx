/**
 * External Dependencies
 */
import React from 'react';
import styled from '@emotion/styled';

/**
 * WordPress Dependencies
 */
import { Modal as WPModal } from '@wordpress/components';

/**
 * Internal Dependencies
 */
import { Sparkles } from '../icons';

const StyledModal = styled(WPModal)`
	.components-modal__header-heading {
		margin-left: 0.5em;
	}
`;

export function Modal(props) {
	const { title, onClose, children } = props;

	const modalIcon = <Sparkles />;

	return (
		<StyledModal
			title={title}
			icon={modalIcon}
			onRequestClose={onClose}
			size="medium"
		>
			{children}
		</StyledModal>
	);
}
