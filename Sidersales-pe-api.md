# Sidersales-pe

## Documentación — Servicio GPS Tracking vía

## MuleSoft

## Propósito del Servicio

El objetivo de esta aplicación es recibir datos provenientes de dispositivos GPS instalados en
unidades de transporte y enviarlos hacia una AWS Lambda alojada dentro de la red Gerdau.

El flujo completo se realiza a través de MuleSoft (Experience API), que actúa como
intermediario entre la fuente de datos GPS y los servicios internos en AWS.
La información procesada es almacenada posteriormente en DynamoDB.

## Responsable del Servicio

**Nombre:** Nilton Zambrano

**Correo:** nzambrano@gerdau.com

## Observaciones generales sobre la aplicación de Mulesoft

**Versión de la aplicación Mulesoft:** 4.6.

Los registros en producción se envían a datadog y se guardan en Anypoint (Runtime
Manager).

### Arquitectura General del Flujo

El flujo operacional funciona de la siguiente manera:

1. **Proveedor GPS** envía un JSON con la información telemétrica.
2. La **Experience API de MuleSoft** recibe la solicitud.
3. MuleSoft **aplica validaciones, políticas de seguridad** y transformación del payload.
4. La API invoca una **AWS Lambda dentro de la red Gerdau**.
5. La Lambda procesa los datos y los almacena en **Amazon DynamoDB**.

### Endpoints y Recursos

#### Environments del API Manager (MuleSoft)

```
Ambiente URL del Endpoint
```

##### DEV

```
https://dev-experience-gerdau-sidersales-pe.us-
e1.cloudhub.io/api/gps-tracking
```

```
QA https://qa-experience-gerdau-sidersales-pe.us-
e1.cloudhub.io/api/gps-tracking
```

```
PRD https://api.gerdau.com/experiencepe/api/gps-tracking - gerdau-sidersales-
```

#### Exchange MuleSoft

**Asset:** Experience Gerdau Sidersales PE API

https://anypoint.mulesoft.com/exchange/402c707c- 9264 - 4c98-868f-
2274bfcd0e08.commercial/experience-gerdau-sidersales-pe-api/minor/1.0/

### Políticas aplicadas en API Manager

Las siguientes policies están configuradas en la Experience API:

- **OAuth 2.0 — Client Credentials (Azure AD)**
- **Rate Limiting (según ambiente)**

**Client Secret Rotation**

- Los client secret expiran de forma periódica.
- El cliente debe solicitar renovación antes del vencimiento.

#### Renovación de Client Secret

La renovación debe solicitarse a través del portal de ServiceNow:

https://gerdau.service-
now.com/digitaldesk?id=sc_category&sys_id=0c4daa801be4ca5041ab844fe54bcba8&catal
og_id=e0d08b13c3330100c8b837659bba8fb

```
Flujo del api MuleSoft.
```

```
Formato JSON Esperado.
```

```
Ejemplo de solicitud de postman.
```

## Ejemplo — Guía Paso a Paso para Obtener el

## Token y Consumir el Servicio

### Paso 1 — Obtener el token de autenticación

Para consumir el servicio de GPS Tracking, primero debe autenticarse en Azure AD utilizando
el flujo **Client Credentials**.

Ejecute el siguiente comando CURL:

curl --location 'https://login.microsoftonline.com/461fd7ef-0eb3- 4420 - b044-
310dc2914d64/oauth2/v2.0/token' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'scope=https://graph.microsoft.com/.default' \
--data-urlencode 'grant_type=client_credentials' \
--data-urlencode 'client_id=6ad86e81-4f9d-49d8-a265-b090dbac3655' \
--data-urlencode 'client_secret=wby8Q~sfJzpx3Agp41VuW_Y3gjiL~kSBOFzQVb7L'

#### ¿Qué debe copiar?

En la respuesta obtendrá un JSON como este:

##### {

"token_type": "Bearer",
"access_token": "eyJhbGciOiJSUzI1NiIsIn..."
}

Debe copiar el valor entero del campo **access_token.**

### Paso 2 — Enviar el token en el encabezado Authorization

Con el token obtenido, ya puede realizar la solicitud al endpoint del servicio.

#### Estructura esperada del header

Authorization: Bearer {token}

Reemplace {token} por el valor extraído del paso anterior.

### Paso 3 — Envio de la solicitud al servicio GPS Tracking

Curl --location 'https://dev-experience-gerdau-sidersales-pe.us-
e1.cloudhub.io/api/gps-tracking' \
--header 'Authorization: Bearer {TOKEN_GENERADO}' \
--header 'Content-Type: application/json' \
--data '{
"placa": "354-E3H",
"latitud": "41.40338",
"longitud": "2.17403",
"velocidadKPH": "58",
"fechaHoraGPS": "2025- 08 - 09 15:06:05",
"rucTransportista": "1245783265",
"ProveedorGPS": "TRAGLOCK"
}'

### Excepciones de la API

```
Código Descripción
```

```
400 — Bad Request JSON mal formado, campos faltantes, tipos incorrectos
```

```
401 — Unauthorized Token inválido, expirado o ausente
403 — Forbidden Client ID no autorizado o sin permisos
```

```
404 — Not Found Endpoint incorrecto
```

```
405 — Not Allowed Error en la URL del Endpoint
```

```
429 — Too Many Requests Límite de rate limit excedido
```

```
500 — Internal Server Error Error inesperado en MuleSoft
502 — Bad Gateway Error en la integración con AWS Lambda
```
