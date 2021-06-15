# Pagos QR
Version 1.7.3
## Parametros requeridos
- Transaction ID
- Currency
- Amount
- Gloss
- Expiration

Credenciales de BCP
- Host
- User
- Password
- Public Token
- User Id
- Business Code
- Service Code
- Certificate Password
- Default Expiration

## Flujo
- Recibe el Transacction_id y valida la si el pedido existe.
- Verificar el metodo de pago es válido
- Validar que la moneda sea válida para el método de pago QR
- Validar que el pedido pertencezca al usuario
- Validar que la transacción no haya sido pagada anteriotmente

- Consumir el endpoint del BCP para generar el QR (POST: <a>/api/v2/Qr/Generated</a>)
    - Validar la respuesta
    - status = 00 - Valida
- Retornar el QR y Expiración

## Webhook
Crear un endpooint para ser consumido por BCP y recibir las respuestas de la transacciones
- Recibe un request y selecciona los parametros necesarios
- Validar si la transacción existe
- Validar si la orden o pedido existe
- Agregar el pago en la DB
- Enviar Notificación
- Retornar la Respuesta del proceso