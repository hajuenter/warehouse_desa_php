import api from "./api";

export async function login(username, password) {
  const response = await api.post("/auth/login", { username, password });
  return response.data.token;
}

export async function changePassword(oldPassword, newPassword) {
  const response = await api.post("/auth/change-password", { oldPassword, newPassword });
  return response.data;
}

export async function forgotPassword() {
  const response = await api.post("/auth/forgot-password");
  return response.data;
}

export async function resetPassword(token, newPassword) {
  const response = await api.post("/auth/reset-password", { token, newPassword });
  return response.data;
}