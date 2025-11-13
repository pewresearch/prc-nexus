# Public facing AI "Knowledge Graph"

Needs a matching wp ability to search the knowledge graph and get a tailored and checked summary.

A custom post type, called, `knowledge_graph`.

- The post body is the topic overview.
- Should have access to `category` taxonomy "topics"

- We need a traffic analysis layer to determine traffic from Parsely for an object or ideally top posts for a topic. We could utilize their recommendations engine potentially as a source and it can take prc related posts in as a source as well.

This will be a "knowledge graph" on every topic that Pew Research Center covers.
From here we'll consolidate all info from posts about that topic. The results from this analysis should be:
a.) A list of relevant posts based on traffic/parsely data (ai enhanced search).
b.) A list of relevant other materials, attachments, charts (ai enhanced search).
c.) A "topic overview" (ai created and static, but can be updated easily from a and b)
d.) Related topics that are adjacent to this topic (ai enhanced search).
