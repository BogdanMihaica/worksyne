<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { RouterLink } from 'vue-router'
import { authStore } from '../../stores/auth'

const email = ref('')
const password = ref('')
const remember = ref(false)
const route = useRoute()
const router = useRouter()
const errorMessage = ref('')
const passwordResetMessage = computed(() => (
  route.query.password_reset === 'success'
    ? 'Your password has been reset. Sign in with your new password.'
    : ''
))
const isLoading = computed(() => authStore.state.isLoading)

async function onSignIn() {
  errorMessage.value = ''

  try {
    await authStore.signIn({
      email: email.value,
      password: password.value,
    })
  } catch (error) {
    errorMessage.value =
      error.response?.data?.errors?.email?.[0] ||
      error.response?.data?.message ||
      'Unable to sign in.'
    return
  }

  if (typeof route.query.redirect === 'string') {
    router.push(route.query.redirect)
    return
  }

  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div class="signin-backdrop relative min-h-screen overflow-hidden text-slate-950">
      <div class="signin-sheen absolute inset-0" />

      <div class="relative z-1 flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
        <div class="w-full max-w-117.5">
          <Card class="overflow-hidden border border-white/70 bg-white/92 shadow-[0_24px_80px_rgba(15,23,42,0.18)] backdrop-blur">
            <template #title>
              <div class="flex items-start justify-between gap-6">
                <div>
                  <p class="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-brand-900">
                    Welcome back
                  </p>
                  <h2 class="text-3xl font-semibold text-slate-950">Sign in</h2>
                </div>
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-brand-50 text-brand-900">
                  <i class="pi pi-lock text-lg" />
                </span>
              </div>
            </template>

            <template #content>
              <form class="space-y-5 pt-2" @submit.prevent="onSignIn">
                <Message v-if="errorMessage" severity="error" size="small">
                  {{ errorMessage }}
                </Message>
                <Message v-if="passwordResetMessage" severity="success" size="small">
                  {{ passwordResetMessage }}
                </Message>

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

                <div class="space-y-2">
                  <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                  <Password
                    id="password"
                    v-model="password"
                    autocomplete="current-password"
                    class="w-full"
                    input-class="w-full"
                    placeholder="Enter your password"
                    toggle-mask
                    :feedback="false"
                    required
                  />
                </div>

                <div class="flex items-center justify-between gap-4">
                  <label for="remember" class="flex items-center gap-2 text-sm text-slate-600">
                    <Checkbox inputId="remember" v-model="remember" binary />
                    <span>Remember me</span>
                  </label>

                  <RouterLink :to="{ name: 'forgot-password' }" class="text-sm font-medium text-brand-700">
                    Forgot password?
                  </RouterLink>
                </div>

                <Button
                  label="Sign in"
                  icon="pi pi-arrow-right"
                  icon-pos="right"
                  type="submit"
                  class="w-full justify-center border-brand-900! bg-brand-700! text-white! hover:bg-brand-950!"
                  size="large"
                  :loading="isLoading"
                />
              </form>
            </template>
          </Card>
        </div>
      </div>
  </div>
</template>

<style scoped>
.signin-backdrop {
  background:
    linear-gradient(120deg, rgba(255, 255, 255, 0.78), rgba(247, 248, 252, 0.52)),
    linear-gradient(135deg, #edf7ff 0%, #f5f7fb 28%, #eef2ff 54%, #e9fbf4 78%, #f7f8fb 100%);
}

.signin-backdrop::before {
  position: absolute;
  inset: -35%;
  content: '';
  background:
    linear-gradient(115deg, transparent 0 22%, rgba(15, 17, 69, 0.1) 30%, transparent 42%),
    linear-gradient(70deg, transparent 0 35%, rgba(29, 4, 111, 0.2) 48%, transparent 60%),
    linear-gradient(150deg, transparent 0 40%, rgba(99, 111, 131, 0.16) 52%, transparent 64%);
  animation: gradient-drift 18s ease-in-out infinite alternate;
}

.signin-sheen {
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
