SELECT 
    id,
    nombre,
    cantidad,
    unidad,
    FORMAT(precio, 0) AS precio_compra,
    FORMAT(valor_venta, 0) AS valor_venta,
    estado,
    inactivo,
    creado_por,
    actualizado_por,
    creado_en,
    actualizado_en
FROM productos

UNION ALL

SELECT 
    NULL AS id,
    'TOTAL GENERAL' AS nombre,
    NULL AS cantidad,
    NULL AS unidad,
    FORMAT(SUM(precio), 0) AS precio_compra,
    NULL AS valor_venta,
    NULL AS estado,
    NULL AS inactivo,
    NULL AS creado_por,
    NULL AS actualizado_por,
    NULL AS creado_en,
    NULL AS actualizado_en
FROM productos

ORDER BY 
    creado_en IS NULL,  -- esto empuja el TOTAL al final
    creado_en DESC;