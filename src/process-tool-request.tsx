/**
 * External Dependencies
 */


/**
 * WordPress Dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal Dependencies
 */
import { generateTrackingData } from './tracking';

/**
 * A candidate from a PRC Nexus tool request.
 */
interface Candidate {
	modelVersion: string;
	usageMetadata: Record<string, any>;
	citationMetadata: Record<string, any>;
}

/**
 * The result of a PRC Nexus tool request.
 */
interface ProcessToolRequestResult {
	candidates: any[];
	metadata: Awaited<ReturnType<typeof generateTrackingData>>;
}

/**
 * Process a PRC Nexus tool request.
 *
 * @param prompt - The prompt to process.
 * @param tool - The tool to process.
 * @param model - The model to process (optional).
 * @return The processed tool request result.
 */
export async function processToolRequest(
	prompt: string,
	tool: string,
	model?: string
): Promise<ProcessToolRequestResult | null | void> {
	const { hasAvailableServices, getAvailableService } = select('ai-services/ai');
	const { createErrorNotice } = dispatch(noticesStore);
	if ( hasAvailableServices() ) {
		const service = getAvailableService();
		console.log('service ==', {...service});
		try {
			const options = {
				feature: tool,
			};
			const availableModels = service.models || [];
			console.log("availableModels ==", availableModels);
			// Get the property names off the availableModels object as modelSlugs
			const modelSlugs = Object.keys(availableModels);
			console.log("modelSlugs ==", modelSlugs);
			if (model && modelSlugs.includes(model)) {
				options.model = model;
			}

			console.log('prompt ==', prompt, options);

			const candidates = await service.generateText(prompt, options);

			console.log('...', candidates);

			const { helpers } = (window as any).aiServices.ai;

			if (candidates.length === 0) {
				throw new Error(`No candidates found for ${tool}`);
			}

			const { modelVersion, usageMetadata, citationMetadata } = candidates[0];

			const metadata = await generateTrackingData(
				modelVersion,
				usageMetadata,
				citationMetadata,
				prompt
			);

			const processedCandidates = helpers.getCandidateContents(candidates);

			console.log('candidates ==', processedCandidates);

			return {
				candidates: processedCandidates,
				metadata,
			};
		} catch (error) {
			console.error(error);
			createErrorNotice(error?.message || String(error));
		}
	} else {
		console.log('No available services...');
	}
}
