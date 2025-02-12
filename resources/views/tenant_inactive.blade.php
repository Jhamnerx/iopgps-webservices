<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente Inactivo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-lg p-8 max-w-md mx-auto text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-600 mx-auto mb-4" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M20.84 4.61a2.34 2.34 0 0 1 0 3.31L5.18 23.58a2.34 2.34 0 0 1-3.31 0L.62 21.31a2.34 2.34 0 0 1 0-3.31L16.24.42a2.34 2.34 0 0 1 3.31 0l1.29 1.3z" />
        </svg>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Acceso Restringido</h1>
        <p class="text-gray-600 mb-6">
            Este Cliente está inactivo y no tiene acceso al sistema en este momento. Por favor, contacta al
            administrador
            si crees que esto es un error.
        </p>
        <a href="/" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition">
            Volver al Inicio
        </a>
    </div>
</body>

</html>
