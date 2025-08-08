document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const passwordInput = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('show-password');

    showPasswordCheckbox.addEventListener('change', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    });

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = passwordInput.value;
        const rememberMe = document.getElementById('remember-me').checked;

        loginError.style.display = 'none';
        loginError.textContent = '';

        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password, remember: rememberMe })
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = 'dashboard.php'; // Redirigir al dashboard
            } else {
                loginError.textContent = result.message || 'Error de inicio de sesión desconocido.';
                loginError.style.display = 'block';
            }
        } catch (error) {
            console.error('Error en el fetch de login:', error);
            loginError.textContent = 'Error de conexión. Inténtalo de nuevo.';
            loginError.style.display = 'block';
        }
    });
});
