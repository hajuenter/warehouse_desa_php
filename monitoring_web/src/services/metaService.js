import api from "./api";

export async function fetchDesaList() {
  const response = await api.get("/desa");
  return response.data.data;
}

export async function fetchTableList() {
  const response = await api.get("/tables");
  return response.data.data;
}
