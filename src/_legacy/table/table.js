// Register the nexus toolbar when the component mounts.
	useEffect(() => {
		addToNexusToolbar({
			title: 'Generate Table',
			icon: blockTable,
			toolType: 'request',
			tool: 'get-table-data',
			onRequest: async (request, instructions, tool, clientId, notices) => {
				try{
					const { data, metadata } = await aiGenerateTableData(
						request,
						instructions
					);
					console.log('...data...', data);
					if (!data) {
						notices.createErrorNotice('No data could be generated for your request.');
						return;
					}
					if (data.length > 1) {
						notices.createSuccessNotice(
							'Table generated. Please review results.'
						);
					}

					console.log('data...', clientId, data, metadata);

					const tableData = data.data;
					const textData = data.text;

					const newAttributes = {
						...tableData,
						caption: textData?.before,
						sourceNote: textData?.after,
					};

					const currentAttributes = select(blockEditorStore).getBlockAttributes(clientId);

					const payload = {
						...newAttributes,
						metadata: {
							...currentAttributes.metadata,
							_nexus: [
								...((currentAttributes.metadata && currentAttributes.metadata._nexus) ?? []),
								{
									feature: tool,
									...metadata,
								},
							],
						},
					};

					updateBlockAttributes(clientId, payload);
				} catch ( error ){
					notices.createErrorNotice(error?.message || String(error));
				}
			},
		});
	}, [attributes, updateBlockAttributes, select]);
