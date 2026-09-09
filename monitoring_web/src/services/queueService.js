import api from "./api";

export async function fetchQueueSummary() {
  const response = await api.get("/queue/summary");
  return response.data.data;
}

export async function fetchQueue(kodeDesa, filters = {}) {
  const params = { kode_desa: kodeDesa };

  // status TIDAK dikirim kalau kosong -> backend otomatis kembalikan semua status
  if (filters.status) params.status = filters.status;

  params.limit = filters.limit ?? 20;
  params.offset = filters.offset ?? 0;

  const response = await api.get("/queue", { params });
  return response.data.data;
}
