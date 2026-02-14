// assets/script.js - Базовые клиентские скрипты

document.addEventListener("DOMContentLoaded", function() {
    // Инициализация тултипов Bootstrap (если нужны)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Подтверждение удаления
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if(!confirm('Вы уверены, что хотите удалить эту запись?')) {
                e.preventDefault();
            }
        });
    });
});

// Логика Избранного
    const favButtons = document.querySelectorAll('.btn-favorite');
    
    favButtons.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Анимация нажатия (опционально)
            this.style.transform = "scale(0.9)";
            setTimeout(() => this.style.transform = "scale(1)", 150);

            const postId = this.getAttribute('data-id');
            const icon = this.querySelector('i');
            const textSpan = this.querySelector('.btn-text'); // Для кнопки в post.php

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
                        // Стало активным
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        
                        // Если это большая кнопка в post.php
                        if(this.classList.contains('btn-outline-danger')) {
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger');
                        }
                        if(textSpan) textSpan.textContent = 'В избранном';
                        
                    } else {
                        // Убрали из избранного
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                        
                        // Если это большая кнопка
                        if(this.classList.contains('btn-danger')) {
                            this.classList.remove('btn-danger');
                            this.classList.add('btn-outline-danger');
                        }
                        if(textSpan) textSpan.textContent = 'В избранное';
                    }
                } else {
                    alert('Ошибка: ' + result.message);
                }
            } catch (err) {
                console.error('Ошибка запроса:', err);
                alert('Не удалось выполнить действие');
            }
        });
    });