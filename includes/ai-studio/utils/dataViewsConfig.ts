/**
 * DataViews configuration utilities
 * 
 * This module provides configuration for DataViews fields and actions.
 */

import type { Ability, DataViewsField } from '../types';

/**
 * Extracts unique categories from abilities for filtering
 * 
 * @param abilities - Array of abilities
 * @returns Array of category elements for DataViews filter
 */
export const getCategoryElements = (abilities: Ability[]) => {
	return [...new Set(abilities.map((ability) => ability.category))]
		.filter(Boolean)
		.sort()
		.map((cat) => ({
			value: cat,
			label: cat
				.split('-')
				.map((word) => word.charAt(0).toUpperCase() + word.slice(1))
				.join(' '),
		}));
};

/**
 * Creates DataViews field definitions
 * 
 * @param categoryElements - Category filter elements
 * @returns Array of field definitions
 */
export const createFields = (
	categoryElements: Array<{ value: string; label: string }>
): DataViewsField[] => {
	return [
		{
			id: 'name',
			label: 'Ability Name',
			enableHiding: false,
			enableSorting: true,
			enableGlobalSearch: true,
		},
		{
			id: 'label',
			label: 'Label',
			enableSorting: true,
			enableHiding: true,
			enableGlobalSearch: true,
		},
		{
			id: 'category',
			label: 'Category',
			enableSorting: true,
			enableHiding: true,
			elements: categoryElements,
			filterBy: {
				operators: ['is', 'isNot'],
			},
			enableGlobalSearch: true,
		},
		{
			id: 'description',
			label: 'Description',
			enableSorting: false,
			enableHiding: true,
			maxWidth: 400,
			enableGlobalSearch: true,
		},
		{
			id: 'input_type',
			label: 'Input Type',
			enableSorting: true,
			enableHiding: true,
		},
		{
			id: 'output_type',
			label: 'Output Type',
			enableSorting: true,
			enableHiding: true,
		},
		{
			id: 'readonly',
			label: 'Read Only',
			enableSorting: true,
			enableHiding: true,
			render: ({ item }) => (item.readonly ? '✓' : '✗'),
		},
		{
			id: 'destructive',
			label: 'Destructive',
			enableSorting: true,
			enableHiding: true,
			render: ({ item }) => (item.destructive ? '⚠️' : '✓'),
		},
		{
			id: 'show_in_rest',
			label: 'REST API',
			enableSorting: true,
			enableHiding: true,
			render: ({ item }) => (item.show_in_rest ? '✓' : '✗'),
		},
	];
};
