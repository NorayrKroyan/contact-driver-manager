<template>
  <div class="p-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold">Contact & Driver Manager</h1>
      </div>

      <button
          class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
          @click="openCreate"
      >
        New Contact
      </button>
    </div>

    <!-- ONE FILTER -->
    <div class="mt-2 flex flex-col gap-2 md:flex-row md:items-center">
      <div class="w-full md:max-w-[520px]">
        <input
            type="text"
            v-model="filters.q"
            placeholder="Search all columns (name, phone, email, address, state, carrier...)"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
        >
      </div>

      <div class="flex items-center gap-2">
        <button
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50"
            @click="clearSearch"
        >
          Clear
        </button>
      </div>
    </div>

    <!-- Grid -->
    <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-900">
          <tr>
            <th class="px-4 py-2 cursor-pointer select-none" @click="toggleSortAndReset('name')">
              Name <span v-if="sort.key === 'name'">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
            </th>

            <th class="px-4 py-2 cursor-pointer select-none" @click="toggleSortAndReset('phone')">
              Phone <span v-if="sort.key === 'phone'">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
            </th>

            <th class="px-4 py-2 cursor-pointer select-none" @click="toggleSortAndReset('state')">
              State <span v-if="sort.key === 'state'">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
            </th>

            <th class="px-4 py-2 cursor-pointer select-none" @click="toggleSortAndReset('carrier')">
              Carrier/Client <span v-if="sort.key === 'carrier'">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
            </th>

            <th class="px-4 py-2 cursor-pointer select-none" @click="toggleSortAndReset('driver')">
              Driver? <span v-if="sort.key === 'driver'">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
            </th>
          </tr>
          </thead>

          <tbody class="text-xs">
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>

          <tr v-else-if="rows.length === 0">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No results</td>
          </tr>

          <!-- compact rows + alternating -->
          <tr
              v-for="(r, idx) in rows"
              :key="r.id_contact"
              class="border-t border-gray-100 hover:bg-gray-100"
              :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
          >
            <td class="px-4 py-1.5 align-middle">
              <button
                  type="button"
                  class="text-left font-medium text-blue-600 hover:text-blue-800 hover:underline"
                  @click="openEdit(r.id_contact)"
              >
                {{ (r.first_name || '') + ' ' + (r.last_name || '') }}
              </button>
            </td>

            <td class="px-4 py-1.5 align-middle font-mono text-[11px] text-gray-700">
              {{ r.phone_number || '' }}
            </td>

            <td class="px-4 py-1.5 align-middle text-gray-700">
              {{ r.state || '' }}
            </td>

            <td class="px-4 py-1.5 align-middle text-gray-700">
              {{ r.carrier_name || '' }}
            </td>

            <td class="px-4 py-1.5 align-middle">
                <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                    :class="Number(r.is_driver) === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'"
                >
                  {{ Number(r.is_driver) === 1 ? 'Yes' : 'No' }}
                </span>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination (server-driven) -->
    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div class="text-xs text-gray-600">
        Showing
        <span class="font-medium">{{ showingFrom }}</span>–
        <span class="font-medium">{{ showingTo }}</span>
        of <span class="font-medium">{{ totalRows }}</span>
      </div>

      <div class="flex items-center gap-2">
        <label class="text-xs text-gray-600">Rows:</label>
        <select
            v-model.number="pageSize"
            class="rounded-lg border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-gray-900"
            @change="goFirstPage"
        >
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>

        <button
            class="rounded-lg border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
            :disabled="page === 1 || loading"
            @click="setPage(1)"
        >
          First
        </button>

        <button
            class="rounded-lg border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
            :disabled="page === 1 || loading"
            @click="setPage(page - 1)"
        >
          Prev
        </button>

        <div class="min-w-[92px] text-center text-xs text-gray-700">
          Page <span class="font-medium">{{ page }}</span> / <span class="font-medium">{{ pageCount }}</span>
        </div>

        <button
            class="rounded-lg border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
            :disabled="page >= pageCount || loading"
            @click="setPage(page + 1)"
        >
          Next
        </button>

        <button
            class="rounded-lg border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 disabled:opacity-50"
            :disabled="page >= pageCount || loading"
            @click="setPage(pageCount)"
        >
          Last
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="err" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ err }}</div>

    <!-- Modal -->
    <ContactDriverModal
        ref="modalRef"
        :open="modal.open"
        :loading="modal.loading"
        :contact-id="modal.contactId"
        :saving="saving"
        :deleting="deleting"
        :lookups="lookups"
        :form="form"
        v-model:driverToggle="driverToggle"
        :formatDateTime="formatDateTime"
        @close="closeModalWrapped"
        @save="saveWrapped"
        @delete="removeContact"
    />
  </div>
</template>

<script setup>
import { onMounted, computed, ref, watch } from 'vue'
import { useContactDriverManager } from '../composables/useContactDriverManager'
import ContactDriverModal from '../components/ContactDriverModal.vue'

const {
  loading, rows, err,
  lookups, filters, sort,
  modal, saving, form, driverToggle,
  formatDateTime,
  loadLookups, load,
  toggleSort,
  openCreate, openEdit, closeModal, save,
  remove,
  pagination,
  setPagination,
} = useContactDriverManager()

// --- server pagination state ---
const page = ref(1)
const pageSize = ref(25) // default 25

const totalRows = computed(() => Number(pagination.total || 0))
const pageCount = computed(() => Math.max(1, Math.ceil(totalRows.value / pageSize.value)))

const showingFrom = computed(() => (totalRows.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1))
const showingTo = computed(() => Math.min(totalRows.value, page.value * pageSize.value))

watch([page, pageSize], () => {
  if (page.value > pageCount.value) page.value = pageCount.value
})

async function goFirstPage() {
  page.value = 1
  setPagination({ page: page.value, limit: pageSize.value })
  await load()
}

async function setPage(p) {
  const next = Math.max(1, Math.min(pageCount.value, Number(p || 1)))
  page.value = next
  setPagination({ page: page.value, limit: pageSize.value })
  await load()
}

async function toggleSortAndReset(key) {
  page.value = 1
  toggleSort(key)
  setPagination({ page: page.value, limit: pageSize.value })
  await load()
}

function clearSearch() {
  filters.q = ''
  goFirstPage()
}

const modalRef = ref(null)

function closeModalWrapped() {
  modalRef.value?.clearAllErrs()
  closeModal()
}
// Delete wiring
const deleting = ref(false)

async function removeContact() {
  if (!modal.contactId) return
  try {
    deleting.value = true
    await remove(modal.contactId)
    closeModal()
    await load()
  } finally {
    deleting.value = false
  }
}

async function saveWrapped() {
  try {
    await save()
    // on success: reload grid
    closeModal()
    await load()
  } catch (e) {
    // Laravel validation: push errors into modal under fields
    if (e?.response?.status === 422) {
      modalRef.value?.applyServerErrors(e)
      return
    }
    // anything else -> show global page error
    throw e
  }
}

onMounted(async () => {
  await loadLookups()
  setPagination({ page: page.value, limit: pageSize.value })
  await load()
})
</script>