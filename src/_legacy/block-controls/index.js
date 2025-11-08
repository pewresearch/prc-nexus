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
import { NexusToolbarMenu } from './toolbar';

const ALLOWED_BLOCKS = ['prc-block/table', 'prc-quiz/controller'];

const withPRCNexusToolbarControls = createHigherOrderComponent(
	(BlockEdit) =>
		function NexusToolbarControls(props) {
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
						<NexusToolbarMenu
							blockName={name}
							clientId={clientId}
							isReady={pilotLight}
						/>
					</BlockControls>
					<BlockEdit {...props} />
				</>
			);
		},
	'withPRCNexusToolbarControls'
);

/**
 * Add PRC Nexus Toolbar Controls to the prc-block/table block.
 */
function initToolbarControls() {
	addFilter(
		'editor.BlockEdit',
		`prc-nexus-toolbar-controls`,
		withPRCNexusToolbarControls,
		100
	);
}

export { initToolbarControls };

export default initToolbarControls;
