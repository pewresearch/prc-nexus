/**
 * Utility functions for ability data transformation
 * 
 * This module contains helper functions for transforming and processing
 * ability data for display and usage.
 */

import type { RawAbility, Ability, JSONSchema } from '../types';

/**
 * Transforms raw ability data into display format with additional fields
 * 
 * @param abilitiesData - Raw abilities from API
 * @returns Transformed abilities with display fields
 */
export const transformAbilities = (abilitiesData: RawAbility[]): Ability[] => {
	return abilitiesData.map((ability, index) => ({
		id: index + 1,
		...ability,
		// Flatten meta annotations for easier display
		readonly: ability.meta?.annotations?.readonly || false,
		destructive: ability.meta?.annotations?.destructive || false,
		idempotent: ability.meta?.annotations?.idempotent || false,
		show_in_rest: ability.meta?.show_in_rest || false,
		// Format input_schema for display
		input_type: Array.isArray(ability.input_schema)
			? 'none'
			: (ability.input_schema?.type || 'object'),
		// Format output_schema for display
		output_type: ability.output_schema?.type || 'unknown',
	}));
};

/**
 * Generates example input based on JSON schema
 * 
 * @param schema - JSON schema to generate example from
 * @returns Example object or null if schema is invalid
 */
export const generateExampleInput = (schema: JSONSchema | any[]): Record<string, any> | null => {
	if (Array.isArray(schema) || !schema?.properties) {
		return null;
	}

	const example: Record<string, any> = {};
	Object.keys(schema.properties).forEach((key) => {
		const prop = schema.properties![key];

		// Use default if available
		if (prop.hasOwnProperty('default')) {
			example[key] = prop.default;
		} else if (prop.enum) {
			// Use first enum value as example
			example[key] = prop.type === 'array' ? [prop.items?.enum?.[0]] : prop.enum[0];
		} else {
			// Generate based on type
			switch (prop.type) {
				case 'string':
					example[key] =
						prop.format === 'email'
							? 'example@email.com'
							: prop.format === 'url'
							? 'https://example.com'
							: 'example';
					break;
				case 'number':
				case 'integer':
					example[key] = prop.minimum || 1;
					break;
				case 'boolean':
					example[key] = false;
					break;
				case 'array':
					if (prop.items?.enum) {
						example[key] = [prop.items.enum[0]];
					} else {
						example[key] = [];
					}
					break;
				case 'object':
					example[key] = {};
					break;
				default:
					example[key] = null;
			}
		}
	});

	return example;
};
