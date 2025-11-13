/**
 * Custom hook for ability execution
 * 
 * This hook manages the state and logic for executing abilities with input validation.
 */

import { useState, useEffect } from '@wordpress/element';
import type { Ability, InputValues, ValidationErrors, JSONSchema } from '../types';
import { executeAbility } from '../services/abilities';
import { validateInput } from '../utils/validation';

/**
 * Hook for managing ability execution state and logic
 * 
 * @param ability - The ability to execute
 * @returns Object containing execution state and handler functions
 */
export const useAbilityExecution = (ability: Ability) => {
	const [isExecuting, setIsExecuting] = useState(false);
	const [result, setResult] = useState<any>(null);
	const [error, setError] = useState<string | null>(null);
	const [inputValues, setInputValues] = useState<InputValues>({});
	const [inputErrors, setInputErrors] = useState<ValidationErrors>({});

	// Initialize input values with defaults
	useEffect(() => {
		if (!Array.isArray(ability.input_schema) && ability.input_schema?.properties) {
			const defaults: InputValues = {};
			Object.keys(ability.input_schema.properties).forEach((key) => {
				const prop = ability.input_schema.properties![key];
				defaults[key] = prop.default || '';
			});
			setInputValues(defaults);
		}
	}, [ability]);

	/**
	 * Executes the ability with current input values
	 */
	const execute = async () => {
		// Validate inputs
		const errors = validateInput(inputValues, ability.input_schema as JSONSchema);
		if (Object.keys(errors).length > 0) {
			setInputErrors(errors);
			return;
		}

		setIsExecuting(true);
		setError(null);
		setResult(null);
		setInputErrors({});

		try {
			// Prepare the input data
			let inputData: any = null;
			if (!Array.isArray(ability.input_schema) && ability.input_schema?.properties) {
				inputData = {};
				Object.keys(ability.input_schema.properties).forEach((key) => {
					const prop = ability.input_schema.properties![key];
					const value = inputValues[key];

					if (value !== '') {
						if (prop.type === 'array') {
							try {
								inputData[key] = JSON.parse(value);
							} catch {
								inputData[key] = [];
							}
						} else {
							inputData[key] = value;
						}
					}
				});

				// If all fields are empty, pass null for abilities that accept no input
				if (Object.keys(inputData).length === 0) {
					inputData = null;
				}
			}

			const executionResult = await executeAbility(ability.name, inputData);
			setResult(executionResult);
			console.log(`Executed ${ability.name}:`, executionResult);
		} catch (err) {
			setError((err as Error).message);
			console.error(`Error executing ${ability.name}:`, err);
		} finally {
			setIsExecuting(false);
		}
	};

	return {
		isExecuting,
		result,
		error,
		inputValues,
		inputErrors,
		setInputValues,
		setInputErrors,
		execute,
	};
};
