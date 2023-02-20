function toggleTextArea(query) {
	const container = document.querySelector(query);
	if(!container) {
		return false;
	}
	container.classList.toggle('visible');
}

function showTextArea(query) {
	const container = document.querySelector(query);
	if(!container) {
		return false;
	}
	container.classList.add('visible');
}

function hideTextArea(query) {
	const container = document.querySelector(query);
	if(!container) {
		return false;
	}
	container.classList.remove('visible');
}