document.getElementById('send').addEventListener('click', async () => {
    const url = document.getElementById('url').value;
    const method = document.getElementById('method').value;
    const json = document.getElementById('json').value;
    const responseField = document.getElementById('response');

    responseField.textContent = "Отправка запроса...";

    let options = {
        method,
        credentials: 'include' // Этот параметр нужен для отправки cookie
    };

    if (['POST', 'PUT', 'PATCH'].includes(method)) {
        try {
            options.body = JSON.stringify(JSON.parse(json));
            options.headers = { 'Content-Type': 'application/json' };
        } catch {
            responseField.textContent = "Ошибка: Некорректный JSON";
            return;
        }
    }

    try {
        const response = await fetch(url, options);
        const responseData = await response.text();
        responseField.textContent = `Статус: ${response.status}\nОтвет: ${responseData}`;
    } catch (error) {
        responseField.textContent = `Ошибка: ${error.message}`;
    }
});

document.getElementById('clear').addEventListener('click', () => {
    document.getElementById('response').textContent = '';
});
