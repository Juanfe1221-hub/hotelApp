SELECT 
    id,
    placa,
    entry_time,
    exit_time,
    IFNULL(duration_hours, TIMESTAMPDIFF(MINUTE, entry_time, exit_time)/60) AS horas_estadia,
    is_stadia,
    valor_por_hora,
    IF(exit_time IS NOT NULL, ROUND(IFNULL(duration_hours, TIMESTAMPDIFF(MINUTE, entry_time, exit_time)/60) * valor_por_hora, 2), NULL) AS charge,
    tipo_vehiculo,
    -- Columna total: suma de todos los cargos
    (SELECT ROUND(SUM(
        IF(exit_time IS NOT NULL, 
           IFNULL(duration_hours, TIMESTAMPDIFF(MINUTE, entry_time, exit_time)/60) * valor_por_hora, 
           0)
    ), 2) FROM parqueadero) AS total
FROM parqueadero
ORDER BY entry_time DESC;