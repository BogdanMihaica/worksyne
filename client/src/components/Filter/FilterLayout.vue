<script setup>
defineProps({
  onSearch: Function,
  onCancel: Function,
})
</script>

<template>
  <div class="flex justify-between items-end max-sm:flex-col">
    <div class="flex gap-y-2 gap-x-[23px] flex-wrap">
      <slot name="filters"></slot>
    </div>
    <div class="flex gap-1">
      <slot name="actions">
        <form-button
          severity="primary"
          @click.prevent="onSearch"
          label="Search"
          icon="magnifying-glass"
        />
        <form-button
          severity="ternary"
          icon="ban"
          size="lg"
          @click.prevent="onCancel"
        />
      </slot>
    </div>
  </div>
</template>
