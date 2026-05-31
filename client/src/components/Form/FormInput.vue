<script setup>
import { computed, ref } from 'vue'
import { Checkbox } from 'primevue'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  secondaryLabel: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
  },
  modelValue: {
    type: [String, Number, Boolean, Object],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  boolean: {
    type: Boolean,
    default: false,
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
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])
const visiblePassword = ref(false)

const sizeClass = computed(() => {
  const sizeMap = {
    xs: 'w-30',
    sm: 'w-45',
    md: 'w-64',
    lg: 'w-full',
  }

  return sizeMap[props.size] || sizeMap.lg
})

/**
 *
 * @param event
 */
function updateValue(event) {
  emit('update:modelValue', event.target.value)
}

/**
 * Toggles between type=text and type=password.
 */
function togglePasswordVisibility() {
  visiblePassword.value = !visiblePassword.value
}
</script>

<template>
  <div>
    <label>
      <div class="font-semibold text-[#334155] text-[13px] mb-1">
        <span>{{ label }}</span>
        <span v-if="required" class="text-red-500">*</span>
      </div>

      <div v-if="type !== 'checkbox'" class="flex items-center gap-2 text-[13px]">
        <input
          :type="type !== 'password'
            ? type
            : (visiblePassword ? 'text' : 'password')"
          :value="modelValue"
          class="p-[7px] border border-gray-200 focus:outline-none"
          :class="sizeClass"
          :placeholder="placeholder || `Enter ${label.toLowerCase()}`"
          @input="updateValue($event)"
        />

        <div v-if="type === 'password'" class="cursor-pointer text-slate-600" @click="togglePasswordVisibility">
          <app-icon :icon="visiblePassword ? 'eye-slash' : 'eye'" />
        </div>
      </div>
      <div v-else class="text-[#334155] text-[13px] flex gap-2 items-center">
        <Checkbox
          :model-value="modelValue"
          size="small"
          binary
          :disabled="disabled || false"
          @update:model-value="val => $emit('update:modelValue', val)"
        />
        <span v-if="secondaryLabel">{{ secondaryLabel }}</span>
      </div>

      <div v-if="description" class="text-xs italic text-neutral-400 mt-1">{{ description }}</div>
      <form-error v-if="error" :error="error" />
    </label>
  </div>
</template>
