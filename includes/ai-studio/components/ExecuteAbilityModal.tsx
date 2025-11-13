/**
 * ExecuteAbilityModal component
 *
 * Modal for executing abilities with input parameters and viewing results.
 */

import {
	Button,
	__experimentalVStack as VStack,
	__experimentalText as Text,
	Notice,
} from '@wordpress/components';
import type { Ability } from '../types';
import { useAbilityExecution } from '../hooks/useAbilityExecution';
import { generateExampleInput } from '../utils/abilityTransform';
import { CopyButton } from './CopyButton';
import { JSONViewer } from './JSONViewer';
import { InputField } from './InputField';

interface ExecuteAbilityModalProps {
	ability: Ability;
	closeModal: () => void;
}

/**
 * Modal component for executing an ability
 *
 * @param props            - Component props
 * @param props.ability    - The ability to execute
 * @param props.closeModal - Function to close the modal
 */
export const ExecuteAbilityModal = ({
	ability,
	closeModal,
}: ExecuteAbilityModalProps) => {
	const {
		isExecuting,
		result,
		error,
		inputValues,
		inputErrors,
		setInputValues,
		setInputErrors,
		execute,
	} = useAbilityExecution(ability);

	const hasInput =
		!Array.isArray(ability.input_schema) &&
		ability.input_schema?.properties;

	/**
	 * Generates and sets example input values
	 */
	const handleGenerateExample = () => {
		const example = generateExampleInput(ability.input_schema);
		if (example) {
			const newValues: Record<string, string> = {};
			Object.keys(example).forEach((key) => {
				if (
					Array.isArray(example[key]) ||
					typeof example[key] === 'object'
				) {
					newValues[key] = JSON.stringify(example[key], null, 2);
				} else {
					newValues[key] = String(example[key]);
				}
			});
			setInputValues(newValues);
			setInputErrors({});
		}
	};

	return (
		<VStack spacing="4">
			<div>
				<Text size="large" weight="600">
					{ability.label}
				</Text>
			</div>
			<Text>{ability.description}</Text>

			{hasInput && !result && ability.input_schema?.properties && (
				<div
					style={{
						background: '#f0f0f0',
						padding: '16px',
						borderRadius: '4px',
					}}
				>
					<div
						style={{
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginBottom: '12px',
						}}
					>
						<Text weight="600">Input Parameters:</Text>
						<Button
							variant="secondary"
							size="small"
							onClick={handleGenerateExample}
						>
							Generate Example
						</Button>
					</div>
					{Object.keys(ability.input_schema.properties).map((key) => {
						const prop = ability.input_schema.properties![key];
						const isRequired =
							ability.input_schema.required?.includes(key) ||
							!prop.hasOwnProperty('default');

						return (
							<InputField
								key={key}
								fieldKey={key}
								prop={prop}
								isRequired={isRequired}
								inputValues={inputValues}
								inputErrors={inputErrors}
								onValueChange={(k, v) =>
									setInputValues({ ...inputValues, [k]: v })
								}
								onErrorClear={(k) =>
									setInputErrors({ ...inputErrors, [k]: '' })
								}
							/>
						);
					})}
					<Text
						size="small"
						style={{
							display: 'block',
							marginTop: '8px',
							color: '#666',
							fontStyle: 'italic',
						}}
					>
						Tip: Leave optional fields empty to use their default
						values
					</Text>
				</div>
			)}

			{error && (
				<Notice status="error" isDismissible={false}>
					Error: {error}
				</Notice>
			)}

			{result && (
				<div>
					<div
						style={{
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							marginBottom: '8px',
						}}
					>
						<Text weight="600">Result:</Text>
						<CopyButton
							text={JSON.stringify(result, null, 2)}
							label="Copy Result"
						/>
					</div>
					<JSONViewer data={result} expanded={true} />
				</div>
			)}

			<div
				style={{
					display: 'flex',
					gap: '8px',
					justifyContent: 'flex-end',
				}}
			>
				{!result && (
					<Button
						variant="primary"
						onClick={execute}
						disabled={isExecuting}
						isBusy={isExecuting}
					>
						{isExecuting ? 'Executing...' : 'Execute'}
					</Button>
				)}
				<Button
					variant={result ? 'primary' : 'secondary'}
					onClick={closeModal}
				>
					{result ? 'Close' : 'Cancel'}
				</Button>
			</div>
		</VStack>
	);
};
