export default {
    data: () => {
        return {
            scrollable_offset: 0,
            loading_next_page: false,
            infScrollDirection: 'bottom',
            y:0
        }
    },
    computed: {
        scrollable() {
            throw new Error('To be implemented')
        },
        isTopDirection(){
            return this.infScrollDirection === 'top'
        }
    },
    methods: {
        scrollend(e) {
            let [, , top] = this.scrollableProps()
            this.y = top
        },
        async loadNext(promise) {
            if (!this.loading_next_page) {
                this.loading_next_page = true
                this.rememberScrollPosition()
                await promise().finally(() => {
                    this.returnScroll()
                    this.loading_next_page = false
                })
            }
        },
        rememberScrollPosition() {
            if(!this.isTopDirection){
                return
            }
            let [h, , top] = this.scrollableProps()
            this.scrollable_offset = h - top
        },
        returnScroll() {
            if(!this.isTopDirection){
                return
            }
            let [h] = this.scrollableProps()
            this.moveScrollableTo(h - this.scrollable_offset)
        },
        scrollableProps() {
            const el = this.scrollable
            return [
                el.scrollHeight,
                el.clientHeight,
                el.scrollTop,
            ];

        },
        async scrollToBottom(smooth = true) {
            await this.scrollable.scrollTo({
                top: this.scrollable.scrollHeight,
                behavior: smooth ? "smooth" : "instant"
            })
        },
        moveScrollableTo(y) {
            if (!y) {
                return
            }
            this.scrollable.scrollTop = y

        }
    },
    activated() {
        this.moveScrollableTo(this.y)
    },
}