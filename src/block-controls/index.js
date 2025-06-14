/**
 * WordPress Dependencies
 */
import { useSelect } from '@wordpress/data';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal Dependencies
 */
import { CopilotToolbarMenu } from './toolbar';

const ALLOWED_BLOCKS = ['prc-block/table'];

const withPRCCopilotToolbarControls = createHigherOrderComponent(
	(BlockEdit) =>
		function CopilotToolbarControls(props) {
			const { name, attributes, setAttributes, clientId } = props;

			const pilotLight = useSelect(
				(select) => {
					if (!ALLOWED_BLOCKS.includes(name)) {
						return false;
					}
					const { hasAvailableServices } = select('ai-services/ai');
					console.log('hasAvailableServices', hasAvailableServices);
					const aiServicesReady = hasAvailableServices();
					console.log('aiServicesReady', aiServicesReady);
					return aiServicesReady;
				},
				[clientId, name]
			);

			if (!ALLOWED_BLOCKS.includes(name)) {
				return <BlockEdit {...props} />;
			}

			console.log('pilotLight', pilotLight);

			return (
				<>
					<BlockControls group="other">
						<CopilotToolbarMenu
							blockName={name}
							clientId={clientId}
							isReady={pilotLight}
						/>
					</BlockControls>
					<BlockEdit {...props} />
				</>
			);
		},
	'withPRCCopilotToolbarControls'
);

/**
 * Add PRC Copilot Toolbar Controls to the prc-block/table block.
 */
function initToolbarControls() {
	addFilter(
		'editor.BlockEdit',
		`prc-copilot-toolbar-controls`,
		withPRCCopilotToolbarControls,
		100
	);
}

export { initToolbarControls };

export default initToolbarControls;
