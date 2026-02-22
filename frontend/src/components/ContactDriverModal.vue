<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="onClose"></div>

    <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
        <div class="text-lg font-semibold">{{ contactId ? 'Edit Contact / Driver' : 'New Contact' }}</div>
        <button class="rounded-lg px-2 py-1 text-sm hover:bg-gray-100" @click="onClose">✕</button>
      </div>

      <div class="max-h-[75vh] overflow-y-auto px-5 py-4">
        <div v-if="loading" class="py-10 text-center text-gray-500">Loading...</div>

        <div v-else class="space-y-3">
          <!-- CONTACT -->
          <div class="rounded-xl border border-gray-200 p-4">
            <div class="mb-2 text-sm font-semibold text-gray-900">Contact Info</div>

            <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
              <FieldRow label="First Name:" class="md:col-span-1">
                <div>
                  <input
                      v-model="form.contact.first_name"
                      class="w-full rounded-lg border px-3 py-2 text-sm"
                      :class="inputErr('contact.first_name')"
                      @input="clearErr('contact.first_name')"
                  />
                  <ErrText :msg="errors['contact.first_name']" />
                </div>
              </FieldRow>

              <FieldRow label="Last Name:" class="md:col-span-1">
                <div>
                  <input
                      v-model="form.contact.last_name"
                      class="w-full rounded-lg border px-3 py-2 text-sm"
                      :class="inputErr('contact.last_name')"
                      @input="clearErr('contact.last_name')"
                  />
                  <ErrText :msg="errors['contact.last_name']" />
                </div>
              </FieldRow>

              <FieldRow label="Phone:" class="md:col-span-1">
                <div>
                  <input
                      v-model="form.contact.phone_number"
                      class="w-full max-w-[260px] rounded-lg border px-3 py-2 text-sm"
                      maxlength="14"
                      placeholder="1-###-###-####"
                      :class="inputErr('contact.phone_number')"
                      @input="clearErr('contact.phone_number')"
                      @blur="onPhoneBlur"
                  />
                  <ErrText :msg="errors['contact.phone_number']" />
                </div>
              </FieldRow>

              <FieldRow label="Email:" class="md:col-span-1">
                <div>
                  <input
                      v-model="form.contact.email"
                      class="w-full max-w-[360px] rounded-lg border px-3 py-2 text-sm"
                      placeholder="name@company.com"
                      :class="inputErr('contact.email')"
                      @input="clearErr('contact.email')"
                  />
                  <ErrText :msg="errors['contact.email']" />
                </div>
              </FieldRow>

              <FieldRow label="Address:" class="md:col-span-1">
                <div>
                  <textarea
                      v-model="form.contact.address"
                      rows="2"
                      class="w-full resize-none rounded-lg border px-3 py-2 text-sm"
                      :class="inputErr('contact.address')"
                      @input="clearErr('contact.address')"
                  />
                  <ErrText :msg="errors['contact.address']" />
                </div>
              </FieldRow>

              <FieldRow label="Home State:" class="md:col-span-1">
                <div>
                  <div
                      class="w-full max-w-[220px] rounded-lg"
                      :class="vselectWrapErr('contact.state')"
                  >
                    <VSelect
                        :options="lookups.states"
                        :reduce="s => s.state_code"
                        label="state_name"
                        :placeholder="''"
                        v-model="form.contact.state"
                        @update:modelValue="clearErr('contact.state')"
                    />
                  </div>
                  <ErrText :msg="errors['contact.state']" />
                </div>
              </FieldRow>

              <div class="hidden md:block md:col-span-1"></div>

              <FieldRow label="Is a Driver:" class="md:col-span-1 -mt-5">
                <div class="w-full max-w-[220px]">
                  <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300"
                      :checked="driverToggle"
                      @change="onToggleDriver($event.target.checked)"
                  />
                </div>
              </FieldRow>

              <div class="hidden md:block md:col-span-1"></div>
            </div>
          </div>

          <!-- DRIVER -->
          <div v-if="driverToggle" class="rounded-xl border border-gray-200 p-4">
            <div class="mb-2 text-sm font-semibold text-gray-900">Driver Info</div>

            <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
              <FieldRow label="Carrier:" class="md:col-span-1">
                <div>
                  <div class="rounded-lg" :class="vselectWrapErr('driver.id_carrier')">
                    <VSelect
                        :options="lookups.carriers"
                        :reduce="c => c.id"
                        label="name"
                        placeholder="Select Carrier"
                        v-model="form.driver.id_carrier"
                        @update:modelValue="clearErr('driver.id_carrier')"
                    />
                  </div>
                  <ErrText :msg="errors['driver.id_carrier']" />
                </div>
              </FieldRow>

              <FieldRow label="Project:" class="md:col-span-1">
                <div class="rounded-lg">
                  <VSelect
                      :options="lookups.projects"
                      :reduce="p => p.id"
                      label="name"
                      placeholder="Select Project"
                      v-model="form.driver.idprojects"
                  />
                </div>
              </FieldRow>

              <FieldRow label="Shift:" class="md:col-span-1">
                <div class="flex flex-wrap items-center gap-4">
                  <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input type="radio" class="h-4 w-4" v-model="form.driver.driver_shift" value="" />
                    No Preference
                  </label>
                  <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input type="radio" class="h-4 w-4" v-model="form.driver.driver_shift" :value="0" />
                    Night
                  </label>
                  <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input type="radio" class="h-4 w-4" v-model="form.driver.driver_shift" :value="1" />
                    Day
                  </label>
                </div>
              </FieldRow>

              <FieldRow label="Spanish:" class="md:col-span-1">
                <div class="flex flex-wrap items-center gap-4">
                  <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input type="radio" class="h-4 w-4" v-model="form.driver.spanish_language" :value="0" />
                    No
                  </label>
                  <label class="inline-flex items-center gap-2 text-sm text-gray-800">
                    <input type="radio" class="h-4 w-4" v-model="form.driver.spanish_language" :value="1" />
                    Yes
                  </label>
                </div>
              </FieldRow>

              <FieldRow label="Truck:" class="md:col-span-1">
                <div class="rounded-lg">
                  <VSelect
                      :options="lookups.trucks"
                      :reduce="v => v.id"
                      label="vehicle_name"
                      placeholder="Select truck"
                      v-model="form.driver.id_vehicle"
                  />
                </div>
              </FieldRow>

              <FieldRow label="Trailer:" class="md:col-span-1">
                <div class="rounded-lg">
                  <VSelect
                      :options="lookups.trailers"
                      :reduce="v => v.id"
                      label="vehicle_name"
                      placeholder="Select trailer"
                      v-model="form.driver.id_trailer"
                  />
                </div>
              </FieldRow>

              <FieldRow label="GPS Device:" class="md:col-span-1">
                <input v-model="form.driver.id_device" class="w-full max-w-[220px] rounded-lg border px-3 py-2 text-sm" />
              </FieldRow>

              <FieldRow label="Mobile App PIN:" :nowrapLabel="true" class="md:col-span-1">
                <input v-model="form.driver.mobile_app_pin" maxlength="4" class="w-full max-w-[140px] rounded-lg border px-3 py-2 text-sm" />
              </FieldRow>
            </div>

            <div class="mt-3 rounded-lg bg-gray-50 p-4">
              <div class="text-sm font-semibold text-gray-800">TCS Fuel</div>

              <div class="mt-2 grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
                <FieldRow label="Card #:" class="md:col-span-1">
                  <input v-model="form.driver.tcs_fuel_card_number" maxlength="10" class="w-full max-w-[220px] rounded-lg border px-3 py-2 text-sm" />
                </FieldRow>

                <FieldRow label="PIN:" class="md:col-span-1">
                  <input v-model="form.driver.tcs_fuel_card_pin" maxlength="4" class="w-full max-w-[140px] rounded-lg border px-3 py-2 text-sm" />
                </FieldRow>

                <FieldRow label="Limit:" class="md:col-span-1">
                  <input v-model="form.driver.tcs_fuel_card_limit" class="w-full max-w-[220px] rounded-lg border px-3 py-2 text-sm" />
                </FieldRow>

                <FieldRow label="Last Updated:" class="md:col-span-1">
                  <div class="text-sm text-gray-800">
                    {{ formatPrettyDateTime(form.driver.tcs_fuel_card_last_updated) || '—' }}
                  </div>
                </FieldRow>
              </div>
            </div>
          </div>

          <div v-if="formError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ formError }}
          </div>
        </div>
      </div>

      <!-- FOOTER with DELETE -->
      <div class="flex items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
        <div class="flex items-center">
          <button
              v-if="contactId"
              class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="onDeleteClick"
          >
            <span v-if="!confirmingDelete">Delete</span>
            <span v-else>Confirm delete</span>
          </button>

          <button
              v-if="contactId && confirmingDelete"
              class="ml-2 rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="cancelDelete"
          >
            Cancel
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="onClose"
          >
            Cancel
          </button>

          <button
              class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="onSaveClick"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineComponent, h, watch } from 'vue'
import VSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'

import {
  formatUsPhoneFromDigits,
  applyDriverDefaultsBeforeSave,
  validateContactDriver,
  flattenLaravelErrors,
} from '../composables/contactDriverModalUtils'

const props = defineProps({
  open: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  contactId: { type: [Number, String, null], default: null },
  saving: { type: Boolean, default: false },
  deleting: { type: Boolean, default: false },
  lookups: { type: Object, required: true },
  form: { type: Object, required: true },
  driverToggle: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'save', 'delete', 'update:driverToggle'])

const confirmingDelete = ref(false)
const errors = ref({})
const formError = ref('')

function clearErr(key) {
  if (!errors.value[key]) return
  const next = { ...errors.value }
  delete next[key]
  errors.value = next
}
function clearAllErrs() {
  errors.value = {}
  formError.value = ''
  confirmingDelete.value = false
}

function inputErr(key) {
  return errors.value[key] ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-300'
}
function vselectWrapErr(key) {
  return errors.value[key] ? 'ring-2 ring-red-100 border border-red-400' : ''
}

function onPhoneBlur() {
  const raw = props.form?.contact?.phone_number
  props.form.contact.phone_number = formatUsPhoneFromDigits(raw)
}

function onToggleDriver(checked) {
  emit('update:driverToggle', checked)
  if (!checked) {
    Object.keys(errors.value).forEach((k) => {
      if (k.startsWith('driver.')) clearErr(k)
    })
    formError.value = ''
  }
}

