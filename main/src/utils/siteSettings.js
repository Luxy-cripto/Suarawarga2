export function applySiteSettings(settings) {
  if (!settings) return

  const siteName =
    settings.site_name || 'SUARAWARGA'

  document.title = siteName

  let favicon =
    document.querySelector('link[rel="icon"]')

  if (!favicon) {
    favicon = document.createElement('link')
    favicon.rel = 'icon'

    document.head.appendChild(favicon)
  }

  if (settings.site_logo) {
    favicon.href = settings.site_logo
  } else {
    favicon.href =
      'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">📢</text></svg>'
  }
}