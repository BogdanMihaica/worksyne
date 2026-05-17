<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineProps({
  icon: String,
})
</script>

<template>
  <FontAwesomeIcon :icon="icon" fixed-width />
</template>
