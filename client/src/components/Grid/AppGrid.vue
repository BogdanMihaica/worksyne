<script setup>
import { onMounted, ref, watch } from 'vue'
import { Column, DataTable, Tag } from 'primevue'
import { useHttp } from '../../plugins/http'

const props = defineProps({
  columns: Array,
  url: String,
  defaultSortField: String,
  defaultSortOrder: String,
  filters: Object,
})

const http = useHttp()
const totalRecords = ref(0)
const records = ref([])
const loading = ref(false)
const loadParams = ref([])
const limit = ref(0)
const first = ref(0)

onMounted(() => {
  loadData()
})

watch(
  () => props.filters,
  () => {
    loadParams.value = []
    first.value = 0
    loadData()
  },
)

/**
 * Fetches the necessary data.
 */
function loadData() {
  loading.value = true

  http
    .get(props.url, {
      params: {
        page: (loadParams.value?.page || 0) + 1,
        sort: loadParams.value?.sortField
          ? (loadParams.value.sortOrder < 0 ? '-' : '') + loadParams.value.sortField
          : props.defaultSortOrder == 'desc'
            ? '-' + props.defaultSortField
            : props.defaultSortField,
        ...props.filters,
      },
    })
    .then(({ data }) => {
      records.value = data?.data
      totalRecords.value = data?.total ?? 0
      limit.value = data?.per_page || 15
    })
    .finally(() => {
      loading.value = false
    })
}

/**
 * Reloads params for the url and loads records based on them.
 *
 * @param event
 */
function reloadParams(event) {
  loadParams.value = event
  first.value = event.first
  loadData()
}

/**
 * Returns the nested property from an object.
 *
 * @param obj
 * @param path
 */
function getNestedValue(obj, path) {
  return path.split('.').reduce((acc, part) => acc?.[part], obj)
}

/**
 * Formats a timestamp into human readable data.
 *
 * @param timestamp
 *
 * @returns string | null
 */
function formatTimestamp(timestamp) {
  return timestamp?.split('.')[0].replace('T', ' ')
}
</script>

<template>
  <DataTable
    :value="records"
    :total-records="totalRecords"
    :loading="loading"
    :lazy="true"
    :paginator="true"
    :first="first"
    :rows="limit"
    paginator-template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
    currentPageReportTemplate="{first} to {last} of {totalRecords}"
    scrollable
    :sortField="defaultSortField"
    :sortOrder="defaultSortOrder == 'desc' ? -1 : 1"
    tableStyle="border-radius: 5px;"
    @page="reloadParams($event)"
    @sort="reloadParams($event)"
  >
    <Column
      v-for="col in columns.filter(column => !column.disabled)"
      :key="col.field"
      :header="col.header"
      :field="col.field"
      :sortable="col.sortable"
      :class="col.widthFit ? 'fit-column' : ''"
      :bodyStyle="col.numeric ? 'text-align: right;' : 'text-align: left;'"
    >
      <template v-if="col.boolean" #body="slotProps">
        {{ getNestedValue(slotProps.data, col.field) ? 'Yes' : 'No' }}
      </template>

      <template v-if="col.type === 'actions'" #body="slotProps">
        <div v-for="item in col.items(slotProps)" class="block">
          <app-action-button v-bind="item" />
        </div>
      </template>

      <template v-if="col.type === 'image'" #body="slotProps">
        <div class="h-30">
          <img 
            class="h-30"
            :src="col.path(slotProps)" 
            :alt="col.alt" 
          />
        </div>
      </template>

      <template v-if="col.rows" #body="slotProps">
        <div>
          <div v-for="row in col.rows">
            <span class="font-bold">{{ row.header }}: </span>
            <span v-if="col.field">{{ slotProps.data[col.field][row.field] }}</span>
            <span v-else>{{ slotProps.data[row.field] }}</span>
          </div>
        </div>
      </template>

      <template v-if="col.date" #body="slotProps">
        <div>
          {{ formatTimestamp(slotProps.data[col.field]) }}
        </div>
      </template>

      <template v-if="col.severity" #body="slotProps">
        <Tag :severity="col.severity(slotProps)" :value="getNestedValue(slotProps.data, col.field)"></Tag>
      </template>

      <template v-if="col.percentage" #body="slotProps">
        <span>{{ getNestedValue(slotProps.data, col.field) }}</span>
        <span> ({{ getNestedValue(slotProps.data, col.percentage) }}%)</span>
      </template>

      <template v-if="col.suffix" #body="slotProps">
        <span>{{ getNestedValue(slotProps.data, col.field) }}</span>
        <span>{{ col.suffix }}</span>
      </template>
    </Column>
  </DataTable>
</template>
