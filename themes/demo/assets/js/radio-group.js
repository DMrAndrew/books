function radioGroupTabs() {
    const groups = document.querySelectorAll('[data-radio-group]');
    const form = groups[0].closest('form');

    for (let group of groups) {
        const inputs = group.querySelectorAll('input[type=radio]');
        const parents = group.querySelectorAll('[data-radio-parent]');
        const childs = group.querySelectorAll('[data-radio-child]');

        function setParentCheck() {
            for (let parent of parents) {
                const value = parent.value;
                const currentChilds = [...childs].filter(el => el.value === value);
                const allAreSame = currentChilds.every(el => el.checked === currentChilds[0].checked);
                parent.checked = allAreSame ? currentChilds[0]?.checked : false;
                rememberValue(parent, !allAreSame);
            }
        }

        function rememberValue(input, clearFlag) {
            const container = input.closest('[data-radio-container]');
            container.dataset.checked = clearFlag ? null : (input.checked ? input.value : container.dataset.checked);
        }

        // remove checked from input by double click
        for (let input of inputs) {
            const container = input.closest('[data-radio-container]');
            input.addEventListener('click', function () {
                if (container.dataset.checked !== this.value) {
                    rememberValue(input);
                } else {
                    rememberValue(input, true);
                    input.checked = false;
                }
            });
            rememberValue(input);
        }
        // change parent state on child click
        for (let child of childs) {
            child.addEventListener('click', function () {
                setParentCheck();
            });
        }
        // click on parent element
        for (let parent of parents) {
            parent.addEventListener('click', function () {
                for (let child of childs) {
                    const container = child.closest('[data-radio-container]');
                    container.dataset.checked = parent.checked ? parent.value : null;
                    child.checked = child.value == parent.value ? parent.checked : false;
                }
            });
        }
        setParentCheck();
    }

    // clear remembered value on form reset
    form.addEventListener('reset', function () {
        for (let group of groups) {
            setTimeout(() => {
                const allInputs = group.querySelectorAll('input[type=radio]');
                for (let input of allInputs) {
                    rememberValue(input, true)
                    input.checked = false;
                }
                for (let input of allInputs) {
                    rememberValue(input)
                }
            }, 1);
        }
    });
}
