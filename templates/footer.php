</div> </div> <footer class="footer mt-auto py-5 bg-dark text-white-50">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="text-white mb-3 fw-bold"><i class="fa-solid fa-map-location-dot text-primary"></i> Spravka.kz</h5>
                <p class="small">
                    Современный городской справочник Туркестана. Мы помогаем жителям и туристам находить лучшие места, услуги и развлечения в городе.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white-50 hover-white"><i class="fa-brands fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white-50 hover-white"><i class="fa-brands fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white-50 hover-white"><i class="fa-brands fa-telegram fa-lg"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-white mb-3">Навигация</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php" class="text-decoration-none text-white-50 hover-white">Главная</a></li>
                    <li class="mb-2"><a href="search.php" class="text-decoration-none text-white-50 hover-white">Поиск</a></li>
                    <li class="mb-2"><a href="search.php?sort=rating" class="text-decoration-none text-white-50 hover-white">Популярное</a></li>
                    <li class="mb-2"><a href="search.php?sort=date" class="text-decoration-none text-white-50 hover-white">Новинки</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Бизнесу</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="add.php" class="text-decoration-none text-white-50 hover-white">Добавить заведение</a></li>
                    <li class="mb-2"><a href="login.php" class="text-decoration-none text-white-50 hover-white">Вход в кабинет</a></li>
                    <li class="mb-2"><a href="register.php" class="text-decoration-none text-white-50 hover-white">Регистрация</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none text-white-50 hover-white">Правила размещения</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white mb-3">Контакты</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="fa-solid fa-envelope me-2"></i> support@spravka.kz</li>
                    <li class="mb-2"><i class="fa-solid fa-phone me-2"></i> +7 (707) 123-45-67</li>
                    <li class="mb-2"><i class="fa-solid fa-location-arrow me-2"></i> г. Туркестан, пр. Тауке хана, 15</li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <div class="text-center small">
            &copy; <?= date('Y') ?> <strong>Spravka.kz</strong>. Все права защищены. 
            Сделано с <i class="fa-solid fa-heart text-danger"></i> в Туркестане.
        </div>
    </div>
</footer>

<style>
    .hover-white:hover { color: #fff !important; transition: color 0.2s; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/script.js"></script>
</body>
</html>