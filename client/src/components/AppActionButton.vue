<script setup>
defineProps({
  label: String,
  icon: String,
  severity: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'sm',
  },
  onClick: Function,
})
</script>

<template>
  <form-button
    type="button"
    :label="label"
    :icon="icon"
    :severity="severity"
    :size="size"
    @click="onClick"
  />
</template>
