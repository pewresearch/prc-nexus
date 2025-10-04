/**
 * External Dependencies
 */
import React from 'react';
import { Icon, lineDotted } from '@wordpress/icons';
import styled from '@emotion/styled';

/**
 * WordPress Dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticeStore } from '@wordpress/notices';
import { ToolbarDropdownMenu, ToolbarButton, createSlotFill } from '@wordpress/components';
import { useState, useCallback, useMemo, useEffect, useRef } from '@wordpress/element';
import { applyFilters, addFilter, removeFilter } from '@wordpress/hooks';

/**
 * Internal Dependencies
 */
import { Sparkles } from '../icons';
import { CandidatesModal, RequestModal } from '../components';
import { LoadingIcon } from '../components/loading';
import { AI_COLORS } from '../constants';

function addToNexusToolbar(props) {
	const { title, icon, toolType, tool, onRequest, onSelect } = props;
	addFilter(
		'prcNexus.toolbarMenuItems',
		'prc-nexus/extend-nexus-toolbar',
		(
			items,
			{
				setActiveTool,
				setModalTitle,
				setRequestModalOpen,
				setCandidatesModalOpen,
				requestedFunction,
				selectedFunction,
			}
		) => {
		  const itemKey = `${toolType}-${tool}`;
		  if (!items.some(item => item.key === itemKey)) {
			items.push({
			  key: itemKey,
			  title: title,
			  icon,
			  onClick: () => {
				setActiveTool(tool);
				setModalTitle(title);
				if (toolType === 'request') {
				  setRequestModalOpen(true);
				} else if (toolType === 'candidates') {
				  setCandidatesModalOpen(true);
				}
				requestedFunction.current = onRequest;
				selectedFunction.current = onSelect;
			  }
			});
		  }
		  return items;
		}
	  );
}

function removeFromNexusToolbar() {
	removeFilter(
		'prcNexus.toolbarMenuItems',
		'prc-nexus/extend-nexus-toolbar'
	);
}

function NexusToolbarMenu({blockName, clientId, isReady}) {
		console.log('NexusToolbarMenu', blockName, clientId, isReady);
	const [modalTitle, setModalTitle] = useState('');
	const [modalDescription, setModalDescription] = useState('');
	const [activeTool, setActiveTool] = useState(null);
	const [processing, setProcessing] = useState(false);
	const [requestModalOpen, setRequestModalOpen] = useState(false);
	const [candidatesModalOpen, setCandidatesModalOpen] = useState(false);
	const { createSuccessNotice, createErrorNotice } = useDispatch(noticeStore);
	const requestedFunction = useRef(null);
	const selectedFunction = useRef(null);

	const defaultMenuItems = [];

	// Allow other plugins to modify/add menu items
	const menuItems : any = applyFilters(
		'prcNexus.toolbarMenuItems',
		defaultMenuItems,
		{
			setActiveTool,
			setModalTitle,
			setRequestModalOpen,
			setCandidatesModalOpen,
			requestedFunction,
			selectedFunction,
		}
	);

	const label = useMemo(() => {
		if ( !isReady ) {
			return 'Connecting to PRC Nexus';
		}
		return 'Nexus available tools for: ' + blockName;
	}, [isReady, blockName]);

	if ( !isReady ) {
		return(
			<ToolbarButton label={label} icon={<LoadingIcon />}>
				{label}
			</ToolbarButton>
		);
	}

	return (
		<>
			<ToolbarDropdownMenu
				label={label}
				icon={<Sparkles purple />}
				controls={menuItems}
				className="prc-nexus__toolbar-menu"
			/>
			{requestModalOpen &&
				<RequestModal
					title={modalTitle}
					description={modalDescription}
					tool={activeTool}
					isOpen={requestModalOpen}
					onClose={() => setRequestModalOpen(false)}
					onRequest={requestedFunction?.current}
					clientId={clientId}
				/>
			}
			{candidatesModalOpen && <CandidatesModal />}
		</>
	);
}

export {
	addToNexusToolbar,
	removeFromNexusToolbar,
	NexusToolbarMenu,
};

export default NexusToolbarMenu;
