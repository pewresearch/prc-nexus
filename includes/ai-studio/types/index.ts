/**
 * Type definitions for the Abilities Dashboard
 */

/**
 * JSON Schema property definition
 */
export interface JSONSchemaProperty {
	type?: 'string' | 'number' | 'integer' | 'boolean' | 'array' | 'object';
	format?: string;
	default?: any;
	enum?: any[];
	items?: {
		type?: string;
		enum?: any[];
	};
	minimum?: number;
	maximum?: number;
	pattern?: string;
	description?: string;
	properties?: Record<string, JSONSchemaProperty>;
	required?: string[];
}

/**
 * JSON Schema definition
 */
export interface JSONSchema {
	type?: string;
	properties?: Record<string, JSONSchemaProperty>;
	required?: string[];
}

/**
 * Ability meta annotations
 */
export interface AbilityAnnotations {
	readonly?: boolean;
	destructive?: boolean;
	idempotent?: boolean;
}

/**
 * Ability meta information
 */
export interface AbilityMeta {
	annotations?: AbilityAnnotations;
	show_in_rest?: boolean;
}

/**
 * Raw ability data from API
 */
export interface RawAbility {
	name: string;
	label: string;
	description: string;
	category: string;
	input_schema: JSONSchema | any[];
	output_schema: JSONSchema;
	meta?: AbilityMeta;
}

/**
 * Transformed ability with additional display fields
 */
export interface Ability extends RawAbility {
	id: number;
	readonly: boolean;
	destructive: boolean;
	idempotent: boolean;
	show_in_rest: boolean;
	input_type: string;
	output_type: string;
}

/**
 * Input values for ability execution
 */
export type InputValues = Record<string, string>;

/**
 * Validation errors
 */
export type ValidationErrors = Record<string, string>;

/**
 * DataViews field definition
 */
export interface DataViewsField {
	id: string;
	label: string;
	enableHiding?: boolean;
	enableSorting?: boolean;
	enableGlobalSearch?: boolean;
	maxWidth?: number;
	elements?: Array<{ value: string; label: string }>;
	filterBy?: {
		operators: string[];
	};
	render?: (props: { item: Ability }) => React.ReactNode;
}

/**
 * DataViews view configuration
 */
export interface DataViewsView {
	type: 'table' | 'grid';
	perPage: number;
	page: number;
	sort: {
		field: string;
		direction: 'asc' | 'desc';
	};
	fields: string[];
	filters: any[];
	search: string;
	hiddenFields: string[];
	layout: {
		primaryField: string;
		mediaField?: string;
	};
}

/**
 * Props for CopyButton component
 */
export interface CopyButtonProps {
	text: string;
	label?: string;
}

/**
 * Props for JSONViewer component
 */
export interface JSONViewerProps {
	data: any;
	expanded?: boolean;
}

/**
 * WordPress Abilities API
 */
export interface WordPressAbilitiesAPI {
	getAbilities: () => Promise<RawAbility[]>;
	executeAbility: (name: string, input: any) => Promise<any>;
}

/**
 * Global WordPress object extension
 */
declare global {
	interface Window {
		wp?: {
			abilities?: WordPressAbilitiesAPI;
		};
	}
}
