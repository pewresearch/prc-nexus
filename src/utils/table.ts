/**
 * External Dependencies
 */
import TurndownService from 'turndown';

/**
 * Table cell interface for virtual table rendering.
 */
interface TableCell {
	content: string;
	tag: 'th' | 'td';
	isHidden: boolean;
}

/**
 * Table row interface for virtual table rendering.
 */
interface TableRow {
	cells: TableCell[];
}

/**
 * Table attributes interface for virtual table rendering.
 */
interface TableAttributes {
	head?: TableRow[];
	body?: TableRow[];
	foot?: TableRow[];
	[key: string]: TableRow[] | undefined;
}

/**
 * Create a virtual table.
 *
 * @param tableAttributes - The table attributes to create a virtual table from.
 * @return The virtual table.
 */
export function createVirtualTable(tableAttributes: TableAttributes): string {
	let html = '<table>';

	// Helper to render a section (head, body, foot)
	const renderSection = (sectionName: keyof TableAttributes): string => {
		const rows = tableAttributes[sectionName];
		if (!rows || rows.length === 0) return '';
		let tag: string;
		if (sectionName === 'head') {
			tag = 'thead';
		} else if (sectionName === 'body') {
			tag = 'tbody';
		} else {
			tag = 'tfoot';
		}
		return `<${tag}>${rows
			.map(
				(row) =>
					`<tr>${row.cells
						.filter((cell) => !cell.isHidden)
						.map(
							(cell) =>
								`<${cell.tag}>${cell.content}</${cell.tag}>`
						)
						.join('')}</tr>`
			)
			.join('')}</${tag}>`;
	};

	html += renderSection('head');
	html += renderSection('body');
	html += renderSection('foot');
	html += '</table>';
	return html;
}

/**
 * Convert the table to markdown.
 *
 * @param tableAttributes - The table attributes to convert.
 * @return The markdown representation of the table.
 */
export function convertTableToMarkdown(
	tableAttributes: TableAttributes
): string {
	const html = createVirtualTable(tableAttributes);
	const turndownService = new TurndownService();
	const markdown = turndownService.turndown(html);
	return markdown;
}

/**
 * Interface for the result of convertMarkdownToTable.
 */
interface MarkdownToTableResult {
	data: TableAttributes;
	text: {
		before: string;
		after: string;
	};
}

/**
 * Convert markdown table to vTable attributes.
 *
 * @param markdown - The markdown to convert.
 * @return The table attributes.
 */
export function convertMarkdownToTable(
	markdown: string
): MarkdownToTableResult | null {
	// Split the content into lines
	const lines: string[] = markdown.split('\n');

	// Variables to store text before and after the table
	const preTableText: string[] = [];
	const postTableText: string[] = [];
	let tableLines: string[] = [];
	let isInTable = false;
	let tableEnded = false;

	// Process each line to separate table and text
	for (const line of lines) {
		// Detect table start by looking for a line with | characters
		if (!isInTable && line.includes('|') && line.trim().startsWith('|')) {
			isInTable = true;
			tableLines.push(line);
			continue;
		}

		// If we're in the table and find a line without |, table has ended
		if (
			isInTable &&
			(!line.includes('|') || !line.trim().startsWith('|'))
		) {
			isInTable = false;
			tableEnded = true;
		}

		// Add lines to appropriate arrays
		if (isInTable) {
			tableLines.push(line);
		} else if (!tableEnded) {
			if (line.trim()) preTableText.push(line);
		} else if (line.trim()) postTableText.push(line);
	}

	// Filter out empty lines from the table
	tableLines = tableLines.filter((line) => line.trim());

	if (tableLines.length < 3) {
		return null; // Not enough lines for a valid table
	}

	// Parse header row
	const headerCells: TableCell[] = tableLines[0]
		.split('|')
		.filter((cell) => cell.trim())
		.map((cell) => ({
			content: cell.trim(),
			tag: 'th',
			isHidden: false,
		}));

	// Skip separator row (line with dashes)

	// Parse body rows
	const bodyRows: TableRow[] = tableLines.slice(2).map((line) => ({
		cells: line
			.split('|')
			.filter((cell) => cell.trim())
			.map((cell) => ({
				content: cell.trim(),
				tag: 'td',
				isHidden: false,
			})),
	}));

	// Combine pre and post text
	const textContent = {
		before: preTableText.join('\n').trim(),
		after: postTableText.join('\n').trim(),
	};

	return {
		data: {
			head: [
				{
					cells: headerCells,
				},
			],
			body: bodyRows,
			foot: [], // No footer in this markdown table
		},
		text: textContent,
	};
}
