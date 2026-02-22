export function digitsOnly(v) {
    return String(v || '').replace(/\D+/g, '')
}

export function formatUsPhoneFromDigits(input) {
    const digits = digitsOnly(input)
    if (digits.length === 11 && digits.startsWith('1')) {
        return `1-${digits.slice(1, 4)}-${digits.slice(4, 7)}-${digits.slice(7, 11)}`
    }
    if (digits.length === 10) {
        return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6, 10)}`
    }
    return String(input || '')
}

export function isValidUsPhoneFormatted(v) {
    const s = String(v || '').trim()
    return /^(\d{3}-\d{3}-\d{4}|\d-\d{3}-\d{3}-\d{4})$/.test(s)
}

export function isValidEmailFormat(v) {
    const s = String(v || '').trim()
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)
}

/**
 * UI defaults BEFORE SAVE:
 * - NEW drivers only: project=10 if empty
 * - NEW drivers only: pin=last4(phone) if empty
 */
export function applyDriverDefaultsBeforeSave({ form, driverToggle, contactId }) {
    if (!driverToggle) return
    const isNew = !contactId
    if (!isNew) return

    if (!form?.driver?.idprojects) {
        form.driver.idprojects = 10
    }

    const phoneDigits = digitsOnly(form?.contact?.phone_number)
    if (!form?.driver?.mobile_app_pin && phoneDigits.length >= 4) {
        form.driver.mobile_app_pin = phoneDigits.slice(-4)
    }
}

/**
 * Returns:
 * { ok: boolean, errors: Record<string,string>, formError: string }
 */
export function validateContactDriver({ form, driverToggle }) {
    const errors = {}

    const phone = String(form?.contact?.phone_number || '').trim()
    if (!phone) errors['contact.phone_number'] = 'Phone is required.'
    else if (!isValidUsPhoneFormatted(phone)) {
        errors['contact.phone_number'] = 'Phone must be ###-###-#### or 1-###-###-####.'
    }

    const email = String(form?.contact?.email || '').trim()

    if (email && !isValidEmailFormat(email)) {
        errors['contact.email'] = 'Email must be like name@company.com.'
    }

    if (driverToggle) {
        const carrier = form?.driver?.id_carrier
        if (!carrier) errors['driver.id_carrier'] = 'Carrier is required when Is a Driver is checked.'
    }

    const ok = Object.keys(errors).length === 0
    return {
        ok,
        errors,
        formError: ok ? '' : 'Please fix the highlighted fields.',
    }
}

export function flattenLaravelErrors(err) {
    const data = err?.response?.data || err?.data || null
    const e = data?.errors || null

    if (e && typeof e === 'object') {
        const flat = {}
        Object.keys(e).forEach((k) => {
            const v = e[k]
            flat[k] = Array.isArray(v) ? String(v[0] || '') : String(v || '')
        })
        return {
            ok: true,
            errors: flat,
            formError: data?.message || 'Please fix the highlighted fields.',
        }
    }

    return {
        ok: false,
        errors: {},
        formError: data?.message || err?.message || 'Save failed. Please try again.',
    }
}