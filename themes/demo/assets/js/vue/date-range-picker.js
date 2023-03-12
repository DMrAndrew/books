import {ref, computed} from 'vue'

export default {
    template: `<q-input v-model="range" dense>
      <template v-slot:append>
        <q-icon name="event" class="cursor-pointer">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-date v-model="date" range mask="DD.MM.YYYY">
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="Применить" color="primary"  />
              </div>
            </q-date>
          </q-popup-proxy>
        </q-icon>
      </template>
    </q-input>`,
    setup() {
        const date = ref()
        const range = computed(() => date.value ? (date.value.to ? (date.value.from + ' - ' + date.value.to) : date.value) : '')
        return {
            date,
            range
        }
    }
}

