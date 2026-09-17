import { useQuasar } from 'quasar'

export function useNotify() {
  const $q = useQuasar()

  const notifySuccess = (message = 'Guardado con éxito.', position = 'top-right') => {
    $q.notify({
      type: 'positive',
      message,
      position,
      progress: true,
      timeout: 2000,
    })
  }

  const notifyError = (message = 'Hubo un error.', position = 'top-right') => {
    $q.notify({
      type: 'negative',
      message,
      position,
      progress: true,
      icon: 'error',
      timeout: 3000,
    })
  }

  const notifyAlert = (message = 'Alerta!', position = 'top-right') => {
    $q.notify({
      type: 'warning',
      message,
      position,
      progress: true,
      timeout: 3000,
    })
  }

  return { notifySuccess, notifyError, notifyAlert }
}
