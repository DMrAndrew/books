function togglePassword(e) {
    const $button = e.currentTarget;
    const $block = $button.parentElement;
    const $input = $block.querySelector('input');
    const isActive = $input.type == 'text';
    $block.classList.toggle('active', !isActive);
    $input.type = isActive ? 'password' : 'text';
    return;
}
