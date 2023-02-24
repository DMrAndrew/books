function filterOpen(e) {
	e && e.stopPropagation();
    e && e.preventDefault();
	const filterBlock = document.querySelector('.listing-book__filters');
	filterBlock.classList.add('active');
	document.filterOpened = true;
    document.body.style.overflow = 'hidden';
}
function filterClose(e) {
    e && e.stopPropagation();
    e && e.preventDefault();
	const filterBlock = document.querySelector('.listing-book__filters');
	filterBlock.classList.remove('active');
    document.filterOpened = false;
    document.body.style.overflow = 'initial';
}

