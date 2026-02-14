// assets/script.js - Клиентские скрипты

document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Инициализация тултипов Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // 2. Подтверждение удаления (универсальное)
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
            if(!confirm('Вы уверены, что хотите удалить эту запись?')) {
                e.preventDefault();
            }
        }
    });

    // 3. Логика Избранного (Делегирование событий)
    // Вешаем обработчик на body, чтобы ловить клики даже на динамических элементах
    document.body.addEventListener('click', async function(e) {
        // Ищем ближайшую кнопку с классом btn-favorite
        const btn = e.target.closest('.btn-favorite');
        
        // Если кликнули не по кнопке избранного - выходим
        if (!btn) return;

        e.preventDefault();
        
        // Анимация нажатия
        btn.style.transform = "scale(0.85)";
        setTimeout(() => btn.style.transform = "scale(1)", 150);

        const postId = btn.getAttribute('data-id');
        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('.btn-text'); // Текст на кнопке (в post.php)

        try {
            let response = await fetch('ajax-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json;charset=utf-8'
                },
                body: JSON.stringify({id: postId})
            });

            let result = await response.json();

            if (result.status === 'success') {
                if (result.action === 'added') {
                    // --- Добавлено в избранное ---
                    
                    // Меняем иконку
                    if(icon) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                    }
                    
                    // Меняем стиль кнопки (если это страница post.php)
                    if(btn.classList.contains('btn-outline-danger')) {
                        btn.classList.remove('btn-outline-danger');
                        btn.classList.add('btn-danger');
                    }

                    // Меняем текст
                    if(textSpan) textSpan.textContent = 'В избранном';
                    
                    // Меняем title (подсказку)
                    btn.setAttribute('title', 'Убрать из избранного');

                } else {
                    // --- Удалено из избранного ---
                    
                    if(icon) {
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                    }
                    
                    if(btn.classList.contains('btn-danger')) {
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn-outline-danger');
                    }

                    if(textSpan) textSpan.textContent = 'В избранное';
                    
                    btn.setAttribute('title', 'В избранное');
                }
            } else if (result.status === 'login_required') {
                // Если не авторизован - редирект на логин
                window.location.href = 'login.php';
            } else {
                alert('Ошибка: ' + (result.message || 'Неизвестная ошибка'));
            }
        } catch (err) {
            console.error('Ошибка запроса:', err);
            // alert('Не удалось выполнить действие'); // Можно раскомментировать для отладки
        }
    });
});