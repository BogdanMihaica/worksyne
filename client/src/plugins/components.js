export function registerComponents(app) {
  const components = import.meta.glob('../components/**/*.vue', { eager: true })

  Object.entries(components).forEach(([path, definition]) => {
    const componentName = path
      .split('/')
      .pop()
      .replace(/\.\w+$/, '')

    app.component(componentName, definition.default)
  })
}
