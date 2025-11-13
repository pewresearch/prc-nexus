/**
 * ViewDetailsModal component
 * 
 * Modal that displays detailed information about an ability.
 */

import { __experimentalVStack as VStack, __experimentalText as Text } from '@wordpress/components';
import type { Ability } from '../types';
import { CopyButton } from './CopyButton';
import { JSONViewer } from './JSONViewer';

interface ViewDetailsModalProps {
	ability: Ability;
	closeModal: () => void;
}

/**
 * Modal component for viewing ability details
 * 
 * @param props - Component props
 * @param props.ability - The ability to display
 * @param props.closeModal - Function to close the modal
 */
export const ViewDetailsModal = ({ ability }: ViewDetailsModalProps) => {
	return (
		<VStack spacing="4">
			<div style={{ marginBottom: '16px' }}>
				<Text size="large" weight="600">
					Ability: {ability.name}
				</Text>
			</div>

			<div>
				<Text weight="600">Label:</Text>
				<Text>{ability.label}</Text>
			</div>

			<div>
				<Text weight="600">Description:</Text>
				<Text>{ability.description}</Text>
			</div>

			<div>
				<Text weight="600">Category:</Text>
				<Text>{ability.category}</Text>
			</div>

			<div>
				<Text weight="600">Annotations:</Text>
				<ul style={{ margin: '8px 0', paddingLeft: '20px' }}>
					<li>Read Only: {ability.readonly ? '✓' : '✗'}</li>
					<li>Destructive: {ability.destructive ? '⚠️ Yes' : '✓ No'}</li>
					<li>Idempotent: {ability.idempotent ? '✓' : '✗'}</li>
					<li>Show in REST: {ability.show_in_rest ? '✓' : '✗'}</li>
				</ul>
			</div>

			<div>
				<div
					style={{
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginBottom: '8px',
					}}
				>
					<Text weight="600">Input Schema:</Text>
					{!Array.isArray(ability.input_schema) && (
						<CopyButton text={JSON.stringify(ability.input_schema, null, 2)} />
					)}
				</div>
				{Array.isArray(ability.input_schema) ? (
					<Text style={{ color: '#666', fontStyle: 'italic' }}>No input required</Text>
				) : (
					<JSONViewer data={ability.input_schema} />
				)}
			</div>

			<div>
				<div
					style={{
						display: 'flex',
						justifyContent: 'space-between',
						alignItems: 'center',
						marginBottom: '8px',
					}}
				>
					<Text weight="600">Output Schema:</Text>
					{(!Array.isArray(ability.output_schema) || ability.output_schema.length > 0) && (
						<CopyButton text={JSON.stringify(ability.output_schema, null, 2)} />
					)}
				</div>
				{Array.isArray(ability.output_schema) && ability.output_schema.length === 0 ? (
					<Text style={{ color: '#666', fontStyle: 'italic' }}>No output schema</Text>
				) : (
					<JSONViewer data={ability.output_schema} />
				)}
			</div>
		</VStack>
	);
};
