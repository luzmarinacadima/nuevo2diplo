
resource "azurerm_service_plan" "backend" {
  name                = "plan-asistencia-backend"
  resource_group_name = azurerm_resource_group.proyecto.name
  location            = "centralus"

  os_type  = "Linux"
  sku_name = "B1"
}

resource "azurerm_linux_web_app" "backend" {
  name                = "asistencia-backend-${random_string.suffix.result}"
  resource_group_name = azurerm_resource_group.proyecto.name
  location            = azurerm_service_plan.backend.location
  service_plan_id     = azurerm_service_plan.backend.id

  https_only = true

site_config {
  always_on        = true
  app_command_line = "sh /home/site/wwwroot/azure/startup.sh"

  application_stack {
    php_version = "8.3"
  }
}
  app_settings = {
    APP_ENV              = "production"
    APP_DEBUG            = "0"
    APP_TIMEZONE         = "America/La_Paz"
    CORS_ALLOWED_ORIGINS = "https://stasisgbtrn34x.z19.web.core.windows.net"
    SESSION_NAME         = "asistenciau_session"
    SESSION_SECURE       = "1"
  }
}

output "backend_url" {
  value = "https://${azurerm_linux_web_app.backend.default_hostname}"
}