function onSaveClick() {
  onPhoneBlur()

  applyDriverDefaultsBeforeSave({
    form: props.form,
    driverToggle: props.driverToggle,
    contactId: props.contactId,
  })

  const v = validateContactDriver({
    form: props.form,
    driverToggle: props.driverToggle,
  })

  errors.value = v.errors
  formError.value = v.formError

  if (!v.ok) return
  emit('save')
}

function onDeleteClick() {
  if (!confirmingDelete.value) {
    confirmingDelete.value = true
    return
  }
  emit('delete')
  confirmingDelete.value = false
}

function cancelDelete() {
  confirmingDelete.value = false
}

function onClose() {
  clearAllErrs()
  emit('close')
}

watch(
    () => props.open,
    (v) => {
      if (!v) clearAllErrs()
    }
)

function applyServerErrors(err) {
  const out = flattenLaravelErrors(err)
  if (out.ok) {
    errors.value = out.errors
    formError.value = out.formError
    return true
  }
  formError.value = out.formError
  return false
}

defineExpose({ applyServerErrors, clearAllErrs })

function formatPrettyDateTime(v) {
  if (!v) return ''
  const s = String(v).replace('T', ' ').slice(0, 19)
  const [datePart, timePart] = s.split(' ')
  if (!datePart || !timePart) return s

  const [y, m, d] = datePart.split('-').map(Number)
  const [hh, mm] = timePart.split(':').map(Number)
  if (!y || !m || !d || Number.isNaN(hh) || Number.isNaN(mm)) return s

  let hour12 = hh % 12
  if (hour12 === 0) hour12 = 12
  const ampm = hh >= 12 ? 'PM' : 'AM'
  const mm2 = String(mm).padStart(2, '0')

  return `${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}-${y} ${hour12}:${mm2} ${ampm}`
}

const FieldRow = defineComponent({
  name: 'FieldRow',
  props: { label: { type: String, required: true }, nowrapLabel: { type: Boolean, default: false } },
  setup(props, { slots, attrs }) {
    return () =>
        h('div', { class: attrs.class || '' },
            h('div', { class: 'grid grid-cols-[160px_minmax(0,1fr)] items-center gap-2' }, [
              h('div', {
                class:
                    'text-sm text-gray-800 font-semibold text-right pr-1' +
                    (props.nowrapLabel ? ' whitespace-nowrap' : ''),
              }, props.label),
              h('div', { class: 'min-w-0' }, slots.default ? slots.default() : null),
            ])
        )
  },
})

const ErrText = defineComponent({
  name: 'ErrText',
  props: { msg: { type: String, default: '' } },
  setup(p) {
    return () => (p.msg ? h('div', { class: 'mt-1 text-xs font-medium text-red-600' }, p.msg) : null)
  },
})
</script>