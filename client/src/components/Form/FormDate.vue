<script setup>
import { computed } from 'vue'
import { DatePicker } from 'primevue'

const props = defineProps({
  type: {
    type: String,
    default: 'date',
  },
  label: {
    type: String,
    required: true,
  },
  error: {
    type: [String],
  },
  modelValue: {
    type: [String, Date],
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
  },
  description: {
    type: String,
    default: '',
  },
})

defineEmits(['update:modelValue'])

const dateFormatMap = computed(() => ({
  date: 'mm-dd-yy',
  month: 'mm-yy',
  year: 'yy',
}))

const sizeClass = computed(() => {
  const sizeMap = {
    xs: 'w-30',
    sm: 'w-45',
    md: 'w-64',
    lg: 'w-full',
  }

  return sizeMap[props.size] || sizeMap.lg
})
</script>

<template>
  <label class="flex flex-col justify-end">
    <div class="font-semibold text-[#334155] text-[13px] mb-1">
      <span>{{ label }}</span>
      <span v-if="required" class="text-red-500">*</span>
    </div>

    <DatePicker
      :model-value="modelValue"
      :date-format="dateFormatMap[type] || 'mm-dd-yy'"
      :view="type"
      :manual-input="false"
      :class="sizeClass"
      placeholder="Select a date"
      :input-style="{ 'border-radius': '0', 'box-shadow': 'none', border: '1px solid #E5E7EB', padding: '7px', 'font-size': '14px' }"
      @update:model-value="$emit('update:modelValue', $event.toLocaleDateString('en-CA'))"
    />
    <div v-if="description" class="text-xs italic text-neutral-400 mt-1">{{ description }}</div>
    <app-form-error v-if="error" :error="error" />
  </label>
</template>
