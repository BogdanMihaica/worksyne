<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  icon: String,
  loading: {
    type: Boolean,
    default: false,
  },
  severity: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'md',
  },
  transparent: {
    type: Boolean,
    default: false,
  },
  iconColor: {
    type: String,
    default: 'inherit'
  }
})

const sizeClass = computed(() => {
  const sizeMap = {
    sm: 'text-[11px] px-2.5 py-1',
    md: 'text-[13px] px-2.5 py-2',
    lg: 'text-[14px] px-3 py-2',
  }

  return sizeMap[props.size] || sizeMap.md
})

const severityClass = computed(() => {
  const severityMap = {
    primary: 'bg-[#636F83] hover:bg-slate-600 text-white',
    success: 'bg-green-600 hover:bg-green-700 text-white',
    danger: 'bg-red-600 hover:bg-red-700 text-white',
    warning: 'bg-yellow-500 hover:bg-yellow-600 text-white',
    info: 'bg-cyan-600 hover:bg-cyan-700 text-white',
    secondary: 'bg-white hover:bg-gray-100 text-black',
    ternary: 'bg-white hover:bg-gray-100 text-[#2a6d80]',
  }

  return severityMap[props.severity] || severityMap.primary
})
</script>

<template>
  <button
    :disabled="loading"
    :class="[
      'cursor-pointer text-center flex items-center justify-center rounded-sm font-medium transition-all',
      transparent ? 'opacity-60 hover:opacity-100' : '',
      sizeClass,
      severityClass,
      loading ? 'opacity-60 pointer-events-none' : '',
    ]"
  >
    <app-icon v-if="icon && !loading" :icon="icon" :class="label ? 'mr-2' : ''" :color="iconColor"/>

    <app-icon v-if="loading" class="animate-spin" icon="spinner" :class="label ? 'mr-2' : ''" />

    <span>{{ label }}</span>
  </button>
</template>
