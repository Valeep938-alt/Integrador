package sena.adso.parqueadero.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Timestamp;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

import sena.adso.parqueadero.model.Registro;
import sena.adso.parqueadero.util.ConexionDB;

/**
 * DAO Registro - Entradas y salidas del parqueadero Tarifas: Carro $3.000/hora
 * | Moto $1.500/hora | Camión $5.000/hora
 */
public class RegistroDAO {

    // Tarifas por hora en COP
    private static final double TARIFA_CARRO = 3000.0;
    private static final double TARIFA_MOTO = 1500.0;
    private static final double TARIFA_CAMION = 5000.0;

    // ─── Registrar ENTRADA ─────────────────────────────────────────────────────
    public int registrarEntrada(int vehiculoId) throws SQLException {
        String ahora = LocalDateTime.now().format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        String sql = "INSERT INTO registros (vehiculo_id, entrada, estado) VALUES (?, ?, 'ACTIVO')";
        try (Connection con = ConexionDB.getConexion();
                PreparedStatement ps = con.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setInt(1, vehiculoId);
            ps.setString(2, ahora);
            ps.executeUpdate();
            try (ResultSet rs = ps.getGeneratedKeys()) {
                return rs.next() ? rs.getInt(1) : -1;
            }
        }
    }

    // ─── Registrar SALIDA y calcular tarifa ────────────────────────────────────
    public Registro registrarSalida(int registroId) throws SQLException {
        // 1. Obtener datos del registro activo
        Registro reg = buscarPorId(registroId);
        if (reg == null || !"ACTIVO".equals(reg.getEstado())) {
            return null;
        }

        // 2. Calcular tarifa según tiempo transcurrido
        LocalDateTime ahora = LocalDateTime.now();
        long minutos = java.time.Duration.between(reg.getEntrada(), ahora).toMinutes();
        long horasCompletas = Math.max(1, (long) Math.ceil(minutos / 60.0)); // mínimo 1 hora
        double tarifaHora = getTarifaPorTipo(reg.getTipo());
        double total = horasCompletas * tarifaHora;

        // 3. Actualizar en BD
        String sql = "UPDATE registros SET salida=NOW(), tarifa=?, estado='FINALIZADO' WHERE id=?";
        try (Connection con = ConexionDB.getConexion(); PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setDouble(1, total);
            ps.setInt(2, registroId);
            ps.executeUpdate();
        }

        // 4. Retornar registro actualizado
        return buscarPorId(registroId);
    }

    // ─── Listar registros ACTIVOS (vehículos dentro) ──────────────────────────
    public List<Registro> listarActivos() throws SQLException {
        return listarPorEstado("ACTIVO");
    }

    // ─── Listar HISTORIAL (finalizados) ───────────────────────────────────────
    public List<Registro> listarHistorial() throws SQLException {
        return listarPorEstado("FINALIZADO");
    }

