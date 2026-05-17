<script setup>
import { computed } from 'vue'
import { Checkbox } from 'primevue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  label: String,
  options: Array,
  error: String,
})

const emit = defineEmits(['update:modelValue'])

const localValue = computed({
  get() {
    return props.modelValue
  },
  set(val) {
    emit('update:modelValue', val)
  },
})
</script>

<template>
  <div>
    <div class="font-semibold text-[#334155] text-[13px] mb-1">{{ label }}</div>

    <label v-for="option in options" :key="option.value || option" class="flex items-center gap-2">
      <Checkbox
        v-model="localValue"
        :value="option.value || option"
        size="small"
      />

      <div class="text-[13px] text-gray-800">{{ option.label || option }}</div>
    </label>

    <app-form-error v-if="error" :error="error" />
  </div>
</template>
