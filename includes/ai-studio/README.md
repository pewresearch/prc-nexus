# Source Code Documentation

This directory contains the TypeScript source code for the Abilities Dashboard, organized into a modular architecture for better maintainability and scalability.

## Directory Structure

```
src/
├── components/          # React components
├── hooks/              # Custom React hooks
├── services/           # API services
├── utils/              # Utility functions
├── types/              # TypeScript type definitions
├── index.tsx           # Application entry point
└── style.scss          # Styles
```

## Components

### `App.tsx`

The main application component that:

- Manages the overall dashboard state
- Configures DataViews for displaying abilities
- Defines actions for viewing details and executing abilities
- Renders the abilities table with filtering and pagination

### `CopyButton.tsx`

A reusable button component that copies text to clipboard.

**Props:**

- `text: string` - The text to copy
- `label?: string` - Button label (default: 'Copy')

### `JSONViewer.tsx`

Displays JSON data in a formatted, readable view.

**Props:**

- `data: any` - Data to display
- `expanded?: boolean` - Whether to show expanded view

### `InputField.tsx`

Renders an input field based on JSON schema property definition. Supports multiple input types:

- Text input
- Number input
- Checkbox (boolean)
- Select dropdown (enum)
- Multi-select (array with enum items)
- JSON array/object textarea

**Props:**

- `fieldKey: string` - Property key
- `prop: JSONSchemaProperty` - Schema property definition
- `isRequired: boolean` - Whether field is required
- `inputValues: InputValues` - Current input values
- `inputErrors: ValidationErrors` - Validation errors
- `onValueChange: (key: string, value: string) => void` - Value change handler
- `onErrorClear: (key: string) => void` - Error clear handler

### `ViewDetailsModal.tsx`

Modal for displaying detailed information about an ability including:

- Ability metadata (name, label, description, category)
- Annotations (readonly, destructive, idempotent, show_in_rest)
- Input and output schemas with copy buttons

**Props:**

- `ability: Ability` - The ability to display
- `closeModal: () => void` - Function to close modal

### `ExecuteAbilityModal.tsx`

Modal for executing abilities with:

- Input parameter form generation based on schema
- Input validation
- Example input generation
- Result display with copy functionality

**Props:**

- `ability: Ability` - The ability to execute
- `closeModal: () => void` - Function to close modal

## Hooks

### `useAbilities()`

Custom hook for fetching and managing abilities data.

**Returns:**

```typescript
{
  abilities: Ability[];  // Array of transformed abilities
  isLoading: boolean;    // Loading state
}
```

### `useAbilityExecution(ability: Ability)`

Custom hook for managing ability execution state and logic.

**Returns:**

```typescript
{
  isExecuting: boolean;
  result: any | null;
  error: string | null;
  inputValues: InputValues;
  inputErrors: ValidationErrors;
  setInputValues: (values: InputValues) => void;
  setInputErrors: (errors: ValidationErrors) => void;
  execute: () => Promise<void>;
}
```

## Services

### `abilities.ts`

WordPress Abilities API wrapper service.

**Functions:**

- `getAbilities(): Promise<RawAbility[]>` - Fetches all abilities
- `executeAbility(abilityName: string, input: any): Promise<any>` - Executes an ability

## Utilities

### `abilityTransform.ts`

Data transformation utilities.

**Functions:**

- `transformAbilities(abilitiesData: RawAbility[]): Ability[]` - Transforms raw abilities for display
- `generateExampleInput(schema: JSONSchema | any[]): Record<string, any> | null` - Generates example input from schema

### `validation.ts`

Input validation utilities.

**Functions:**

- `validateInput(inputValues: InputValues, schema: JSONSchema | any[]): ValidationErrors` - Validates input against schema

### `dataViewsConfig.ts`

DataViews configuration utilities.

**Functions:**

- `getCategoryElements(abilities: Ability[])` - Extracts unique categories for filtering
- `createFields(categoryElements)` - Creates DataViews field definitions

## Types

All TypeScript type definitions are centralized in `types/index.ts`:

- `JSONSchemaProperty` - JSON Schema property definition
- `JSONSchema` - JSON Schema definition
- `AbilityAnnotations` - Ability meta annotations
- `AbilityMeta` - Ability meta information
- `RawAbility` - Raw ability from API
- `Ability` - Transformed ability with display fields
- `InputValues` - Input values for ability execution
- `ValidationErrors` - Validation errors
- `DataViewsField` - DataViews field definition
- `DataViewsView` - DataViews view configuration
- `CopyButtonProps` - CopyButton component props
- `JSONViewerProps` - JSONViewer component props
- `WordPressAbilitiesAPI` - WordPress Abilities API interface

## Styling

Styles are organized in `style.scss` with the following sections:

- DataViews imports
- Custom dashboard styles
- JSON Viewer styles
- Input field styles
- Multi-select styles
- Input parameters container
- Modal content styles
- Button container

## Building

The project uses `@wordpress/scripts` for building:

```bash
npm run build  # Production build
npm run start  # Development mode with watch
```

## TypeScript Configuration

TypeScript is configured with:

- Strict type checking enabled
- React JSX support
- ES2020 target
- Modern module resolution
- DOM type definitions included

See `tsconfig.json` for full configuration.
