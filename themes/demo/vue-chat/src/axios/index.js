import axios from "axios";

axios.interceptors.response.use(
    response => response,
    error => {
        const status = error.response ? error.response.status : null;

        if(window && window.oc){
            window.oc.flashMsg({ text: status, class: 'error' });
        }

        return Promise.reject(error);
    }
)
export default axios.create({
    baseURL: '/api/messenger/'
})
