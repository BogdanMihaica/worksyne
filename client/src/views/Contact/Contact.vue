<script setup>
import Message from 'primevue/message'
import { reactive, ref } from 'vue'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const sending = ref(false)
const sent = ref(false)
const errors = ref({})
const errorMessage = ref('')
const form = reactive({
  name: '',
  email: '',
  company: '',
  subject: '',
  message: '',
  website: '',
})

function fieldError(field) {
  return errors.value[field]?.[0]
}

async function submit() {
  errors.value = {}
  errorMessage.value = ''
  sent.value = false
  sending.value = true

  try {
    await http.post('/api/contact', form)

    sent.value = true
    Object.assign(form, {
      name: '',
      email: '',
      company: '',
      subject: '',
      message: '',
      website: '',
    })
  } catch (error) {
    errors.value = error.response?.data?.errors || {}
    errorMessage.value = error.response?.data?.message || 'Unable to send your message. Please try again.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f7f8fb] text-slate-950">
    <public-navigation />

    <main class="px-5 py-12 lg:px-8 lg:py-18">
      <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="pt-4">
          <p class="text-sm font-semibold uppercase text-brand-700">Contact</p>
          <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-950">
            Tell us what your team needs.
          </h1>
          <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
            Share your company structure, operational challenges, or rollout plans. We will respond to the email address you provide.
          </p>
        </div>

        <div class="rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <form class="grid gap-5" @submit.prevent="submit">
            <Message v-if="sent" severity="success">
              Your message was sent successfully. We will get back to you soon.
            </Message>
            <Message v-if="errorMessage" severity="error">
              {{ errorMessage }}
            </Message>

            <div class="grid gap-5 sm:grid-cols-2">
              <form-input
                v-model="form.name"
                label="Name"
                size="lg"
                required
                :error="fieldError('name')"
              />
              <form-input
                v-model="form.email"
                label="Email"
                type="email"
                size="lg"
                required
                description="We will send our response to this address."
                :error="fieldError('email')"
              />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <form-input
                v-model="form.company"
                label="Company"
                size="lg"
                :error="fieldError('company')"
              />
              <form-input
                v-model="form.subject"
                label="Subject"
                size="lg"
                required
                :error="fieldError('subject')"
              />
            </div>

            <form-textarea
              v-model="form.message"
              label="Message"
              placeholder="How can we help?"
              size="lg"
              :rows="8"
              required
              :error="errors.message"
            />

            <input
              v-model="form.website"
              type="text"
              name="website"
              tabindex="-1"
              autocomplete="off"
              class="hidden"
              aria-hidden="true"
            />

            <div class="flex justify-end">
              <form-button
                type="submit"
                label="Send message"
                icon="paper-plane"
                :loading="sending"
              />
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>
