<script setup>
const columns = [
  {
    field: 'name',
    header: 'Name',
    sortable: true,
  },
  {
    field: 'price',
    header: 'Price',
    sortable: true,
    numeric: true,
  },
  {
    field: 'created_at',
    header: 'Created at',
    sortable: true,
    date: true,
  },
]
</script>

<template>
  <app-card>
    <template #title>
      Subscription Plans
    </template>

    <template #content>
      <app-grid
        :columns="columns"
        url="/api/subscription-plans"
        default-sort-field="created_at"
        default-sort-order="desc"
      />
    </template>
  </app-card>
</template>
