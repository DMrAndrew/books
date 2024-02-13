export default {
    computed: {
        mode() {
            return process.env.NODE_ENV;
        },
        isProduction() {
            return this.mode === 'production';
        },
        user() {
            return JSON.parse(localStorage.getItem('user'))
        },
        messenger() {
            return this.$messenger.value
        }
    },
    methods: {
        momentize(date) {
            return moment(date, 'DD-MM-YY')
        },
    }
}
