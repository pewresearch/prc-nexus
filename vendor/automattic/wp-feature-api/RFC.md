# RFC: WordPress Feature API

## Summary

The WordPress Feature API is a proposed system for exposing server and client side functionality in WordPress for use in, but not exclusive to, LLMs in order to enhance agentic WordPress systems. It's focused on discoverability and execution across server and client. At its core, it is a very accessible registry of defined WordPress features in the form of resources and tools. We propose leveraging the existing WP REST API to power the functionality underneath the Feature API and providing a javascript `wp.features` module for client-side use.

### What this is NOT

This is not an attempt at introducing AI features and LLMs into WordPress. That's a separate discussion, albeit a good and needed one. However, the intended consumer of the Feature API **is** LLMs and agentic systems that will be built in WordPress. As such, it's not exclusive to being used by LLMs, and offers a standard and distributed way of exposing WP functionality throughout WordPress.

### Problem

LLMs lack a formal way of interacting with WordPress, whether they're called from the client or server. This problem has been addressed by emerging standards like [MCP](https://modelcontextprotocol.io/) that make client LLM-powered software aware of external resources and tools. In WordPress's case, we have end-to-end control of the server and client and therefore this is a means for making it easier to expose WP functionality across it, inspired by MCP concepts in order to align with the larger AI ecosystem.

There's a lot of potential in shared features for WordPress that's pretty simple and easy to implement itself:

-   Update site options
-   Customize styles
-   Search posts
-   Update a post
-   etc.

The list is endless. But they are all easy to define features and useful across WordPress users, AI being one of them. Given some key properties, like descriptions and structured schema to describe the feature, AI is quite capable of using them just like a human developer in WordPress would. This raises two main questions this RFC aims to address:

1. How do we make features discoverable, across the client and server?
2. How do we make features executable, across the client and server?
3. And for extra credit, how do we make this as easy as possible for developers to use?

## Core Features

-   A central registry of features that can be accessed by the client and server, but consolidated under a single API.
-   Easy to register features anywhere in WordPress
-   Scopable and filterable in order to focus on the most relevant features to the specific LLM call.
-   Features can be composed to create more complex workflows

## Use Cases

Let's walk through some common use cases to address how we can use Feature API to solve them. Since we're agnostic to how we call an LLM, I'll use `vercel/ai` as an example for the client, and a hypothetical `ai()` server-side AI SDK.

### Client-Side LLMs

Imagine an AI model operating in WordPress:

```tsx
import { generateText } from 'ai';
import { openai } from '@ai-sdk/openai';

const userMessage = 'I want to update my post';

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	system: 'You are a friendly WordPress assistant!',
	prompt: userMessage,
} );
```

How do we make this model aware of all the features of WordPress and have it use them?

```tsx
import { generateText } from 'ai';
import { openai } from '@ai-sdk/openai';

// register features globally
wp.features.register( 'editor/go_to_post', {
	type: 'tool', // or resource, borrowed from MCP
	description: 'Navigates to a post when in the WordPress editor.',
	input_schema: z.object( { postId: z.string() } ),
	callback: async ( input ) => {
		document.location.href = `post.php?post=${ input.postId }&action=edit`;
	},
} );

// gets all features from the registry
const features = wp.features.get();

// Reformat our features as tools for the LLM
const toolsFromFeatures = function ( features ) {
	return features.map( ( { id, name, description, input_schema } ) => ( {
		id,
		name,
		description,
		parameters: input_schema,
		execute: async ( input ) => {
			return await wp.features.run( id, input );
		},
	} ) );
};

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	system: `You are a friendly WordPress assistant! You must not make up tool parameters, always ask the user for the information needed, or use other tools to get the information needed.`,
	prompt: 'I want to update my post',
} );
```

You see that we're borrowing the terminology from MCP for the `type` of feature. Tools are generally actionable and have effects, whereas resources are generally passive and are used to provide more context. Think of it as the difference between GET and POST requests.

Resources are used to provide more context. Because of this, resources are often registered server-side, because they can expose data over the REST API.

Note that we'll need a post ID for the above example. We can ask the user, or we can provide another tool, provided through a Feature resource, that can be used to get the post ID.

