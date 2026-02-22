import { http } from './http'

// NOTE: all endpoints are relative and start with /api/

export async function apiLookups() {
    const { data } = await http.get('/api/contact-driver/lookups')
    return data
}

export async function apiList(params) {
    // params: { limit, page, q, sort, dir }
    const { data } = await http.get('/api/contact-driver', { params })
    return data
}

export async function apiGet(contactId) {
    const { data } = await http.get(`/api/contact-driver/${contactId}`)
    return data
}

export async function apiCreate(payload) {
    const { data } = await http.post('/api/contact-driver', payload)
    return data
}

export async function apiUpdate(contactId, payload) {
    const { data } = await http.put(`/api/contact-driver/${contactId}`, payload)
    return data
}

export async function apiDelete(contactId) {
    const { data } = await http.delete(`/api/contact-driver/${contactId}`)
    return data
}