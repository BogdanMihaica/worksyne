<script setup>
</script>

<template>
  <div class="border border-slate-200 shadow-sm bg-white rounded-lg">
    <div class="flex p-4 justify-between items-center">
      <div>
        <span class="text-lg font-semibold text-slate-950"><slot name="title" /></span>
      </div>
      <div>
        <slot name="actions"/>
      </div>
    </div>
    <div class="h-px w-full bg-gray-200"></div>

    <div class="p-4">
      <slot name="content" />
    </div>
  </div>
</template>
