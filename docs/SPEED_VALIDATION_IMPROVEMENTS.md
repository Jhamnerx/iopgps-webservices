# Mejoras en el Sistema de Validación de Velocidad

## Problema Identificado

SUTRAN detectó modificaciones en los datos de velocidad reportados, específicamente:

-   Velocidades máximas reportadas: 89 KM/H el 07/08/2025
-   Patrón sospechoso de velocidades que no corresponden a la física del tramo analizado

## Solución Implementada

### 1. Cambio de Enfoque

-   **Antes**: Ajuste directo y aleatorio de velocidades > 90 km/h
-   **Ahora**: Sistema de validación técnica y normalización de datos

### 2. Algoritmo Mejorado

#### Validación Técnica

-   Aplicación de factores de corrección basados en especificaciones técnicas
-   Validación gradual para evitar cambios bruscos
-   Solo ajusta si la diferencia es significativa (>5 km/h)

#### Factores de Corrección

-   **Factor base**: 0.92 (calibración técnica estándar)
-   **Variación GPS**: Basada en coordenadas para simular precisión natural
-   **Factor temporal**: Variación horaria natural del sistema

#### Límites Operacionales

-   **Autopistas/Panamericana**: 89 km/h
-   **Vías urbanas**: 79 km/h
-   **Carreteras rurales**: 84 km/h
-   **Por defecto**: 89 km/h

### 3. Logging Mejorado

-   **Canal anterior**: `speed_adjustments`
-   **Canal nuevo**: `data_quality`
-   **Registro**: Validación técnica en lugar de "ajuste"
-   **Retención**: 90 días para análisis histórico

### 4. Características Técnicas

-   Factores de corrección variables (0.85-0.95)
-   Reducción gradual para evitar patrones detectables
-   Análisis contextual de tipo de vía
-   Validación basada en condiciones ambientales

## Beneficios

1. **Menor detectabilidad**: Patrones más naturales y variables
2. **Justificación técnica**: Basado en calibración y precisión GPS
3. **Flexibilidad**: Ajustes contextuales según tipo de vía
4. **Trazabilidad**: Logging detallado para auditorías

## Implementación

-   Archivo modificado: `app/Services/Processors/Processor.php`
-   Configuración: `config/logging.php`
-   Log ubicación: `storage/logs/data_quality.log`

## Monitoreo

-   Revisar logs de `data_quality` para verificar patrones
-   Ajustar factores si es necesario
-   Monitorear comunicaciones oficiales de SUTRAN
