/**
 * WordPress Dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	ToolbarDropdownMenu,
	ToolbarButton,
	Modal,
	TextControl,
} from '@wordpress/components';
// import { getAbilities, executeAbility } from `@wordpress/abilities`;

/**
 * Internal Dependencies
 */
import { Sparkles } from '../icons';

function AbilitiesToolbarDropdownMenu(props) {
	const { abilitiesList } = props;
	if (abilitiesList.length === 0) {
		return null;
	}
	return (
		<BlockControls group="other">
			<ToolbarDropdownMenu
				icon={<Sparkles purple />}
				label="Nexus"
				controls={abilitiesList.map((ability) => {
					return {
						title: ability.label,
						text: ability.label,
						icon: <Sparkles purple />,
						onClick: () => {
							console.log('Executing ability', ability.name);
							window.wp.abilities
								.executeAbility(ability.name, {
									data_description:
										'Public approval of abortion for the last 10 years',
								})
								.then((result) => {
									console.log('Ability executed!!', result);
								})
								.catch((error) => {
									console.error(
										'Error executing ability',
										error
									);
								})
								.finally(() => {
									console.log('Ability execution completed');
								});
						},
					};
				})}
			/>
		</BlockControls>
	);
}

const withPRCNexusToolbarControls = createHigherOrderComponent(
	(BlockEdit) =>
		function NexusToolbarControls(props) {
			const { name } = props;
			const [abilitiesList, setAbilitiesList] = useState([]);

			// Load the abilities list once.
			useEffect(() => {
				window.wp.abilities.getAbilities().then((abilities) => {
					setAbilitiesList(abilities);
				});
			}, []);

			const matchedAbilitiesList = useMemo(() => {
				return abilitiesList.filter((ability) =>
					ability.meta?.allowed_blocks?.includes(name)
				);
			}, [abilitiesList, name]);

			return (
				<>
					<AbilitiesToolbarDropdownMenu
						abilitiesList={matchedAbilitiesList}
					/>
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
