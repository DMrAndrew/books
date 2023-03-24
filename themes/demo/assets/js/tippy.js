function initTippy(){
	//remove oldTippy
	for (let oldContainer of document.querySelectorAll('[data-tippy-root]')){
		oldContainer.remove();
	}
	// init tippy contruct
  const modalContainer = document.querySelectorAll('[data-tippy-continer]');
  for (let block of modalContainer) {
    const init = block.querySelector('[data-tippy-init]');
    const content = block.querySelector('[data-tippy-block]');
    tippy(block, {
      interactive:true,
      arrow: false,
      theme: 'light',
      allowHTML: true,
      placement: 'bottom-start',
      maxWidth: 400,
      content: content.innerHTML
    });
  }
    // simple tippy init
  tippy('[data-tippy-content]', {
    theme: 'light'
  });
}