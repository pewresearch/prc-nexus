/**
 * API service for WordPress Abilities
 * 
 * This module handles all interactions with the WordPress Abilities API,
 * including fetching abilities and executing them.
 */

import type { RawAbility } from '../types';

/**
 * Fetches all available abilities from the WordPress Abilities API
 * 
 * @returns Promise resolving to array of abilities
 * @throws Error if wp.abilities is not available
 */
export const getAbilities = async (): Promise<RawAbility[]> => {
	if (typeof window.wp !== 'undefined' && window.wp.abilities) {
		return await window.wp.abilities.getAbilities();
	}
	throw new Error('wp.abilities is not available');
};

/**
 * Executes a specific ability with given input
 * 
 * @param abilityName - Name of the ability to execute
 * @param input - Input data for the ability
 * @returns Promise resolving to the execution result
 * @throws Error if wp.abilities is not available or execution fails
 */
export const executeAbility = async (
	abilityName: string,
	input: any
): Promise<any> => {
	if (typeof window.wp !== 'undefined' && window.wp.abilities) {
		return await window.wp.abilities.executeAbility(abilityName, input);
	}
	throw new Error('wp.abilities is not available');
};
