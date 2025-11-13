/**
 * App component
 *
 * Main application component that renders the abilities dashboard.
 */

import { useState } from '@wordpress/element';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import type { DataViewsView } from '../types';
import { useAbilities } from '../hooks/useAbilities';
import { getCategoryElements, createFields } from '../utils/dataViewsConfig';
import { ViewDetailsModal } from './ViewDetailsModal';
import { ExecuteAbilityModal } from './ExecuteAbilityModal';

/**
 * Main application component for the Abilities Dashboard
 */
export const App = () => {
	const { abilities, isLoading } = useAbilities();

	// Extract unique categories from abilities for filtering
	const categoryElements = getCategoryElements(abilities);

	// Define fields for DataViews columns
	const fields = createFields(categoryElements);

	// Define the view configuration
	const [view, setView] = useState<DataViewsView>({
		type: 'table',
		perPage: 10,
		page: 1,
		sort: {
			field: 'name',
			direction: 'asc',
		},
		fields: [
			'name',
			'label',
			'category',
			'description',
			'readonly',
			'destructive',
		],
		filters: [],
		search: '',
		hiddenFields: [],
		layout: {
			primaryField: 'name',
		},
	});

	// Use filterSortAndPaginate to process the data based on view configuration
	const { data: processedAbilities, paginationInfo } = filterSortAndPaginate(
		abilities,
		view,
		fields
	);

	// Define default layouts for different view types
	const defaultLayouts = {
		table: {
			primaryField: 'name',
		},
	};

	// Define available actions for each ability
	const actions = [
		{
			id: 'view-details',
			label: 'View Details',
			isPrimary: true,
			RenderModal: ({ items, closeModal }: any) => {
				const ability = items[0];
				return (
					<ViewDetailsModal
						ability={ability}
						closeModal={closeModal}
					/>
				);
			},
		},
		{
			id: 'execute',
			label: 'Execute',
			RenderModal: ({ items, closeModal }: any) => {
				const ability = items[0];
				return (
					<ExecuteAbilityModal
						ability={ability}
						closeModal={closeModal}
					/>
				);
			},
		},
	];

	if (isLoading) {
		return <p>Loading abilities...</p>;
	}

	if (abilities.length === 0) {
		return <p>No abilities found.</p>;
	}

	return (
		<div style={{ padding: '20px' }}>
			<div style={{ marginBottom: '20px' }}>
				<p>
					This dashboard lets you explore and execute{' '}
					<strong>WordPress Abilities</strong> interactively. Use the
					table below to browse available abilities, filter and
					search, view input/output schemas, and run abilities
					directly from the interface.
				</p>
			</div>
			<DataViews
				data={processedAbilities}
				fields={fields}
				view={view}
				onChangeView={setView}
				actions={actions}
				paginationInfo={paginationInfo}
				supportedLayouts={['table']}
				defaultLayouts={defaultLayouts}
			/>
		</div>
	);
};
