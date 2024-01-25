function toggleTextArea(query) {
	const container = document.querySelector(query);
	const textarea = container.querySelector('textarea');
	if(!container) {
		return false;
	}
	closeAllTextareBlocks(query);
	container.classList.toggle('visible');
	if(container.classList.contains('visible')){
		textarea.focus();
	}
}

function showTextArea(query) {
	const container = document.querySelector(query);
	const textarea = container.querySelector('textarea');
	if(!container) {
		return false;
	}
	closeAllTextareBlocks();
	container.classList.add('visible');
	textarea.focus();
}

function hideTextArea(query) {
	closeAllTextareBlocks();
}

function closeAllTextareBlocks (query) {
	const currentBlock = document.querySelector(query);
	const allContainer = document.querySelectorAll('.comment-js-form');
	for (let block of allContainer) {
		if(currentBlock && block == currentBlock){
			continue;
		}
		block.classList.remove('visible');
	}
}

let promocodeSubmitLstnr;
addEventListener('render', function() {

	// new updated dom dropdowns
	openDropdown();

	//donation
	if (typeof updateDonateTimeout === 'undefined' || updateDonateTimeout == null) {
		var updateDonateTimeout;
	}
	$('body').on('keyup', '#donate', function (e) {
		e.preventDefault();
		this.value = this.value.replace(/[^\d.]/g, '');

		clearTimeout(updateDonateTimeout);
		updateDonateTimeout = setTimeout(function () {
			clearTimeout(updateDonateTimeout);
			oc.request('#orderForm', 'onOrderAddDonation', {})
		}, 1000);
	});

	// promocode
	if (typeof promocodeSubmitLstnr === 'undefined' || promocodeSubmitLstnr == null) {
		promocodeSubmitLstnr = $('body').on('click', '#promocodeSubmit', function (e) {
			e.preventDefault();

			this.value = this.value.replace(/[^\d.]/g, '');
			oc.request('#orderForm', 'onOrderAddPromocode', {})
		});
	}
});
