/**
 * WordPress Dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { ToolbarDropdownMenu, ToolbarButton, Modal, TextControl } from '@wordpress/components';
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
	console.log("We have abilities");
	return (
		<BlockControls group="other">
			<ToolbarDropdownMenu icon={<Sparkles purple />} label="Nexus" controls={abilitiesList.map(ability => {
				return {
					title: ability.label,
					text: ability.label,
					icon: <Sparkles purple />,
					onClick: () => {
						console.log("Executing ability", ability.name);
						window.wp.abilities.executeAbility(ability.name, {
							data_description: "Public approval of abortion for the last 10 years"
						}).then(result => {
							console.log("Ability executed!!", result);
						});
					}
				}
			})}/>
		</BlockControls>
	);
}
