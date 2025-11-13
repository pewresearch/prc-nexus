/**
 * Validation utilities for ability input parameters
 * 
 * This module provides validation functions for ability inputs based on
 * their JSON schema definitions.
 */

import type { JSONSchema, InputValues, ValidationErrors } from '../types';

/**
 * Validates input values against a JSON schema
 * 
 * @param inputValues - Current input values
 * @param schema - JSON schema to validate against
 * @returns Object containing validation errors (empty if valid)
 */
export const validateInput = (
	inputValues: InputValues,
	schema: JSONSchema | any[]
): ValidationErrors => {
	const errors: ValidationErrors = {};

	if (Array.isArray(schema) || !schema?.properties) {
		return errors;
	}

	Object.keys(schema.properties).forEach((key) => {
		const prop = schema.properties![key];
		const value = inputValues[key];
		const isRequired = schema.required?.includes(key) || !prop.hasOwnProperty('default');

		// Check required fields
		if (isRequired && !value) {
			errors[key] = 'This field is required';
			return;
		}

		// Skip validation for empty optional fields
		if (!value && !isRequired) {
			return;
		}

		// Type-specific validation
		if (value) {
			// Array validation
			if (prop.type === 'array') {
				try {
					const parsed = JSON.parse(value);
					if (!Array.isArray(parsed)) {
						errors[key] = 'Must be a valid JSON array';
					} else if (prop.items?.enum) {
						// Validate enum values
						const invalidValues = parsed.filter((v) => !prop.items!.enum!.includes(v));
						if (invalidValues.length > 0) {
							errors[key] = `Invalid values: ${invalidValues.join(', ')}. Allowed: ${prop.items.enum.join(', ')}`;
						}
					}
				} catch {
					errors[key] = 'Must be valid JSON array format';
				}
			}

			// Object validation
			if (prop.type === 'object') {
				try {
					const parsed = JSON.parse(value);
					if (typeof parsed !== 'object' || Array.isArray(parsed)) {
						errors[key] = 'Must be a valid JSON object';
					}
				} catch {
					errors[key] = 'Must be valid JSON object format';
				}
			}

			// Number validation
			if (prop.type === 'number' || prop.type === 'integer') {
				const num = Number(value);
				if (isNaN(num)) {
					errors[key] = `Must be a valid ${prop.type}`;
				} else {
					if (prop.type === 'integer' && !Number.isInteger(num)) {
						errors[key] = 'Must be an integer';
					}
					if (prop.minimum !== undefined && num < prop.minimum) {
						errors[key] = `Must be at least ${prop.minimum}`;
					}
					if (prop.maximum !== undefined && num > prop.maximum) {
						errors[key] = `Must be at most ${prop.maximum}`;
					}
				}
			}

			// String pattern validation
			if (prop.type === 'string' && prop.pattern) {
				try {
					const regex = new RegExp(prop.pattern);
					if (!regex.test(value)) {
						errors[key] = `Must match pattern: ${prop.pattern}`;
					}
				} catch {
					// Invalid regex pattern in schema
				}
			}

			// Enum validation for string/number enums (not arrays)
			if (prop.enum && prop.type !== 'array') {
				if (!prop.enum.includes(value)) {
					errors[key] = `Must be one of: ${prop.enum.join(', ')}`;
				}
			}
		}
	});

	return errors;
};
