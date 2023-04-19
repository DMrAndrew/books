import date_range_picker from './date-range-picker.js'
import select from './select.js'

for (let event of ['page:loaded']) {
    addEventListener(event, () => {
        console.log(event)
        for (let container of [`#date-range-picker`, `#date-range-picker2`]) {
            window.mountApp(date_range_picker, container)
        }
        window.mountApp(select, '#select')
    });
}

