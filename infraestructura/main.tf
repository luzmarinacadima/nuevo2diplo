
terraform {
  required_version = ">= 1.6.0"

  required_providers {
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 4.0"
    }

    random = {
      source  = "hashicorp/random"
      version = "~> 3.0"
    }
  }
}

provider "azurerm" {
  features {}
}

resource "random_string" "suffix" {
  length  = 8
  lower   = true
  upper   = false
  numeric = true
  special = false
}

resource "azurerm_resource_group" "proyecto" {
  name     = "rg-asistencia-lab"
  location = "eastus2"
}


resource "azurerm_storage_account" "frontend" {
  name                = "stasis${random_string.suffix.result}"
  resource_group_name = azurerm_resource_group.proyecto.name
  location            = "centralus"

  account_tier             = "Standard"
  account_replication_type = "LRS"
  account_kind             = "StorageV2"

  https_traffic_only_enabled = true
}

resource "azurerm_storage_account_static_website" "frontend" {
  storage_account_id = azurerm_storage_account.frontend.id
  index_document     = "index.html"
}

output "frontend_url" {
  value = azurerm_storage_account.frontend.primary_web_endpoint
}

output "storage_account_name" {
  value = azurerm_storage_account.frontend.name
}
