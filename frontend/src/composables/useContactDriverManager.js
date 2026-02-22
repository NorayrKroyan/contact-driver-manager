import { reactive, ref, computed, watch } from 'vue'
import { apiList, apiGet, apiCreate, apiUpdate, apiLookups, apiDelete } from '../api/contactDriverApi'

export function useContactDriverManager() {
    const loading = ref(false)
    const rows = ref([])
    const err = ref('')

    const lookups = reactive({ carriers: [], projects: [], trucks: [], trailers: [], states: [] })
    const filters = reactive({ q: '' })
    const sort = reactive({ key: 'name', dir: 'asc' })

    const modal = reactive({ open: false, loading: false, contactId: null })
    const saving = ref(false)

    const pagination = reactive({ page: 1, limit: 25, total: 0 })

    function setPagination(p) {
        if (!p) return
        if (p.page != null) pagination.page = Number(p.page) || 1
        if (p.limit != null) pagination.limit = Number(p.limit) || 25
        if (p.total != null) pagination.total = Number(p.total) || 0
    }

    const form = reactive({
        contact: { first_name: '', last_name: '', phone_number: '', email: '', address: '', state: '' },
        driver: {
            is_driver: 0,
            id_carrier: '',
            driver_shift: '',
            spanish_language: 0,
            id_vehicle: '',
            id_trailer: '',
            id_device: '',
            idprojects: '',
            mobile_app_pin: '',
            driver_profile_url: '',
            tcs_fuel_card_number: '',
            tcs_fuel_card_pin: '',
            tcs_fuel_card_limit: '',
            tcs_fuel_card_last_updated: '',
        },
    })

    const driverToggle = computed({
        get: () => Number(form.driver.is_driver) === 1,
        set: (v) => {
            form.driver.is_driver = v ? 1 : 0
            if (v) {
                if (form.driver.spanish_language === '' || form.driver.spanish_language == null) {
                    form.driver.spanish_language = 0
                }
                if (form.driver.driver_shift == null) {
                    form.driver.driver_shift = ''
                }
            }
        },
    })

    function resetForm() {
        Object.assign(form.contact, { first_name: '', last_name: '', phone_number: '', email: '', address: '', state: '' })
        Object.assign(form.driver, {
            is_driver: 0,
            id_carrier: '',
            driver_shift: '',
            spanish_language: 0,
            id_vehicle: '',
            id_trailer: '',
            id_device: '',
            idprojects: '',
            mobile_app_pin: '',
            driver_profile_url: '',
            tcs_fuel_card_number: '',
            tcs_fuel_card_pin: '',
            tcs_fuel_card_limit: '',
            tcs_fuel_card_last_updated: '',
        })
    }

    function hydrate(row) {
        const c = row?.contact || {}
        const d = row?.driver || {}

        form.contact.first_name = c.first_name || ''
        form.contact.last_name = c.last_name || ''
        form.contact.phone_number = c.phone_number || ''
        form.contact.email = c.email || ''
        form.contact.address = c.address || ''
        form.contact.state = c.state || ''

        form.driver.is_driver = Number(d.is_driver || 0)
        form.driver.id_carrier = d.id_carrier ?? ''
        form.driver.driver_shift = d.driver_shift ?? ''
        form.driver.spanish_language = d.spanish_language ?? 0
        form.driver.id_vehicle = d.id_vehicle ?? ''
        form.driver.id_trailer = d.id_trailer ?? ''
        form.driver.id_device = d.id_device ?? ''
        form.driver.idprojects = d.idprojects ?? ''
        form.driver.mobile_app_pin = d.mobile_app_pin ?? ''
        form.driver.driver_profile_url = d.driver_profile_url ?? ''
        form.driver.tcs_fuel_card_number = d.tcs_fuel_card_number ?? ''
        form.driver.tcs_fuel_card_pin = d.tcs_fuel_card_pin ?? ''
        form.driver.tcs_fuel_card_limit = d.tcs_fuel_card_limit ?? ''
        form.driver.tcs_fuel_card_last_updated = d.tcs_fuel_card_last_updated ?? ''
    }

    function toggleSort(key) {
        if (sort.key === key) sort.dir = sort.dir === 'asc' ? 'desc' : 'asc'
        else {
            sort.key = key
            sort.dir = 'asc'
        }
    }

    async function loadLookups() {
        try {
            const res = await apiLookups()
            Object.assign(lookups, res?.lookups || {})
        } catch {}
    }

    async function load() {
        err.value = ''
        loading.value = true
        try {
            const res = await apiList({
                limit: pagination.limit,
                page: pagination.page,
                q: filters.q,
                sort: sort.key,
                dir: sort.dir,
            })
            rows.value = res?.rows || []
            setPagination({ total: res?.total ?? 0 })
        } catch (e) {
            err.value = e?.message || 'Load failed'
        } finally {
            loading.value = false
        }
    }

    function openCreate() {
        err.value = ''
        resetForm()
        modal.open = true
        modal.loading = false
        modal.contactId = null // ✅ FIXED
    }

    async function openEdit(contactId) {
        err.value = ''
        modal.open = true
        modal.loading = true
        modal.contactId = contactId
        try {
            const res = await apiGet(contactId)
            hydrate(res?.row)
        } catch (e) {
            err.value = e?.message || 'Failed to load'
        } finally {
            modal.loading = false
        }
    }

    function closeModal() {
        modal.open = false
        modal.loading = false
        modal.contactId = null
        saving.value = false
    }

    async function save() {
        err.value = ''
        saving.value = true
        try {
            const payload = { contact: { ...form.contact }, driver: { ...form.driver } }

            if (!modal.contactId) {
                const res = await apiCreate(payload)
                hydrate(res?.row)
                modal.contactId = res?.row?.contact?.id_contact || null
            } else {
                const res = await apiUpdate(modal.contactId, payload)
                hydrate(res?.row)
            }
        } catch (e) {
            if (e?.response?.status !== 422) err.value = e?.message || 'Save failed'
            throw e
        } finally {
            saving.value = false
        }
    }

    async function remove(contactId) {
        err.value = ''
        await apiDelete(contactId)
    }

    function formatDateTime(v) {
        if (!v) return ''
        return String(v).replace('T', ' ').slice(0, 19)
    }

    let searchTimeout = null
    watch(
        () => filters.q,
        () => {
            clearTimeout(searchTimeout)
            searchTimeout = setTimeout(() => load(), 350)
        }
    )

    return {
        loading,
        rows,
        err,
        lookups,
        filters,
        sort,
        modal,
        saving,
        form,
        driverToggle,
        formatDateTime,
        loadLookups,
        load,
        toggleSort,
        openCreate,
        openEdit,
        closeModal,
        save,
        pagination,
        setPagination,
        remove,
    }
}