```php
wp_register_feature('core/posts/search', [
    'name' => 'Search Posts',
    'description' => 'Searches for posts by title, content, or slug.',
    'type' => 'resource',
    'input_schema' => [
        'query' => [
          'type' => 'string',
          'description' => 'A simple query to search for posts by title, content, or slug. The simpler and shorter the better, in order to have a hit.',
        ],
    ],
    'output_schema' => [
        'posts' => [
          'type' => 'array',
          'items' => ['type' => 'object', 'properties' => [
            'id' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
          ]],
        ],
    ],
]);
```

Now the AI should respond to the user asking which post and condense that into a simple query to search the posts feature resource, and pass this to the `editor/go_to_post` tool with the fetched post ID. We're relying on the OpenAI tools API to handle this logic, but we could implement a similar workflow manually ourselves using a "router" that chooses the tools needed to fulfill the user's request.

Let's break down how we can call a feature directly. First, let's register some more specific plugin features, like WooCommerce:

```php
wp_register_feature('woocommerce/product/report', [
    'name' => 'WooCommerce Product Report',
    'description' => 'Gets a WooCommerce product report by ID.',
    'type' => 'resource',
    'input_schema' => [
        'productId' => ['type' => 'string'],
    ],
    'output_schema' => [
        'name' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'attributes' => ['type' => 'array'],
        'sales_by_month' => [
          'type' => 'array',
          'items' => ['type' => 'object', 'properties' => [
            'month' => ['type' => 'string'],
            'sales' => ['type' => 'number'],
          ]],
        ],
    ],
    'callback' => function (array $input) {
        $product = get_post($input['productId']);
        $sales = get_post_meta($product->ID, '_total_sales', true);
        $sales_by_month = get_post_meta($product->ID, '_sales_by_month', true);

        return [
            'name' => $product->post_title,
            'description' => $product->post_content,
            'sales' => $sales,
            'sales_by_month' => $sales_by_month,
        ];
    },
]);

wp_register_feature('woocommerce/products', [
    'name' => 'WooCommerce Products',
    'description' => 'Gets a list of WooCommerce products.',
    'type' => 'resource',
    'output_schema' => [
        'products' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id' => ['type' => 'string'],
              'name' => ['type' => 'string'],
              'description' => ['type' => 'string'],
              'price' => ['type' => 'number'],
            ],
          ],
        ],
    ],
]);
```

Now that we've registered this resource, the client can use it:

```tsx
const feature = wp.features.find( 'woocommerce/product/report' );
const report = await feature.run(
	{
		productId: '123',
	},
	// options
	{
		stream: false,
	}
);
```

This calls the REST API endpoint that's been registered by the feature. The request parameters are validated against the input schema of the feature and the result is validated against the output schema before being returned as the response. Server-side features are shared with the client (over REST) and can be used in the same way as client-side features.

Now the LLM can use this feature in a chat and we can start composing more complex AI workflows. Let's register a client feature for displaying a rich message to the user that renders a WooCommerce product report.

```tsx
wp.features.register( 'woocommerce/rich_message/report', {
	id: 'woocommerce/rich_message/report',
	name: 'WooCommerce Rich Message Report',
	type: 'tool',
	description:
		'Displays a rich message to the user that renders a WooCommerce product report.',
	input_schema: z.object( {
		userMessage: z.string(),
		productId: z.string(),
	} ),
	output_schema: z.object( {
		message: z.string(),
		report: z.object( {
			name: z.string(),
			description: z.string(),
			total: z.number(),
			monthly: z.array(
				z.object( {
					month: z.string(),
					amount: z.number(),
				} )
			),
		} ),
	} ),
	callback: generateRichMessageReport,
} );

async function generateRichMessageReport( context, feature ) {
	const { userMessage, productId } = context;
	const { output_schema } = feature;

	const productReportResource = wp.features.find(
		'woocommerce/product/report'
	);
	const report = await productReportResource.run( { productId } );

	// Use the LLM to generate a rich message that complies with this feature's output schema.
	const { text } = await generateText( {
		model: openai( 'gpt-4o' ),
		prompt: `You are a friendly WordPress assistant! Generate a rich message to the user that renders a WooCommerce product report and a response to the user's message.

    User message: ${ userMessage }

    Product report: ${ JSON.stringify( report ) }`,
		schema: output_schema,
	} );

	return text;
}
```

Now when the user asks to see a report for a product, the LLM can generate a rich message to the user that renders a WooCommerce product report.

