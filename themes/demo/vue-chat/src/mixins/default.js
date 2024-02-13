export default {
    computed: {
        mode() {
            return process.env.NODE_ENV;
        },
        isProduction() {
            return this.mode === 'production';
        },
        user(){
            return this.$store.getters.user
        },
        messenger(){
            return this.$messenger
        }
    },
}
