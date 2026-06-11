# Sistema de Debug Condicional

Este documento describe el sistema de debug condicional implementado en la aplicación, que permite activar/desactivar el logging de debug mediante una variable de entorno.

## Configuración

El sistema de debug se controla mediante la variable de entorno `SYSTEM_CODE_DEBUG`:

- `SYSTEM_CODE_DEBUG=true` o `SYSTEM_CODE_DEBUG=1` → Debug habilitado
- `SYSTEM_CODE_DEBUG=false` o no definida → Debug deshabilitado (por defecto)

### Configurar en .env

Agregar al archivo `.env`:

```env
SYSTEM_CODE_DEBUG=true
```

O configurar como variable de entorno del sistema.

## Funciones Disponibles

### PHP: `debug_log()`

Función helper ubicada en `config/helpers.php` que solo registra mensajes si `SYSTEM_CODE_DEBUG` está habilitado.

**Uso:**
```php
debug_log('Mensaje de debug aquí');
```

**Comportamiento:**
- Si `SYSTEM_CODE_DEBUG=true`: Registra el mensaje con prefijo `[DEBUG]` usando `error_log()`
- Si `SYSTEM_CODE_DEBUG=false` o no definida: No hace nada (no registra)

### JavaScript: `debugLog()`

Función JavaScript disponible en páginas que la incluyan. Solo registra mensajes si `SYSTEM_CODE_DEBUG` está habilitado.

**Uso:**
```javascript
debugLog('Mensaje de debug aquí');
debugLog('Variable:', variable);
debugLog('Múltiples', 'argumentos', objeto);
```

**Comportamiento:**
- Si `SYSTEM_CODE_DEBUG=true`: Registra el mensaje con prefijo `[DEBUG]` usando `console.log()`
- Si `SYSTEM_CODE_DEBUG=false` o no definida: No hace nada (no registra)

## Cuándo Usar Cada Función

### Usar `debug_log()` (PHP) o `debugLog()` (JavaScript) para:
- ✅ Información detallada de flujo de ejecución
- ✅ Retry attempts en conexiones API
- ✅ Datos de sesión/cookies para troubleshooting
- ✅ Debug de templates/vistas
- ✅ Información de desarrollo que no es crítica

### Usar `error_log()` (PHP) directamente para:
- ✅ Excepciones capturadas en catch blocks
- ✅ Errores de configuración crítica (SDK no disponible, keys faltantes)
- ✅ Errores de validación/autenticación fallida
- ✅ Errores de base de datos
- ✅ Cualquier error que requiera atención inmediata

## Archivos Modificados

### Implementación Core
- `config/helpers.php` - Función `debug_log()` agregada

### Archivos con Debug Condicional

#### PHP
- `public/link-details.php` - 2 statements de debug QR
- `public/stripe/checkout.php` - 3 statements (retry attempts, secret key prefix)
- `public/stripe/portal.php` - 4 statements (retry attempts)
- `public/login.php` - 10 statements (OAuth verbose logging)

#### JavaScript
- `public/link-details.php` - 14 statements (console.log/error/warn reemplazados)

## Ejemplos de Uso

### Ejemplo PHP
```php
// Debug condicional - solo se registra si SYSTEM_CODE_DEBUG=true
debug_log("Stripe API connection error (attempt $retryCount/$maxRetries): " . $e->getMessage());

// Error crítico - siempre se registra
error_log("ERROR: Stripe authentication failed - check your STRIPE_SECRET_KEY");
```

### Ejemplo JavaScript
```javascript
// Debug condicional - solo se registra si SYSTEM_CODE_DEBUG=true
debugLog('Switching to tab:', tabName);
debugLog('Selected tab element:', selectedTab);

// Error crítico - siempre se registra (usar console.error directamente si es necesario)
if (!element) {
    console.error('Critical error: Element not found');
}
```

## Beneficios

1. **Rendimiento**: Reduce el logging innecesario en producción
2. **Seguridad**: Evita exponer información sensible en logs de producción
3. **Flexibilidad**: Fácil activar/desactivar debug sin modificar código
4. **Mantenibilidad**: Código más limpio y consistente

## Mejores Prácticas

1. **Siempre usar `debug_log()` para información de desarrollo**
   - No usar `error_log()` directamente para debug
   - Mantener `error_log()` solo para errores críticos

2. **No exponer datos sensibles en debug**
   - Nunca loguear contraseñas, tokens completos, o información personal
   - Usar prefijos o máscaras para datos sensibles

3. **Documentar debug importante**
   - Si un debug es especialmente útil, agregar comentario explicativo
   - Mantener este documento actualizado

4. **Revisar logs periódicamente**
   - Verificar que los logs de debug no estén activos en producción
   - Limpiar logs antiguos regularmente

## Migración de Código Existente

Si encuentras código que usa `error_log()` o `console.log()` para debug:

1. **PHP**: Reemplazar `error_log('debug message')` con `debug_log('debug message')`
2. **JavaScript**: Reemplazar `console.log('message')` con `debugLog('message')`

**Antes:**
```php
error_log('OAuth callback - GET params: ' . print_r($_GET, true));
```

**Después:**
```php
debug_log('OAuth callback - GET params: ' . print_r($_GET, true));
```

**Antes:**
```javascript
console.log('Switching to tab:', tabName);
```

**Después:**
```javascript
debugLog('Switching to tab:', tabName);
```

## Notas Técnicas

- La función `debug_log()` verifica `SYSTEM_CODE_DEBUG` usando la función `env()` existente
- Los mensajes de debug se prefijan con `[DEBUG]` para fácil identificación en logs
- La variable `SYSTEM_CODE_DEBUG` se inyecta en JavaScript desde PHP usando `json_encode()`
- El sistema es compatible con valores `'true'`, `'1'`, `true` (boolean), o `1` (int)

## Troubleshooting

### Debug no funciona
1. Verificar que `SYSTEM_CODE_DEBUG=true` esté en `.env` o variables de entorno
2. Verificar que el archivo `.env` esté siendo cargado correctamente
3. Verificar permisos de lectura del archivo `.env`
4. Reiniciar el servidor web si es necesario

### Debug aparece en producción
1. Verificar que `SYSTEM_CODE_DEBUG` no esté definida o esté en `false`
2. Verificar que no haya valores por defecto incorrectos
3. Revisar logs para identificar qué función está generando el debug

## Changelog

- **v2.0.0** - Implementación inicial del sistema de debug condicional
  - Función `debug_log()` agregada en `config/helpers.php`
  - Función `debugLog()` agregada en JavaScript
  - 19 statements de `error_log` convertidos a `debug_log`
  - 14 statements de `console.log/error/warn` convertidos a `debugLog`
  - Debug HTML visible removido de templates

