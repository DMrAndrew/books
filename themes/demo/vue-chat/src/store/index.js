import { createStore } from 'vuex'
export default createStore({
  state: {
  },
  getters: {
      user (state) {
          return JSON.parse(localStorage.getItem('user' ))
      }
  },
  mutations: {
  },
  actions: {
  },
  modules: {
  }
})
