SELECT 
    reserva_id,
    habitacion_id,
    huesped,
    camas,
    fecha_inicio,
    fecha_fin,
    estado,
    tipo_cliente,
    precio_total,
    creado_en,
    usuario_crea,
    sw_eliminado,
    -- Columna para mostrar si está eliminada
    CASE 
        WHEN sw_eliminado = 1 THEN 'Eliminada'
        ELSE 'Activa'
    END AS estado_registro,
    -- Columna total: suma de todos los precios totales de registros no eliminados
    (SELECT SUM(precio_total) FROM reservascreadas WHERE sw_eliminado = 0) AS total_general
FROM reservascreadas
ORDER BY fecha_inicio DESC;