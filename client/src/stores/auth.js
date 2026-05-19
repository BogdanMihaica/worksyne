import { computed, reactive } from 'vue'
import { http } from '../plugins/http'

const tokenKey = 'worksyne_auth_token'

const state = reactive({
  token: localStorage.getItem(tokenKey),
  user: null,
  isLoading: false,
  error: null,
})

function setToken(token) {
  state.token = token

  if (token) {
    localStorage.setItem(tokenKey, token)
    http.defaults.headers.common.Authorization = `Bearer ${token}`
    return
  }

  localStorage.removeItem(tokenKey)
  delete http.defaults.headers.common.Authorization
}

setToken(state.token)

export const authStore = {
  state,
  isAuthenticated: computed(() => Boolean(state.token)),
  userEmail: computed(() => state.user?.email || ''),
  userRole: computed(() => state.user?.role || ''),
  isAdmin: computed(() => state.user?.role === 'admin'),

  async signIn(credentials) {
    state.isLoading = true
    state.error = null

    try {
      const { data } = await http.post('/api/auth/login', credentials)

      setToken(data.token)
      state.user = await this.fetchUser()

      return state.user
    } catch (error) {
      setToken(null)
      state.user = null
      state.error = error.response?.data?.message || 'Unable to sign in.'
      throw error
    } finally {
      state.isLoading = false
    }
  },

  async fetchUser() {
    if (!state.token) {
      return null
    }

    state.isLoading = true

    try {
      const { data } = await http.get('/api/auth/me')

      state.user = data

      return data
    } catch (error) {
      setToken(null)
      state.user = null
      throw error
    } finally {
      state.isLoading = false
    }
  },

  async signOut() {
    try {
      if (state.token) {
        await http.post('/api/auth/logout')
      }
    } finally {
      setToken(null)
      state.user = null
    }
  },
}
