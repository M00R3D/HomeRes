import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global response interceptor: handle 419 (CSRF/session) and network errors
axios.interceptors.response.use(
	function (response) { return response; },
	function (error) {
		try {
			const status = error?.response?.status;
			if (status === 419) {
				// Flag session error and redirect to login so page reload can show message
				try { localStorage.setItem('session_error', 'Tu sesión expiró. Por favor inicia sesión de nuevo.'); } catch(e) {}
				window.location.href = '/login';
				return Promise.reject(error);
			}
			// If there was no response (network) treat similarly
			if (!error.response) {
				try { localStorage.setItem('session_error', 'Error de conexión. Intenta de nuevo.'); } catch(e) {}
				window.location.reload();
			}
		} catch (e) {
			// ignore
		}
		return Promise.reject(error);
	}
);
