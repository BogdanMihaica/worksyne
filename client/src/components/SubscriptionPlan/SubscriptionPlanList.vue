<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

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
  {
    field: 'actions',
    header: 'Actions',
    type: 'actions',
    widthFit: true,
    items: ({ data }) => [
      {
        label: 'Edit',
        icon: 'pen-to-square',
        severity: 'secondary',
        onClick: () => openEditSubscriptionPlan(data.id),
      },
    ],
  },
]

function openCreateSubscriptionPlan() {
  router.push({ name: 'subscription-plan-create' })
}

function openEditSubscriptionPlan(id) {
  router.push({ name: 'subscription-plan-edit', params: { id } })
}
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

    <template #actions>
      <form-button
        label="New"
        icon="plus"
        @click="openCreateSubscriptionPlan"
      />
    </template>
  </app-card>
</template>
