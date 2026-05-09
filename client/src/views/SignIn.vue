<script setup>
import { ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'

const email = ref('')
const password = ref('')
const remember = ref(false)

function onSignIn() {
  alert('Test');
}
</script>

<template>
  <main class="min-h-screen bg-[#f4f7fb] text-slate-950">
      <div class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
        <div class="w-full max-w-117.5">
          <Card class="overflow-hidden border border-slate-200 shadow-[0_24px_80px_rgba(15,23,42,0.12)]">
            <template #title>
              <div class="flex items-start justify-between gap-6">
                <div>
                  <p class="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-teal-600">
                    Welcome back
                  </p>
                  <h2 class="text-3xl font-semibold text-slate-950">Sign in</h2>
                </div>
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-teal-50 text-teal-700">
                  <i class="pi pi-lock text-lg" />
                </span>
              </div>
            </template>

            <template #content>
              <form class="space-y-5 pt-2" @submit.prevent>
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
                  />
                </div>

                <div class="flex items-center justify-between gap-4">
                  <label for="remember" class="flex items-center gap-3 text-sm text-slate-600">
                    <Checkbox id="remember" v-model="remember" binary />
                    <span>Remember me</span>
                  </label>
                  <Button label="Forgot password?" link type="button" class="px-0 text-sm" />
                </div>

                <Button
                  label="Sign in"
                  icon="pi pi-arrow-right"
                  icon-pos="right"
                  type="button"
                  class="w-full justify-center"
                  size="large"
                  @click="onSignIn"
                />
              </form>
            </template>
          </Card>
        </div>
      </div>
  </main>
</template>
