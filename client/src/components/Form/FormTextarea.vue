<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: Array,
  },
  modelValue: {
    type: String,
    default: '',
  },
  rows: {
    type: [Number, String],
    default: 4,
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
  <label>
    <div class="font-semibold text-[#334155] text-[13px] mb-1">
      <span>{{ label }}</span>
      <span v-if="required" class="text-red-500">*</span>
    </div>
    <div>
      <textarea
        :value="modelValue"
        class="w-full p-[7px] border text-[13px] border-gray-200 focus:outline-none h-auto"
        :placeholder="placeholder || `Enter agent ${label.toLowerCase()}`"
        :rows="rows"
        @input="$emit('update:modelValue', $event.target.value)"
      >
      </textarea>
    </div>
    <div v-if="description" class="text-xs italic text-neutral-400 mt-1">{{ description }}</div>
    <form-error v-if="error" :error="error" />
  </label>
</template>
