<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineProps({
  icon: String,
  color: {
    type: String,
    default: 'inherit'
  }
})
</script>

<template>
  <FontAwesomeIcon :icon="icon" fixed-width :style="{ color }"/>
</template>
