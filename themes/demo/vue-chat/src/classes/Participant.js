export class Participant{
    constructor(participant) {
        this._participant = participant
    }

    is(participant){
        return participant.providerID === this.providerID && participant.alias === this.alias
    }
    get providerID(){
        return this._participant.provider_id ?? null
    }

    get alias(){
        return this._participant.provider_alias ?? null
    }
    get participant(){
        return this._participant
    }

    get hasPicture(){
        return this._participant?.base?.picture;
    }
    get picture(){
        return this.hasPicture ? this._participant.base.picture : null
    }

    static make(src) {
        if(!src){
            return Participant.nullObject()
        }
        return src instanceof Participant ? src : Participant.new(src)
    }

    static new(src) {
        return new Proxy(new Participant(src), {
            get(target, prop) {
                if (prop in target) {
                    return target[prop];
                }
                if (target._participant && prop in target._participant) {
                    return target._participant[prop];
                }
                return null;
            }
        })

    }
    static nullObject() {
        return new Proxy({}, {
            get(target, prop) {
                return null;
            }
        });
    }
}