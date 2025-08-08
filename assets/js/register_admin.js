document.getElementById('register-admin-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const messageDiv = document.getElementById('form-message');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    messageDiv.style.display = 'none';
    messageDiv.className = 'message';

    try {
        const response = await fetch('api/admin/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            messageDiv.textContent = result.message;
            messageDiv.classList.add('success');
            form.reset();
        } else {
            messageDiv.textContent = result.message || 'Ocurrió un error desconocido.';
            messageDiv.classList.add('error');
        }
        messageDiv.style.display = 'block';

    } catch (error) {
        messageDiv.textContent = 'Error de conexión. Por favor, inténtalo de nuevo.';
        messageDiv.classList.add('error');
        messageDiv.style.display = 'block';
    }
});
