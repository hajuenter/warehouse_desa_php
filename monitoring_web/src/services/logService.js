import api from "./api";

export async function fetchLogs(filters = {}) {
  const params = {};

  if (filters.kode_desa) params.kode_desa = filters.kode_desa;
  if (filters.table_name) params.table_name = filters.table_name;
  if (filters.action) params.action = filters.action;
  params.limit = filters.limit ?? 20;
  params.offset = filters.offset ?? 0;

  const response = await api.get("/logs", { params });
  return response.data.data;
}