```tsx
const features = wp.features.get();

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	system: "You are a friendly WordPress assistant! You've been provided a list of tools. If you need to use a tool with parameters, call a tool that can help you identify the parameters. For example, if you need to know the product ID, call the `woocommerce/products` tool.",
	prompt: 'I want to see a report for product 123',
} );
```

### Server-Side LLMs

So far we've been considering client-side AI in order to demonstrate how they can be used with server and client features. However, in many cases we would want to call our AI from the server so that we don't expose too much on the client, like our provider API keys and prompts.

To do this, we can define a server-side AI-driven feature as the entry-point to be called directly from the client.

We call it from the client in exactly the same way:

```tsx
const feature = wp.features.find( 'woocommerce/product/report' );
const report = await feature.run( { productId: '123' } );
```

But register everything as a server-side feature:

```php
wp_register_feature("woocommerce/rich_message/report", [
  "name" => "WooCommerce Rich Message Report",
  "description" => "Displays a rich message to the user that renders a WooCommerce product report.",
  "type" => "tool",
  "input_schema" => array(
    // this is WP Rest compatible schema
    "userMessage" => array(
      "type" => "string",
    ),
    "productId" => array(
      "type" => "string",
    ),
  ),
  "output_schema" => array(
    "message" => array(
      "type" => "string",
    ),
    "report" => array(
      "type" => "object",
      "properties" => array(
        "name" => array(
          "type" => "string",
        ),
        "description" => array(
          "type" => "string",
        ),
        "total" => array(
          "type" => "number",
        ),
        "monthly" => array(
          "type" => "array",
          "items" => array(
            "type" => "object",
            "properties" => array(
              "month" => array(
                "type" => "string",
              ),
              "amount" => array(
                "type" => "number",
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  "callback" => function (array $context, WP_Feature $feature) {
    $report_resource = wp_get_feature("woocommerce/product/report", array('type' => 'resource'));
    $report = $report_resource->call($context);

    // Hypothetical Prompt helper
    $prompt = Prompt::find('woocommerce/rich_message/report')->set_context([
      'userMessage' => $context['userMessage'],
      'productReport' => $report,
    ]);

    return ai()->generate_text()->prompt($prompt)->response()->to_array();
  },
]);
```

## Feature Scope

We can imagine many features being registered and so always passing so many to our LLM would not be feasible. We need some good ways of filtering to the most relevant features for our needs. There's a few ways this can be handled:

### Feature Categories

When registering a feature, provide a category for it. This can then be specified for retrieval:

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "WooCommerce Product Report",
  "description" => "Gets a WooCommerce product report by ID.",
  "type" => "resource",
  "categories" => ["woocommerce", "reporting"],
]);
```

```tsx
const features = wp.features.get( { categories: [ 'woocommerce' ] } );
```

### Filter

For situations where the feature availability is determined dynamically based on the request state, provide a callback that returns a boolean to filter the feature:

```php
wp_register_feature("woocommerce/product/report", [
  "id" => "woocommerce/product/report",
  "name" => "WooCommerce Product Report",
  "description" => "Gets a WooCommerce product report by ID.",
  "type" => "resource",
  "filter" => function (WP_Feature $_feature) {
    return is_woocommerce();
  },
]);
```

Or for the client, filter by client state:

```tsx
wp.features.register( 'core/blocks/edit_color', {
	type: 'tool',
	description:
		'Edits the color of a block for either the background or text.',
	filter: () => {
		const selectedBlockClientId =
			select( blockEditorStore ).getSelectedBlockClientId();

		if ( ! selectedBlockClientId ) {
			return false;
		}

		const selectedBlock = select( blockEditorStore ).getBlock(
			selectedBlockClientId
		);
		const blockType = select( 'core/blocks' ).getBlockType(
			selectedBlock.name
		);

		const hasBackgroundColorSetting =
			blockType?.supports?.color?.background || false;
		const hasTextColorSetting = blockType?.supports?.color?.text || false;

		return hasBackgroundColorSetting || hasTextColorSetting;
	},
} );
```

Now we get out of the box filtering when we retrieve features:

```tsx
const features = wp.features.get( {
	// true by default, so this isn't needed for filtering to apply.
	filter: true,
} );
```

### Schema Matching Features

We can also match features based on the schema of the input or output, which is useful when we've already built up some context and want features that match what we have available to us.

```php
wp_register_feature("bigsky/blocks/edit_color", [
  "name" => "Edit Block Color",
  "description" => "Edits the color of a block for either the background or text.",
  "type" => "feature",
  "input_schema" => array(
    "color" => array(
      "type" => "string",
    ),
    "blockId" => array(
      "type" => "number",
    ),
  ),
  "output_schema" => array(
    "block" => array(
      ...
    ),
  ),
  ...
]);
```

This will return only the features that match the provided context of the client, in this case features that have a blockId property.

```tsx
const ctx = {
	message: 'I want to edit the color of the block',
	blockId: 123,
};

