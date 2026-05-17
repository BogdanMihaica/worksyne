<script setup>
import { computed } from 'vue'
import { Select } from 'primevue'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: [String, Number, Boolean],
    default: '',
  },
  error: {
    type: Array,
  },
  options: {
    type: Array,
    default: [],
  },
  boolean: {
    type: Boolean,
    default: false,
  },
  defaultOption: {
    type: Boolean,
    default: true,
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

const computedOptions = computed(() => {
  if (props.boolean) {
    return [
      { value: 1, label: 'Yes' },
      { value: 0, label: 'No' },
    ]
  }

  if (props.defaultOption) {
    props.options.push({ value: '', label: '-' })
  }

  return props.options
})

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
  <div>
    <label class="flex flex-col justify-end">
      <div class="font-semibold text-[#334155] text-[13px] mb-1">
        <span>{{ label }}</span>
        <span v-if="required" class="text-red-500">*</span>
      </div>

      <Select
        :model-value="modelValue"
        :options="computedOptions"
        option-label="label"
        option-value="value"
        placeholder="--Select--"
        size="small"
        :class="sizeClass"
        @update:model-value="val => $emit('update:modelValue', val)"
      >
        <template #dropdownicon>
          <div class="text-[9px] w-fit ml-2">
            <i class="pi pi-sort-down-fill" />
          </div>
        </template>
      </Select>
      <div v-if="description" class="text-xs italic text-neutral-400 mt-1">{{ description }}</div>
      <app-form-error v-if="error" :error="error" />
    </label>
  </div>
</template>
