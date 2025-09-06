SELECT 
    id,
    nombre,
    CAST(cantidad AS UNSIGNED) AS cantidad,
    unidad,
    precio AS precio_compra,
    valor_venta AS valor_venta,
    (CAST(cantidad AS UNSIGNED) * CAST(valor_venta AS DECIMAL(15,2))) AS subtotal,
    estado,
    inactivo,
    creado_por,
    actualizado_por,
    creado_en,
    actualizado_en
FROM productos 
WHERE sw_bodega = 2 
  AND estado = 1
  AND (inactivo IS NULL OR inactivo = 0)

UNION ALL

SELECT 
    NULL AS id,
    'TOTAL BODEGA 2' AS nombre,
    SUM(CAST(cantidad AS UNSIGNED)) AS cantidad,
    'TOTAL' AS unidad,
    SUM(CAST(precio AS DECIMAL(15,2))) AS precio_compra,
    SUM(CAST(valor_venta AS DECIMAL(15,2))) AS valor_venta,
    SUM(CAST(cantidad AS UNSIGNED) * CAST(valor_venta AS DECIMAL(15,2))) AS subtotal,
    NULL AS estado,
    NULL AS inactivo,
    NULL AS creado_por,
    NULL AS actualizado_por,
    NULL AS creado_en,
    NULL AS actualizado_en
FROM productos 
WHERE sw_bodega = 2 
  AND estado = 1 
  AND (inactivo IS NULL OR inactivo = 0)

ORDER BY 
    creado_en IS NULL,
    creado_en DESC;