<script setup>
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  userId: {
    type: [Number, String],
    default: null,
  },
  userOptions: {
    type: Array,
    default: () => [],
  },
  status: {
    type: String,
    default: 'pending',
  },
  title: {
    type: String,
    default: 'Request Timeoff',
  },
  submitLabel: {
    type: String,
    default: 'Request',
  },
  timeoffRequest: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'created', 'updated'])

const http = useHttp()
const toast = useAppToast()

const startDate = ref('')
const endDate = ref('')
const reason = ref('')
const selectedUserId = ref('')
const errors = ref({})
const saving = ref(false)

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      reset()
      fillFromTimeoffRequest()
    }
  },
)

async function submit() {
  saving.value = true
  errors.value = {}

  try {
    const payload = {
      user_id: props.userId || selectedUserId.value,
      start_date: startDate.value,
      end_date: endDate.value,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      reason: reason.value,
      status: props.status,
    }

    if (props.timeoffRequest?.id) {
      const { data } = await http.put(`/api/timeoff-requests/${props.timeoffRequest.id}`, payload)

      toast({ type: 'success', message: 'Timeoff request updated successfully.' })
      emit('updated', data)
      close()
      return
    }

    const { data } = await http.post('/api/timeoff-requests', payload)

    toast({ type: 'success', message: 'Timeoff request created successfully.' })
    emit('created', data)
    close()
  } catch (error) {
    toast({ type: 'error', message: 'Some errors occured' })

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    saving.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}

function reset() {
  startDate.value = ''
  endDate.value = ''
  reason.value = ''
  selectedUserId.value = ''
  errors.value = {}
}

function fillFromTimeoffRequest() {
  if (!props.timeoffRequest) {
    return
  }

  startDate.value = toDateOnly(props.timeoffRequest.start_date)
  endDate.value = toDateOnly(props.timeoffRequest.end_date)
  reason.value = props.timeoffRequest.reason || ''
  selectedUserId.value = props.timeoffRequest.user_id || ''
}

function toDateOnly(value) {
  return String(value || '').split('T')[0]
}
</script>

<template>
  <Dialog
    :visible="modelValue"
    modal
    :header="title"
    :style="{ width: 'min(520px, calc(100vw - 32px))' }"
    @update:visible="$emit('update:modelValue', $event)"
  >
    <form class="flex flex-col gap-4" @submit.prevent="submit">
      <form-select
        v-if="!userId"
        v-model="selectedUserId"
        label="User"
        required
        size="lg"
        :options="userOptions"
        :default-option="false"
        :error="errors.user_id"
      />
      <form-date
        v-model="startDate"
        label="Start date"
        required
        size="lg"
        :error="errors.start_date"
      />
      <form-date
        v-model="endDate"
        label="End date"
        required
        size="lg"
        :error="errors.end_date"
      />
      <form-textarea
        v-model="reason"
        label="Reason"
        placeholder="Enter the reason for this request"
        required
        size="lg"
        :rows="5"
        :error="errors.reason"
      />

      <div class="flex justify-end gap-2">
        <form-button
          severity="ternary"
          label="Cancel"
          @click.prevent="close"
        />
        <form-button
          type="submit"
          icon="save"
          :label="submitLabel"
          :loading="saving"
        />
      </div>
    </form>
  </Dialog>
</template>
