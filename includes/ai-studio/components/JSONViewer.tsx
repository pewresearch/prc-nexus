/**
 * JSONViewer component
 * 
 * A component that displays JSON data in a formatted, readable way.
 */

import type { JSONViewerProps } from '../types';

/**
 * Displays JSON data with syntax highlighting and formatting
 * 
 * @param props - Component props
 * @param props.data - Data to display as JSON
 * @param props.expanded - Whether to show expanded view (not currently used)
 */
export const JSONViewer = ({ data, expanded = false }: JSONViewerProps) => {
	return <div className="json-viewer">{JSON.stringify(data, null, 2)}</div>;
};
