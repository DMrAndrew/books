export default class Message{
    constructor(message) {
        this.message = message
        this.body = message.body
        this.owner = message.owner?.name ?? ''
        this.updated_at = moment(message.updated_at)
        this.created_at = moment(message.created_at)
        this.time = this.updated_at.format('HH:mm')
    }
    static make(src){
        return     new Proxy(new Message(src), {
            get(target, prop) {
                if (prop in target) {
                    return target[prop];
                } else {
                    return target.message[prop];
                }
            }
        })

    }

}
