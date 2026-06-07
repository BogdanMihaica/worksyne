<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-[#f7f8fb] text-slate-950">
    <public-navigation />
    <div class="flex-1">
      <RouterView />
    </div>
    <public-footer />
  </div>
</template>
