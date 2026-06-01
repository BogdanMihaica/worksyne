<script setup>
import Dialog from 'primevue/dialog'

defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm action',
  },
  message: {
    type: String,
    default: 'This action cannot be undone',
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  confirmSeverity: {
    type: String,
    default: 'primary',
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <Dialog
    :visible="modelValue"
    modal
    :header="title"
    :style="{ width: 'min(420px, calc(100vw - 32px))' }"
    @update:visible="$emit('update:modelValue', $event)"
  >
    <div class="text-sm leading-6 text-slate-700">
      {{ message }}
    </div>

    <div class="mt-5 flex justify-end gap-2">
      <form-button
        severity="ternary"
        label="Cancel"
        @click.prevent="close"
      />
      <form-button
        :severity="confirmSeverity"
        :label="confirmLabel"
        :loading="loading"
        @click.prevent="$emit('confirm')"
      />
    </div>
  </Dialog>
</template>