    private List<Registro> listarPorEstado(String estado) throws SQLException {
        List<Registro> lista = new ArrayList<>();
        String sql = "SELECT r.*, v.placa, v.tipo FROM registros r "
                + "JOIN vehiculos v ON r.vehiculo_id = v.id "
                + "WHERE r.estado = ? ORDER BY r.entrada DESC";
        try (Connection con = ConexionDB.getConexion(); PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setString(1, estado);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    lista.add(mapear(rs));
                }
            }
        }
        return lista;
    }

    // ─── Buscar por ID ────────────────────────────────────────────────────────
    public Registro buscarPorId(int id) throws SQLException {
        String sql = "SELECT r.*, v.placa, v.tipo FROM registros r "
                + "JOIN vehiculos v ON r.vehiculo_id = v.id WHERE r.id = ?";
        try (Connection con = ConexionDB.getConexion(); PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? mapear(rs) : null;
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    private double getTarifaPorTipo(String tipo) {
        if (tipo == null) {
            return TARIFA_CARRO;
        }
        switch (tipo.toUpperCase()) {
            case "MOTO":
                return TARIFA_MOTO;
            case "CAMION":
                return TARIFA_CAMION;
            default:
                return TARIFA_CARRO;
        }
    }

    private Registro mapear(ResultSet rs) throws SQLException {
        Registro r = new Registro();
        r.setId(rs.getInt("id"));
        r.setVehiculoId(rs.getInt("vehiculo_id"));
        r.setPlaca(rs.getString("placa"));
        r.setTipo(rs.getString("tipo"));
        r.setEntrada(rs.getTimestamp("entrada").toLocalDateTime());
        Timestamp sal = rs.getTimestamp("salida");
        if (sal != null) {
            r.setSalida(sal.toLocalDateTime());
        }
        r.setTarifa(rs.getDouble("tarifa"));
        r.setEstado(rs.getString("estado"));
        return r;
    }

    // ─── Reporte del día actual ───────────────────────────────────────────────
    public String obtenerReporteDia() throws SQLException {
        String sql = "SELECT COALESCE(SUM(tarifa), 0) AS total, COUNT(*) AS cantidad " +
                "FROM registros WHERE DATE(salida) = CURDATE() AND estado = 'FINALIZADO'";
        try (Connection con = ConexionDB.getConexion();
                PreparedStatement ps = con.prepareStatement(sql);
                ResultSet rs = ps.executeQuery()) {
            if (rs.next()) {
                double total = rs.getDouble("total");
                int cantidad = rs.getInt("cantidad");
                String fecha = java.time.LocalDate.now().toString();
                return String.format(java.util.Locale.US,
                        "{\"totalDia\":%.2f,\"cantidadSalidas\":%d,\"fecha\":\"%s\"}",
                        total, cantidad, fecha);
            }
            return String.format(java.util.Locale.US,
                    "{\"totalDia\":%.2f,\"cantidadSalidas\":%d,\"fecha\":\"%s\"}",
                    0.0, 0, java.time.LocalDate.now().toString());
        }
    }

    // ─── Historial filtrado por fechas y tipo ─────────────────────────────────
    public List<Registro> listarHistorialFiltrado(String desde, String hasta, String tipo) throws SQLException {
        StringBuilder sql = new StringBuilder(
                "SELECT r.*, v.placa, v.tipo FROM registros r "
                        + "JOIN vehiculos v ON r.vehiculo_id = v.id "
                        + "WHERE r.estado = 'FINALIZADO'");
        if (desde != null && !desde.isEmpty()) {
            sql.append(" AND DATE(r.salida) >= '").append(desde).append("'");
        }
        if (hasta != null && !hasta.isEmpty()) {
            sql.append(" AND DATE(r.salida) <= '").append(hasta).append("'");
        }
        if (tipo != null && !tipo.isEmpty()) {
            sql.append(" AND v.tipo = '").append(tipo.toUpperCase()).append("'");
        }
        sql.append(" ORDER BY r.salida DESC");

        List<Registro> lista = new ArrayList<>();
        try (Connection con = ConexionDB.getConexion();
                PreparedStatement ps = con.prepareStatement(sql.toString());
                ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                lista.add(mapear(rs));
            }
        }
        return lista;
    }

    // ─── Suma total de tarifas filtradas ──────────────────────────────────────
    public double sumarTarifasFiltradas(String desde, String hasta, String tipo) throws SQLException {
        StringBuilder sql = new StringBuilder(
                "SELECT COALESCE(SUM(r.tarifa), 0) AS total FROM registros r "
                        + "JOIN vehiculos v ON r.vehiculo_id = v.id "
                        + "WHERE r.estado = 'FINALIZADO'");
        if (desde != null && !desde.isEmpty()) {
            sql.append(" AND DATE(r.salida) >= '").append(desde).append("'");
        }
        if (hasta != null && !hasta.isEmpty()) {
            sql.append(" AND DATE(r.salida) <= '").append(hasta).append("'");
        }
        if (tipo != null && !tipo.isEmpty()) {
            sql.append(" AND v.tipo = '").append(tipo.toUpperCase()).append("'");
        }

        try (Connection con = ConexionDB.getConexion();
                PreparedStatement ps = con.prepareStatement(sql.toString());
                ResultSet rs = ps.executeQuery()) {
            return rs.next() ? rs.getDouble("total") : 0.0;
        }
    }

    // ─── Verificar si vehículo ya está adentro (registro ACTIVO) ──────────────
    public int obtenerRegistroActivo(int vehiculoId) throws SQLException {
        String sql = "SELECT id FROM registros WHERE vehiculo_id = ? AND estado = 'ACTIVO' LIMIT 1";
        try (Connection con = ConexionDB.getConexion(); PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setInt(1, vehiculoId);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? rs.getInt("id") : -1;
            }
        }
    }
}
