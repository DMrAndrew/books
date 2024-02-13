import Thread from "@/classes/Thread";
import {reactive} from "vue";
import ThreadList from './ThreadList'

class Messenger {
    constructor(options = []) {
        this.threads = reactive((new ThreadList()))
    }

}

export default function (data) {
    return new Messenger(data);
}
