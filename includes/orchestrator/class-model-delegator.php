<?php
namespace PRC\Platform\Nexus;

/**
 * This class delegate tasks to the appropriate model, based on the needs ot the ability being executed. This may mean some abilities use faster small models, while some some may use larger, slower, more intelligent models.
 */
class Model_Delegator {
	// One idea we could pursue to get this started is for a master array of tool names and functions and then the model and model settings associated with them. This could just be a really big array in this class that we reference when an ability is called. Then we can use that to set the model and settings for the AiClient call.
}
