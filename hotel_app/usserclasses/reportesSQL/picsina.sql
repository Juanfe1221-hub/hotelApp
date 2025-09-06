SELECT 
    fecha,
    tipo_piscina,
    SUM(conteo) AS total_ingresos
FROM piscina
WHERE accion = 'entrada'
GROUP BY fecha, tipo_piscina
ORDER BY fecha DESC, tipo_piscina;