const features = wp.features.get( {
	context: { infer: ctx, strict: false },
} );
```

We infer the schema from our context object and don't do strict matching, so all features with at least a "blockId" property will be returned.

### Features Query

We may want to filter our features based on the user's query. For this, we can do two types of search:

1. Standard keyword based search
2. Semantic search

Please note, I will suggest that registered features have a corresponding repository for their metadata, like the database, and possibly reusing the `posts` table. This is to aid in querying when we have many features registered, or semantic search when that feature eventually surfaces in WordPress.

For any server registered features, including the shallow registration (no callback) of client features, we may query them by either keyword, semantic search, or potentially a hybrid of the two.

Semantic search is not a fully available option yet, due to the lack of vector DB support in WordPress, but we should plan for it to be. We can implement some more inefficient fallbacks for non-vector supported environments directly in PHP. I'm assuming at a minimum, posts will eventually have an `embedding` column so that features can be semantically queried like any other WordPress post content.

Otherwise, we can resort to simple keyword search.

This opens the door for some interesting features, like:

```tsx
const userMessage = 'I want to edit the color of the block';

const { embedding } = await embed( {
	model: openai.embedding( 'text-embedding-3-small' ),
	value: userMessage,
} );

const features = wp.features.get( {
	query: { semantic: { embedding } },
} );

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	prompt: userMessage,
} );
```

If we can declare support for embedding in WordPress through a standard AI SDK, we can lean on this and make the query more developer friendly by embedding internally:

```tsx
const userMessage = 'I want to edit the color of the block';

const features = wp.features.get( {
	query: { semantic: { text: userMessage } },
} );
```

## Client / Server Feature Awareness

Since registration of features can happen on either the server, client or both, it raises the question of how we can make both registries aware of each other.

### Server to Client

Whenever, `wp.features.get` is called, it fetches the list of features from the server over REST. This can be filtered based on the user's criteria as we've already seen.

### Client to Server

For client registered features only, you may simply share the client features as context with your call:

```tsx
const features = wp.features.get( { location: 'client' } );
const feature = wp.features.find( 'bigsky/assistant_router' );
const result = await feature.run( {
	features: features.map( ( { id, description } ) => ( {
		id,
		description,
	} ) ),
	message: 'I want to edit the color of the block',
} );
```

But since this might be a common pattern, we can make things easier by automatically sharing the features with the request:

```tsx
const tool = await feature.run(
	{
		message: 'I want to edit the color of the block',
	},
	{
		shareFeatures: true,
	}
);
```

This will send the available client-only features with the request, and the server will merge them with the server registered features for that request, making them available on the server when you handle the feature call.

Or if you want more granular control, pass a callback that returns the same options that would be used for `wp.features.get`:

```tsx
const tool = await feature.run(
	{
		message: 'I want to edit the color of the block',
	},
	{
		shareFeatures: ( ctx ) => {
			return {
				location: 'client', // setting this to server wouldn't make sense here, since they are already available on the server
				categories: [ 'bigsky' ],
				context: { infer: ctx, strict: false },
			};
		},
	}
);
```

## Integrating with Existing WordPress APIs (Command Palette)

There already exists some very useful "features" in WordPress that tie into this system well, one of them being the editor command palette. How can we leverage this?

### Registering Features from Commands

The command palette already contains the parts needed to register features, so it can be pretty straightforward for the author to declare it:

```js
const command = {
	id: 'custom-command/clear-content',
	name: 'Clear Content',
	label: __( 'Clear all content' ),
	icon: 'trash',
	callback: ( { close } ) => {
		if ( confirm( 'Are you sure you want to clear all content?' ) ) {
			wp.data.dispatch( 'core/block-editor' ).resetBlocks( [] );
			createInfoNotice( 'Content cleared!', { type: 'snackbar' } );
		}
		close();
	},
};
const cmdSchema = z
	.object( {
		close: z.function().optional(),
		open: z.function().optional(),
		isOpen: z.function().optional(),
		search: z.function().optional(),
		history: z.function().optional(),
	} )
	.optional();

