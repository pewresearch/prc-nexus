/**
 * Custom hook for managing abilities data
 * 
 * This hook handles fetching and transforming abilities from the API.
 */

import { useState, useEffect } from '@wordpress/element';
import type { Ability } from '../types';
import { getAbilities } from '../services/abilities';
import { transformAbilities } from '../utils/abilityTransform';

/**
 * Hook for fetching and managing abilities data
 * 
 * @returns Object containing abilities array and loading state
 */
export const useAbilities = () => {
	const [abilities, setAbilities] = useState<Ability[]>([]);
	const [isLoading, setIsLoading] = useState(true);

	useEffect(() => {
		const loadAbilities = async () => {
			try {
				const abilitiesData = await getAbilities();
				const transformedData = transformAbilities(abilitiesData);
				setAbilities(transformedData);
				setIsLoading(false);
			} catch (error) {
				console.error('Error loading abilities:', error);
				setIsLoading(false);
			}
		};

		loadAbilities();
	}, []);

	return { abilities, isLoading };
};
