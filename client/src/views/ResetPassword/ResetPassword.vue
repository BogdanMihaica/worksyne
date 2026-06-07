<script setup>
import Card from 'primevue/card'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useHttp } from '../../plugins/http'

const route = useRoute()
const router = useRouter()
const http = useHttp()
const password = ref('')
const passwordConfirmation = ref('')
const resetting = ref(false)
const errorMessage = ref('')
const hasValidLink = computed(() => (
  typeof route.query.token === 'string' &&
  typeof route.query.email === 'string'
))

async function submit() {
  errorMessage.value = ''

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'The password confirmation does not match.'
    return
  }

  resetting.value = true

  try {
    await http.post('/api/auth/reset-password', {
      token: route.query.token,
      email: route.query.email,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    router.push({ name: 'sign-in', query: { password_reset: 'success' } })
  } catch (error) {
    const errors = error.response?.data?.errors || {}
    errorMessage.value =
      errors.password?.[0] ||
      errors.token?.[0] ||
      error.response?.data?.message ||
      'Unable to reset your password.'
  } finally {
    resetting.value = false
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
            <h1 class="text-3xl font-semibold text-slate-950">Choose a new password</h1>
            <p class="mt-3 text-sm font-normal leading-6 text-slate-500">
              Use at least 12 characters with uppercase, lowercase, numbers, and symbols.
            </p>
          </template>

          <template #content>
            <Message v-if="!hasValidLink" severity="error" size="small">
              This password reset link is incomplete or invalid.
            </Message>

            <form v-else class="space-y-5 pt-2" @submit.prevent="submit">
              <Message v-if="errorMessage" severity="error" size="small">{{ errorMessage }}</Message>

              <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-slate-700">New password</label>
                <Password
                  id="password"
                  v-model="password"
                  autocomplete="new-password"
                  class="w-full"
                  input-class="w-full"
                  placeholder="Enter a strong password"
                  toggle-mask
                  :feedback="false"
                  required
                />
              </div>

              <div class="space-y-2">
                <label for="password-confirmation" class="text-sm font-medium text-slate-700">Confirm new password</label>
                <Password
                  id="password-confirmation"
                  v-model="passwordConfirmation"
                  autocomplete="new-password"
                  class="w-full"
                  input-class="w-full"
                  placeholder="Repeat your new password"
                  toggle-mask
                  :feedback="false"
                  required
                />
              </div>

              <form-button
                type="submit"
                label="Reset password"
                icon="pi pi-lock"
                class="w-full"
                :loading="resetting"
              />
            </form>

            <RouterLink :to="{ name: 'sign-in' }" class="mt-5 block text-center text-sm font-medium text-brand-700">
              Back to sign in
            </RouterLink>
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