useCommand( command );

registerFeature( command.id, {
	name: command.name,
	type: 'tool',
	description: 'Clears all content from the editor.',
	category: [ 'editor', 'command-palette' ],
	input_schema: cmdSchema,
	filter: () => {
		return window.wp.editor !== undefined;
	},
	callback: async ( props, _feature ) => {
		return command.callback( props );
	},
} );
```

Now this command is available to the LLM and can be used in a prompt:

```tsx
const features = wp.features.get( { location: 'client' } );

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	prompt: 'I want to clear all content from the editor.',
} );
```

### Registering Commands from Features

There's also the other route of using what's already registered to power the command palette.

```js
const features = wp.features.get( {
	location: 'client',
	categories: [ 'editor', 'command-palette', 'block-selection' ],
} );

// register each feature as a command
features.forEach( ( feature ) => {
	const command = {
		name: feature.id,
		label: feature.name,
		icon: feature.meta.icon,
		callback: feature.callback,
		context: 'block-selection',
	};

	wp.data.dispatch( wp.commands.store ).registerCommand( command );
} );
```

## Permissions

Of course, we will need to make sure that features are permissive. This would only apply to the server-side features, since the client-side features are already limited by the context of the client.

For server-side features, there is a `permissions` property that can be used to specify the features available to the authenticated user.

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "WooCommerce Product Report",
  "description" => "Gets a WooCommerce product report by ID.",
  "type" => "resource",
  "permissions" => ["manage_woocommerce"],
]);
```

Passing an array defaults to checking the current user's capabilities, or a string to check for their role.

We may also pass a function that returns a boolean for more complex permissions.

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "WooCommerce Product Report",
  "description" => "Gets a WooCommerce product report by ID.",
  "permissions" => function (WP_User $user, WP_Feature $_feature) {
    return $user->has_cap("manage_woocommerce");
  },
]);
```

This could also be done through a `filter` hook, like `feature_user_can`.

```php
add_filter('feature_woocommerce_product_report_user_can', function(WP_User $user, WP_Feature $feature) {
  return $user->has_cap("manage_woocommerce");
}, 10, 3);
```

### Client / Server Separation Rules

Server and client features are kept separate but consiladated when ran.

-   Server registered features are kept under a `server` collection
-   Client registered features are kept under a `client` collection
-   Server registered features without callbacks are considered client, and will expect a client feature to be registered for it.
-   If a callback is provided on both a server and client registered feature, the server will be called first, then the client callback executed with the result of the server callback.
-   If a callback is **only** provided on the client, it will be called exclusively from the client. Note, we still need to check the server over REST to determine if its server counterpart is available.

## Customizability

To hook into features, and customize their behavior, several hooks and filters would be available. Much of the functionality is coming from the REST API, so we can wrap many of the current REST filter/hooks for the features system.

### Example Execution Filters

```php
// Global filter for feature input before execution (before REST call - 'rest_pre_dispatch')
apply_filters('feature_pre_run', array $context, WP_Feature $feature);
apply_filters("feature_{$feature_id}_pre_run", array $context, WP_Feature $feature);
```

### Feature "Middleware" using Hooks

```php
// Rate limiting middleware for resource intensive features like "core/update_post"
add_filter('feature_core_update_post_pre_run', function($context, WP_Feature $feature) {
  $transient_key = "feature_rate_limit_{$feature->id}";
  $rate_limit = get_transient($transient_key);

  if ($rate_limit && $rate_limit >= 100) { // 100 requests max
    return new WP_Error(
      "rate_limit_exceeded",
      "Rate limit exceeded for this feature",
      ["status" => 429]
    );
  }

  set_transient(
    $transient_key,
    ($rate_limit ? $rate_limit + 1 : 1),
    HOUR_IN_SECONDS
  );

  return $context;
}, 10, 2);

// Logging middleware
add_action('feature_post_run', function(WP_Feature $feature, $output, $context) {
  error_log(sprintf(
    "Feature %s executed with context %s and output %s",
    $feature->id,
    wp_json_encode($context),
    wp_json_encode($output)
  ));
}, 10, 4);
```
