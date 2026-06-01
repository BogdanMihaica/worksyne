<script setup>
import { computed, onMounted, ref } from 'vue'
import { DayPilotScheduler } from '@daypilot/daypilot-lite-vue'

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  events: {
    type: Array,
    default: () => [],
  },
  title: {
    type: String,
    default: 'Schedule',
  },
  startDate: {
    type: String,
    default: '',
  },
  days: {
    type: Number,
    default: 7,
  },
})

const emit = defineEmits(['range-change', 'event-click'])
const currentStart = ref(props.startDate || formatDate(startOfWeek(new Date())))

const weekLabel = computed(() => {
  const start = new Date(`${currentStart.value}T00:00:00`)
  const end = addDays(start, props.days - 1)

  return `${formatDisplayDate(start)} - ${formatDisplayDate(end)}`
})

const schedulerRows = computed(() => {
  return props.rows
    .filter(Boolean)
    .map((row) => ({ ...row }))
})

const schedulerEvents = computed(() => {
  return props.events
    .filter(Boolean)
    .map((event) => ({ ...event }))
})

const schedulerConfig = computed(() => ({
  theme: 'worksyne_scheduler',
  width: '100%',
  startDate: currentStart.value,
  days: props.days,
  scale: 'Day',
  cellWidth: 220,
  cellGroupBy: 'Day',
  timeHeaders: [
    { groupBy: 'Month', format: 'MMMM yyyy' },
    { groupBy: 'Day', format: 'ddd d' },
  ],
  resources: schedulerRows.value,
  rowHeaderWidth: 220,
  rowMarginTop: 8,
  rowMarginBottom: 8,
  eventHeight: 34,
  eventBorderRadius: 8,
  eventPadding: 8,
  durationBarVisible: false,
  heightSpec: 'Max',
  height: 420,
  businessBeginsHour: 8,
  businessEndsHour: 18,
  businessWeekends: false,
  cellsMarkBusiness: true,
  floatingEvents: false,
  floatingTimeHeaders: true,
  eventClickHandling: 'Enabled',
  eventMoveHandling: 'Disabled',
  eventResizeHandling: 'Disabled',
  timeRangeSelectedHandling: 'Disabled',
  onEventClick: (args) => {
    emit('event-click', {
      event: args.e.data,
      x: args.originalEvent.clientX,
      y: args.originalEvent.clientY,
    })
  },
  onBeforeRowHeaderRender: (args) => {
    args.row.html = rowHeaderHtml(args.row.data)
  },
  onBeforeCellRender: (args) => {
    if (!args.cell.properties.business) {
      args.cell.properties.backColor = '#f8fafc'
    }
  },
}))

onMounted(() => {
  emitRangeChange()
})

function previous() {
  currentStart.value = formatDate(addDays(new Date(`${currentStart.value}T00:00:00`), -props.days))
  emitRangeChange()
}

function next() {
  currentStart.value = formatDate(addDays(new Date(`${currentStart.value}T00:00:00`), props.days))
  emitRangeChange()
}

function today() {
  currentStart.value = formatDate(startOfWeek(new Date()))
  emitRangeChange()
}

function emitRangeChange() {
  const start = new Date(`${currentStart.value}T00:00:00`)
  const end = addDays(start, props.days - 1)

  emit('range-change', {
    start: currentStart.value,
    end: formatDate(end),
  })
}

function startOfWeek(date) {
  const nextDate = new Date(date)
  const day = nextDate.getDay()
  const distance = day === 0 ? -6 : 1 - day

  nextDate.setDate(nextDate.getDate() + distance)

  return nextDate
}

function addDays(date, days) {
  const nextDate = new Date(date)

  nextDate.setDate(nextDate.getDate() + days)

  return nextDate
}

function formatDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDisplayDate(date) {
  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
  }).format(date)
}

function rowHeaderHtml(row) {
  if (!row) {
    return ''
  }

  const subtitle = row.subtitle ? `<div class="worksyne-scheduler-row-subtitle">${escapeHtml(row.subtitle)}</div>` : ''

  return `
    <div class="worksyne-scheduler-row">
      <div class="worksyne-scheduler-row-avatar">${escapeHtml(row.initials || initials(row.name))}</div>
      <div class="worksyne-scheduler-row-copy">
        <div class="worksyne-scheduler-row-name">${escapeHtml(row.name)}</div>
        ${subtitle}
      </div>
    </div>
  `
}

function initials(name) {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function escapeHtml(value) {
  return String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}
</script>

<template>
  <div class="w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
      <div class="min-w-0">
        <h2 class="text-lg font-semibold text-slate-950">{{ title }}</h2>
        <p class="text-sm text-slate-500">{{ weekLabel }}</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-900"
          aria-label="Previous"
          @click="previous"
        >
          <i class="pi pi-angle-left" />
        </button>
        <button
          type="button"
          class="h-9 rounded-lg border border-slate-200 px-4 text-sm font-medium text-slate-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-900"
          @click="today"
        >
          Today
        </button>
        <button
          type="button"
          class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-900"
          aria-label="Next"
          @click="next"
        >
          <i class="pi pi-angle-right" />
        </button>
      </div>
    </div>

    <div v-if="$slots.actions" class="flex justify-end border-b border-slate-200 bg-white px-5 py-3">
      <slot name="actions" />
    </div>

    <div class="min-h-105 w-full overflow-hidden bg-slate-50/70 p-3">
      <DayPilotScheduler :config="schedulerConfig" :events="schedulerEvents" />
    </div>
  </div>
</template>

<style scoped>
:deep(.worksyne_scheduler_main) {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  background: #ffffff;
  font-family: inherit;
}

:deep(.worksyne_scheduler_timeheadergroup),
:deep(.worksyne_scheduler_timeheadercol) {
  border-color: #e2e8f0;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

:deep(.worksyne_scheduler_corner) {
  border-color: #e2e8f0;
  background: #ffffff;
}

:deep(.worksyne_scheduler_rowheader) {
  border-color: #e2e8f0;
  background: #ffffff;
}

:deep(.worksyne_scheduler_cell) {
  border-color: #edf2f7;
  background: #ffffff;
}

:deep(.worksyne_scheduler_cell_business) {
  background: #fbfdff;
}

:deep(.worksyne_scheduler_matrix_vertical_line),
:deep(.worksyne_scheduler_matrix_horizontal_line) {
  background: #e2e8f0;
}

:deep(.worksyne_scheduler_event) {
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #1e3a8a;
  box-shadow: 0 8px 20px rgb(15 23 42 / 10%);
  font-size: 12px;
  font-weight: 700;
}

:deep(.worksyne_scheduler_event.worksyne-scheduler-event-approved) {
  border-color: #22c55e !important;
  background: #dcfce7 !important;
  color: #166534 !important;
}

:deep(.worksyne_scheduler_event.worksyne-scheduler-event-pending) {
  border-color: #f59e0b !important;
  background: #fef3c7 !important;
  color: #92400e !important;
}

:deep(.worksyne-scheduler-row) {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
}

:deep(.worksyne-scheduler-row-avatar) {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 8px;
  background: #0f172a;
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
}

:deep(.worksyne-scheduler-row-copy) {
  min-width: 0;
}

:deep(.worksyne-scheduler-row-name) {
  overflow: hidden;
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:deep(.worksyne-scheduler-row-subtitle) {
  overflow: hidden;
  color: #64748b;
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
