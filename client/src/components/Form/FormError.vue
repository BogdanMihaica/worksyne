<script setup>
defineProps({
  error: [Array, String],
})
</script>

<template>
  <div class="text-red-500 text-[13px]">{{ error[0] || error }}</div>
</template>
