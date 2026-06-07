<script setup>
import Card from 'primevue/card'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useHttp } from '../../plugins/http'

const http = useHttp()
const email = ref('')
const sending = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

async function submit() {
  successMessage.value = ''
  errorMessage.value = ''
  sending.value = true

  try {
    const { data } = await http.post('/api/auth/forgot-password', {
      email: email.value,
    })

    successMessage.value = data.message
  } catch (error) {
    errorMessage.value =
      error.response?.data?.errors?.email?.[0] ||
      error.response?.data?.message ||
      'Unable to process your request. Please try again.'
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="auth-backdrop relative min-h-screen overflow-hidden text-slate-950">
    <div class="auth-sheen absolute inset-0" />
    <auth-home-button />

    <div class="relative z-1 flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
      <div class="w-full max-w-117.5">
        <Card class="overflow-hidden border border-white/70 bg-white/92 shadow-[0_24px_80px_rgba(15,23,42,0.18)] backdrop-blur">
          <template #title>
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-brand-900">Account recovery</p>
            <h1 class="text-3xl font-semibold text-slate-950">Forgot your password?</h1>
            <p class="mt-3 text-sm font-normal leading-6 text-slate-500">
              Enter your email and we will send a secure reset link if an account exists.
            </p>
          </template>

          <template #content>
            <form class="space-y-5 pt-2" @submit.prevent="submit">
              <Message v-if="successMessage" severity="success" size="small">{{ successMessage }}</Message>
              <Message v-if="errorMessage" severity="error" size="small">{{ errorMessage }}</Message>

              <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-slate-700">Email address</label>
                <IconField>
                  <InputIcon class="pi pi-envelope" />
                  <InputText
                    id="email"
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    class="w-full"
                    placeholder="you@company.com"
                    required
                  />
                </IconField>
              </div>

              <form-button
                type="submit"
                label="Send reset link"
                icon="pi pi-send"
                class="w-full"
                :loading="sending"
              />

              <RouterLink :to="{ name: 'sign-in' }" class="block text-center text-sm font-medium text-brand-700">
                Back to sign in
              </RouterLink>
            </form>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-backdrop {
  background:
    linear-gradient(120deg, rgba(255, 255, 255, 0.78), rgba(247, 248, 252, 0.52)),
    linear-gradient(135deg, #edf7ff 0%, #f5f7fb 28%, #eef2ff 54%, #e9fbf4 78%, #f7f8fb 100%);
}

.auth-backdrop::before {
  position: absolute;
  inset: -35%;
  content: '';
  background:
    linear-gradient(115deg, transparent 0 22%, rgba(15, 17, 69, 0.1) 30%, transparent 42%),
    linear-gradient(70deg, transparent 0 35%, rgba(29, 4, 111, 0.2) 48%, transparent 60%),
    linear-gradient(150deg, transparent 0 40%, rgba(99, 111, 131, 0.16) 52%, transparent 64%);
  animation: gradient-drift 18s ease-in-out infinite alternate;
}

.auth-sheen {
  background: linear-gradient(100deg, transparent 20%, rgba(255, 255, 255, 0.38) 45%, transparent 70%);
  animation: sheen-slide 9s linear infinite;
}

@keyframes gradient-drift {
  from {
    transform: translate3d(-4%, -2%, 0) rotate(0deg);
  }

  to {
    transform: translate3d(4%, 3%, 0) rotate(8deg);
  }
}

@keyframes sheen-slide {
  from {
    transform: translateX(-45%);
  }

  to {
    transform: translateX(45%);
  }
}
</style>
