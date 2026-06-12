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
  expandable: {
    type: Boolean,
    default: false,
  },
  dataKey: {
    type: String,
    default: 'id',
  },
})

const http = useHttp()
const totalRecords = ref(0)
const records = ref([])
const expandedRows = ref({})
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
      expandedRows.value = {}
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

function formatValue(column, data) {
  const value = getNestedValue(data, column.field)

  return column.format ? column.format(value, data) : value
}
</script>

<template>
  <DataTable
    v-model:expandedRows="expandedRows"
    :value="records"
    :data-key="dataKey"
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
    <Column v-if="expandable" expander class="fit-column" />

    <Column
      v-for="col in columns.filter(column => !column.disabled)"
      :key="col.field"
      :header="col.header"
      :field="col.field"
      :sortable="col.sortable"
      :class="col.widthFit ? 'fit-column' : ''"
      :bodyStyle="col.numeric ? 'text-align: right;' : 'text-align: left;'"
    >
      <template #body="slotProps">
        <template v-if="col.boolean">
          {{ getNestedValue(slotProps.data, col.field) ? 'Yes' : 'No' }}
        </template>

        <div v-else-if="col.type === 'actions'" class="flex flex-wrap gap-2">
          <app-action-button
            v-for="item in col.items(slotProps)"
            :key="item.label"
            v-bind="item"
          />
        </div>

        <div v-else-if="col.type === 'image'" class="h-30">
          <img
            class="h-30"
            :src="col.path(slotProps)"
            :alt="col.alt"
          />
        </div>

        <div v-else-if="col.rows">
          <div v-for="row in col.rows" :key="row.field">
            <span class="font-bold">{{ row.header }}: </span>
            <span v-if="col.field">{{ slotProps.data[col.field][row.field] }}</span>
            <span v-else>{{ slotProps.data[row.field] }}</span>
          </div>
        </div>

        <div v-else-if="col.date">
          {{ formatTimestamp(getNestedValue(slotProps.data, col.field)) }}
        </div>

        <Tag
          v-else-if="col.severity"
          :severity="col.severity(slotProps)"
          :value="getNestedValue(slotProps.data, col.field)"
        />

        <template v-else-if="col.percentage">
          <span>{{ getNestedValue(slotProps.data, col.field) }}</span>
          <span> ({{ getNestedValue(slotProps.data, col.percentage) }}%)</span>
        </template>

        <template v-else-if="col.suffix">
          <span>{{ getNestedValue(slotProps.data, col.field) }}</span>
          <span>{{ col.suffix }}</span>
        </template>

        <template v-else-if="col.format">
          {{ formatValue(col, slotProps.data) }}
        </template>

        <template v-else>
          {{ getNestedValue(slotProps.data, col.field) }}
        </template>
      </template>
    </Column>

    <template v-if="expandable" #expansion="slotProps">
      <slot name="expansion" :data="slotProps.data" />
    </template>
  </DataTable>
</template>
