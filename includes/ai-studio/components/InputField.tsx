/**
 * InputField component
 * 
 * Renders an input field based on JSON schema property definition.
 */

import { TextareaControl, Button, __experimentalText as Text, Notice } from '@wordpress/components';
import type { JSONSchemaProperty, InputValues, ValidationErrors } from '../types';

interface InputFieldProps {
	fieldKey: string;
	prop: JSONSchemaProperty;
	isRequired: boolean;
	inputValues: InputValues;
	inputErrors: ValidationErrors;
	onValueChange: (key: string, value: string) => void;
	onErrorClear: (key: string) => void;
}

/**
 * Get input type based on schema property
 */
const getInputType = (prop: JSONSchemaProperty): string => {
	if (prop.enum && prop.type !== 'array') return 'select';
	if (prop.type === 'boolean') return 'checkbox';
	if (prop.type === 'number' || prop.type === 'integer') return 'number';
	if (prop.type === 'array' && prop.items?.enum) return 'multi-select';
	if (prop.type === 'array') return 'json-array';
	if (prop.type === 'object') return 'json-object';
	return 'text';
};

/**
 * Format type information for display
 */
const getTypeInfo = (prop: JSONSchemaProperty): string => {
	let info = prop.type || 'string';
	if (prop.type === 'array' && prop.items) {
		if (prop.items.type) {
			info += ` of ${prop.items.type}s`;
		}
		if (prop.items.enum) {
			info += ` (${prop.items.enum.length} choices)`;
		}
	}
	if (prop.minimum !== undefined || prop.maximum !== undefined) {
		info += ` (${prop.minimum ?? '*'} - ${prop.maximum ?? '*'})`;
	}
	if (prop.pattern) {
		info += ` (pattern: ${prop.pattern})`;
	}
	return info;
};

/**
 * Input field component that renders different input types based on schema
 * 
 * @param props - Component props
 */
