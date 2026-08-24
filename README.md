# Descuento Global Woo

Plugin reutilizable para programar un descuento global en una tienda WooCommerce sin modificar los precios guardados de los productos.

## Funcionalidades

- Una campaña global con porcentaje entre `0,01 %` y `100 %`.
- Inicio y finalización opcionales en la zona horaria de WordPress.
- Activación manual y estados programada, activa, finalizada o deshabilitada.
- Prevalencia temporal sobre ofertas existentes, con restauración automática.
- Compatibilidad con productos simples, variables, carrito, checkout, HPOS y bloques.
- Precio regular tachado e insignia porcentual personalizable desde el tema.
- Interfaz administrativa con paleta Everforest.
- Acción segura para limpiar toda la configuración.

## Requisitos

- WordPress 6.9 o superior.
- WooCommerce 11.0 o superior.
- PHP 7.4 o superior.

## Instalación

1. Copia la carpeta `descuento-global-woo` dentro de `wp-content/plugins/`.
2. Activa **Descuento Global Woo** desde el panel de plugins.
3. Configura la campaña en **WooCommerce → Descuento global**.

Si la fecha inicial queda vacía, la campaña comienza al guardarla. Si la fecha final queda vacía, permanece activa hasta que se deshabilite.

## Extensibilidad

El plugin expone los siguientes filtros:

- `dgw_effective_percentage`
- `dgw_product_is_eligible`
- `dgw_sale_badge_text`

Y las siguientes acciones:

- `dgw_campaign_started`
- `dgw_campaign_ended`

## Licencia

GPL-2.0-or-later.

## Autor

[Mauricio Correa](https://maurouix.com/)
