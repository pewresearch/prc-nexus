/**
 * WordPress Dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal Dependencies
 */

/**
 * The tracking data for a PRC Copilot tool request.
 */
interface TrackingData {
	userId: number | undefined;
	timestamp: string;
	modelVersion: string;
	usageMetadata: Record<string, any>;
	citationMetadata: Record<string, any>;
	prompt: string;
}

/**
 * Generate tracking data for a PRC Copilot tool request.
 *
 * @param modelVersion - The version of the model used.
 * @param usageMetadata - The usage metadata for the tool request.
 * @param citationMetadata - The citation metadata for the tool request.
 * @param prompt - The prompt for the tool request.
 * @return The tracking data for the tool request.
 */
export async function generateTrackingData(
	modelVersion: string,
	usageMetadata: Record<string, any>,
	citationMetadata: Record<string, any>,
	prompt: string
): Promise<TrackingData> {
	// Get the current user id
	const currentUser = select('core').getCurrentUser();
	const currentUserId = currentUser?.id as number | undefined;

	// Get the current timestamp
	const currentTimestamp = new Date().toISOString();

	return {
		userId: currentUserId,
		timestamp: currentTimestamp,
		modelVersion,
		usageMetadata,
		citationMetadata,
		prompt,
	};
}