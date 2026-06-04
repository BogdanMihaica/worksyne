<script setup>
import AutoComplete from 'primevue/autocomplete'
import { ref } from 'vue'
import { useHttp } from '../../plugins/http'
import { useAppToast } from '../../plugins/toast'

const http = useHttp()
const toast = useAppToast()

const recipients = ref([])
const recipientSuggestions = ref([])
const message = ref('')
const loadingRecipients = ref(false)
const sending = ref(false)
const errors = ref({})

async function searchRecipients(event) {
  loadingRecipients.value = true

  try {
    const { data } = await http.get('/api/company-notifications/recipients', {
      params: {
        q: event.query,
      },
    })

    const selectedIds = new Set(recipients.value.map((recipient) => recipient.id))
    recipientSuggestions.value = data.filter((recipient) => !selectedIds.has(recipient.id))
  } catch {
    toast({ type: 'error', message: 'Unable to load recipients.' })
  } finally {
    loadingRecipients.value = false
  }
}

async function sendNotification() {
  errors.value = {}
  sending.value = true

  try {
    const { data } = await http.post('/api/company-notifications', {
      user_ids: recipients.value.map((recipient) => recipient.id),
      message: message.value,
    })

    recipients.value = []
    message.value = ''
    toast({
      type: 'success',
      message: `Notification sent to ${data.sent_count} user${data.sent_count === 1 ? '' : 's'}.`,
    })
  } catch (error) {
    errors.value = error.response?.data?.errors || {}
    toast({ type: 'error', message: error.response?.data?.message || 'Unable to send notification.' })
  } finally {
    sending.value = false
  }
}

function isLastRecipient(recipient) {
  return recipients.value.findIndex((selected) => selected.id === recipient.id) === recipients.value.length - 1
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-950">Notifications</h1>
      <p class="text-sm text-slate-500">Send a message to approved users in your company.</p>
    </div>

    <app-card size="large">
      <template #title>
        New notification
      </template>

      <template #content>
        <form class="grid gap-5" @submit.prevent="sendNotification">
          <label>
            <div class="mb-1 text-[13px] font-semibold text-[#334155]">
              <span>Recipients</span>
              <span class="text-red-500">*</span>
            </div>

            <AutoComplete
              v-model="recipients"
              multiple
              option-label="label"
              :suggestions="recipientSuggestions"
              :loading="loadingRecipients"
              placeholder="Search users by name or email"
              input-class="w-full"
              class="w-full"
              @complete="searchRecipients"
            >
              <template #option="{ option }">
                <div>
                  <div class="text-sm font-medium text-slate-950">{{ option.name }}</div>
                  <div class="text-xs text-slate-500">{{ option.email }}</div>
                </div>
              </template>
              <template #chip="{ value }">
                <span class="text-xs font-medium">
                  {{ value.name }}<span v-if="!isLastRecipient(value)">,</span>
                </span>
              </template>
            </AutoComplete>

            <form-error v-if="errors.user_ids" :error="errors.user_ids" />
          </label>

          <form-textarea
            v-model="message"
            label="Message"
            placeholder="Write the notification message"
            size="lg"
            :rows="6"
            required
            :error="errors.message"
          />

          <div class="flex justify-end gap-3">
            <form-button
              type="submit"
              label="Send notification"
              icon="paper-plane"
              :loading="sending"
            />
          </div>
        </form>
      </template>
    </app-card>
  </div>
</template>
