import {ref} from 'vue'

export default {
    setup() {
        return {
            modelSingle: ref('Apple'),
            modelMultiple: ref(['Facebook']),

            options: [
                'Google', 'Facebook', 'Twitter', 'Apple', 'Oracle'
            ]
        }
    },
    template: `
         <q-select
                    outlined
                    v-model="modelMultiple"
                    multiple
                :options="options"
                use-chips

                />`
}

