<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHttp } from '../../plugins/http'

const route = useRoute()
const router = useRouter()
const http = useHttp()

const name = ref('')
const price = ref('')
const errors = ref({})
const loading = ref(false)
const saving = ref(false)

const isEditMode = computed(() => Boolean(route.params.id))
const title = computed(() => isEditMode.value ? 'Edit Subscription Plan' : 'Create Subscription Plan')

onMounted(async () => {
  loading.value = true

  await loadSubscriptionPlan()

  loading.value = false
})

async function loadSubscriptionPlan() {
  if (!isEditMode.value) {
    return
  }

  const { data } = await http.get(`/api/subscription-plans/${route.params.id}`)

  name.value = data.name
  price.value = data.price
}

async function submit() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: name.value,
    price: price.value,
  }

  try {
    if (isEditMode.value) {
      await http.put(`/api/subscription-plans/${route.params.id}`, payload)
    } else {
      await http.post('/api/subscription-plans', payload)
    }

    router.push({ name: 'subscription-plans' })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    saving.value = false
  }
}

function cancel() {
  router.push({ name: 'subscription-plans' })
}
</script>

<template>
  <app-card>
    <template #title>
      {{ title }}
    </template>

    <template #content>
      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <form-input
          v-model="name"
          label="Name"
          required
          size="lg"
          :error="errors.name"
        />

        <form-input
          v-model="price"
          label="Price"
          type="number"
          required
          size="lg"
          :error="errors.price"
        />

        <div class="flex gap-2">
          <form-button
            type="submit"
            icon="save"
            label="Save"
            :loading="saving || loading"
          />
          <form-button
            severity="ternary"
            label="Cancel"
            @click.prevent="cancel"
          />
        </div>
      </form>
    </template>
  </app-card>
</template>