export const InputField = ({
	fieldKey,
	prop,
	isRequired,
	inputValues,
	inputErrors,
	onValueChange,
	onErrorClear,
}: InputFieldProps) => {
	const inputType = getInputType(prop);
	const value = inputValues[fieldKey] || '';

	const handleChange = (newValue: string) => {
		onValueChange(fieldKey, newValue);
		onErrorClear(fieldKey);
	};

	return (
		<div
			style={{
				marginBottom: '20px',
				padding: '12px',
				background: '#fff',
				borderRadius: '4px',
				border: '1px solid #ddd',
			}}
		>
			<div style={{ marginBottom: '8px' }}>
				<label style={{ display: 'block', marginBottom: '4px' }}>
					<Text weight="600" style={{ fontSize: '14px', color: '#1e1e1e' }}>
						{fieldKey}
						{isRequired && <span style={{ color: '#d94f4f' }}> *</span>}
						{!isRequired && <span style={{ color: '#666' }}> (optional)</span>}
					</Text>
				</label>
				<Text size="small" style={{ display: 'block', color: '#666' }}>
					Type:{' '}
					<code
						style={{
							background: '#f0f0f0',
							padding: '2px 4px',
							borderRadius: '2px',
							fontSize: '11px',
						}}
					>
						{getTypeInfo(prop)}
					</code>
					{prop.default !== undefined && (
						<span>
							{' '}
							• Default:{' '}
							<code
								style={{
									background: '#f0f0f0',
									padding: '2px 4px',
									borderRadius: '2px',
									fontSize: '11px',
								}}
							>
								{JSON.stringify(prop.default)}
							</code>
						</span>
					)}
				</Text>
				{prop.description && (
					<Text size="small" style={{ display: 'block', marginTop: '4px', color: '#666' }}>
						{prop.description}
					</Text>
				)}
			</div>

			{/* Multi-select for array with enum items */}
			{inputType === 'multi-select' && prop.items?.enum && (
				<div>
					<div
						style={{
							background: '#f8f8f8',
							padding: '8px',
							borderRadius: '4px',
							marginBottom: '8px',
							maxHeight: '150px',
							overflowY: 'auto',
						}}
					>
						<Text size="small" weight="500" style={{ display: 'block', marginBottom: '6px' }}>
							Available options (click to select/deselect):
						</Text>
						<div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
							{prop.items.enum.map((val) => {
								let isSelected = false;
								try {
									const arr = value ? JSON.parse(value) : [];
									isSelected = Array.isArray(arr) && arr.includes(val);
								} catch {}

								return (
									<Button
										key={String(val)}
										variant={isSelected ? 'primary' : 'secondary'}
										size="small"
										onClick={() => {
											let currentArray: any[] = [];
											try {
												if (value) {
													currentArray = JSON.parse(value);
												}
											} catch {}

											if (!Array.isArray(currentArray)) currentArray = [];

											if (currentArray.includes(val)) {
												currentArray = currentArray.filter((v) => v !== val);
											} else {
												currentArray.push(val);
											}

											const newValue =
												currentArray.length > 0 ? JSON.stringify(currentArray, null, 2) : '';
											handleChange(newValue);
										}}
									>
										{String(val)}
									</Button>
								);
							})}
						</div>
					</div>
					<TextareaControl
						value={value}
						onChange={handleChange}
						placeholder="Selected values will appear here as JSON array"
						rows={3}
						style={{
							fontFamily: 'monospace',
							fontSize: '12px',
							backgroundColor: '#fff',
						}}
					/>
				</div>
			)}

			{/* Regular select dropdown */}
			{inputType === 'select' && prop.enum && (
				<select
					value={value}
					onChange={(e) => handleChange(e.target.value)}
					style={{
						width: '100%',
						padding: '8px',
						border: inputErrors[fieldKey] ? '1px solid #d94f4f' : '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize: '14px',
					}}
				>
					<option value="">-- Select {fieldKey} --</option>
					{prop.enum.map((val) => (
						<option key={String(val)} value={String(val)}>
							{String(val)}
						</option>
					))}
				</select>
			)}

			{/* Boolean checkbox */}
			{inputType === 'checkbox' && (
				<div>
					<label style={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}>
						<input
							type="checkbox"
							checked={value === 'true' || value === true}
							onChange={(e) => handleChange(e.target.checked.toString())}
							style={{ marginRight: '8px' }}
						/>
						<Text>Enable {fieldKey}</Text>
					</label>
				</div>
			)}

			{/* Number input */}
			{inputType === 'number' && (
				<input
					type="number"
					value={value}
					onChange={(e) => handleChange(e.target.value)}
					placeholder={`Enter ${prop.type}`}
					min={prop.minimum}
					max={prop.maximum}
					style={{
						width: '100%',
						padding: '8px',
						border: inputErrors[fieldKey] ? '1px solid #d94f4f' : '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize: '14px',
					}}
				/>
			)}

			{/* JSON array/object inputs */}
			{(inputType === 'json-array' || inputType === 'json-object') && (
				<TextareaControl
					value={value}
					onChange={handleChange}
					placeholder={
						inputType === 'json-array'
							? `Example: ["item1", "item2", "item3"]`
							: `Example: {"key": "value", "key2": "value2"}`
					}
					rows={4}
					style={{
						fontFamily: 'monospace',
						fontSize: '12px',
						backgroundColor: '#fff',
					}}
					help={`Enter valid JSON ${prop.type} format`}
				/>
			)}

			{/* Default text input */}
			{inputType === 'text' && (
				<input
					type="text"
					value={value}
					onChange={(e) => handleChange(e.target.value)}
					placeholder={`Enter ${prop.type || 'text'} value`}
					style={{
						width: '100%',
						padding: '8px',
						border: inputErrors[fieldKey] ? '1px solid #d94f4f' : '1px solid #8c8f94',
						borderRadius: '4px',
						fontSize: '14px',
					}}
				/>
			)}

			{inputErrors[fieldKey] && (
				<Notice status="error" isDismissible={false} style={{ marginTop: '8px' }}>
					{inputErrors[fieldKey]}
				</Notice>
			)}
		</div>
	);
};
