export default class Message {
    constructor(message) {
        this.message = message
        this.body = message.body
        this.owner = message.owner?.name
        this.picture = message.owner?.base.picture
        this.updated_at = moment(message.updated_at)
        this.created_at = moment(message.created_at)
        this.time = this.updated_at.format('HH:mm')
    }

    static make(src) {
        return src instanceof Message ? src : Message.new(src)
    }

    static new(src){
        return new Proxy(new Message(src), {
            get(target, prop) {
                switch (true) {
                    case prop in target: {
                        return target[prop];
                    }
                    case prop in target.message: {
                        return target.message[prop];
                    }
                    default: {
                        return null;
                    }
                }
            }
        })

    }
}
