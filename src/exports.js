import {
	processToolRequest,
	addToCopilotToolbar,
	removeFromCopilotToolbar,
	CandidatesModal,
	RequestModal,
	CandidatesButton,
} from './';

function loadScript(slug, script) {
	if (!window.prcCopilot[slug]) {
		window.prcCopilot[slug] = script;
	}
}

window.prcCopilot = {};

loadScript('processToolRequest', processToolRequest);
loadScript('addToCopilotToolbar', addToCopilotToolbar);
loadScript('removeFromCopilotToolbar', removeFromCopilotToolbar);
loadScript('CandidatesModal', CandidatesModal);
loadScript('RequestModal', RequestModal);
loadScript('CandidatesButton', CandidatesButton);
console.log('Loading @prc/copilot...', window.prcCopilot);
