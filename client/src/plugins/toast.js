import { inject } from 'vue'

export const toastKey = Symbol('toast')

const severityMap = {
  error: 'error',
  success: 'success',
  warning: 'warn',
  info: 'info',
}

export function createToast(toast) {
  return function notify({ type = 'info', message, title, duration = 3000 }) {
    toast.add({
      severity: severityMap[type] || severityMap.info,
      summary: title || titleFor(type),
      detail: message,
      life: duration,
    })
  }
}

export function registerToast(app) {
  const notify = createToast(app.config.globalProperties.$toast)

  app.config.globalProperties.toast = notify
  app.provide(toastKey, notify)
}

export function useAppToast() {
  const toast = inject(toastKey)

  if (!toast) {
    throw new Error('Toast plugin is not registered.')
  }

  return toast
}

function titleFor(type) {
  const titles = {
    error: 'Error',
    success: 'Success',
    warning: 'Warning',
    info: 'Info',
  }

  return titles[type] || titles.info
}
