SELECT 
    d.id,        
    p.nombre AS producto,        
    d.cantidad,        
    d.valor_unitario,        
    FORMAT(d.cantidad * d.valor_unitario, 0, 'es_CO') AS total,        
    d.observacion,        
    d.despachado_por,        
    d.creado_en 
FROM despachos d 
INNER JOIN productos p ON d.producto_id = p.id
WHERE p.sw_bodega = 2
  AND p.estado = 1
  AND (p.inactivo IS NULL OR p.inactivo = 0)

UNION ALL  

SELECT 
    NULL AS id,        
    'TOTAL BODEGA 2' AS producto,        
    NULL AS cantidad,        
    NULL AS valor_unitario,        
    FORMAT(SUM(d.cantidad * d.valor_unitario), 0, 'es_CO') AS total,        
    NULL AS observacion,        
    NULL AS despachado_por,        
    NULL AS creado_en 
FROM despachos d
INNER JOIN productos p ON d.producto_id = p.id
WHERE p.sw_bodega = 2
  AND p.estado = 1
  AND (p.inactivo IS NULL OR p.inactivo = 0)

ORDER BY 
    creado_en IS NULL,
    creado_en DESC; 