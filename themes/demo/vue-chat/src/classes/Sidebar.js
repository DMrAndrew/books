export class Sidebar{
    constructor( mode = 'list') {
        this._mode = mode
        this.list_query = ''
        this.create_query = ''
    }
    get mode(){
        return this._mode
    }

    toCreateMode(){
        this._mode = 'create';
        return this;

    }
    toListMode(){
        this._mode = 'list';
        return this;
    }
}