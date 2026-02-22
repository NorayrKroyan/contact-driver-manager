import axios from 'axios'

export const http = axios.create({
    baseURL: '', // ✅ same-origin; Vite will proxy /api/* to Laravel
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    withCredentials: false, // ✅ not needed for your current API
})