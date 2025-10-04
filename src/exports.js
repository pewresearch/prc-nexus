import {
	processToolRequest,
	addToNexusToolbar,
	removeFromNexusToolbar,
	CandidatesModal,
	RequestModal,
	CandidatesButton,
} from './';

function loadScript(slug, script) {
	if (!window.prcNexus[slug]) {
		window.prcNexus[slug] = script;
	}
}

window.prcNexus = {};

loadScript('processToolRequest', processToolRequest);
loadScript('addToNexusToolbar', addToNexusToolbar);
loadScript('removeFromNexusToolbar', removeFromNexusToolbar);
loadScript('CandidatesModal', CandidatesModal);
loadScript('RequestModal', RequestModal);
loadScript('CandidatesButton', CandidatesButton);
console.log('Loading @prc/nexus...', window.prcNexus);
