import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export interface User {
    id: string
    name: string
    email: string
    account: {
        id: string
        name: string
        plan: string
        status: string
    }
}

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(localStorage.getItem('token'))
    const user = ref<User | null>(null)

    const isAuthenticated = computed(() => !!token.value)

    // Configure axios
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }

    async function login(email: string, password: string) {
        const response = await axios.post('/api/auth/login', { email, password })
        token.value = response.data.access_token
        user.value = response.data.user

        localStorage.setItem('token', token.value!)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }

    async function register(data: {
        name: string
        email: string
        password: string
        password_confirmation: string
        account_name: string
    }) {
        await axios.post('/api/auth/register', data)
    }

    async function fetchUser() {
        if (!token.value) return

        const response = await axios.get('/api/auth/me')
        user.value = response.data
    }

    async function logout() {
        await axios.post('/api/auth/logout')
        token.value = null
        user.value = null

        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
    }

    return {
        token,
        user,
        isAuthenticated,
        login,
        register,
        fetchUser,
        logout
    }
})
