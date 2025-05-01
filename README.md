# PRC Copilot

AI services and tools for PRC Platform.

## Overview

PRC Copilot is a WordPress plugin that provides AI services and tools for the PRC Platform. It integrates with the `ai-services` plugin and provides specialized AI capabilities focused on table data manipulation and generation.

## Requirements

- WordPress 6.7+
- PHP 8.2+
- Required Plugins:
  - prc-platform-core
  - ai-services

## Features

### Table-Related AI Services

The plugin provides three main AI features for table manipulation:

#### 1. Table Caption Generation
- Feature ID: `get-table-caption`
- Takes a markdown table as input
- Generates multiple caption options
- Returns results as a JSON array of strings
- Best option is placed as the first element
- Follows Pew Research Center style and voice
- Will return error message for non-tabular data

#### 2. Table Title Generation
- Feature ID: `get-table-title`
- Takes a markdown table as input
- Highlights the most important data points
- Specially handles population and percentage data
- Returns multiple title options as a JSON array
- Best option is placed as the first element
- Follows Pew Research Center style and voice
- Will return error message for non-tabular data

#### 3. Table Data Generation
- Feature ID: `get-table-data`
- Generates markdown tables from descriptions
- Sources data exclusively from Pew Research Center
- Focuses on:
  - Reports
  - Short reads
  - Fact sheets
- Handles temporal data requirements:
  - Specific years
  - Year ranges
- Returns only the markdown table
- Includes validation and error handling
- Will return detailed error message if data cannot be found

### Integration Features

#### Jetpack AI Integration
- Actively disables Jetpack's AI Assistant to prevent conflicts
- Filters out 'ai-assistant' and 'ai-assistant-support' extensions

## License

This project is licensed under the GPL-2.0+ License - see the [LICENSE](LICENSE) file for details.
