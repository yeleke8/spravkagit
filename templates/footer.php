</div> <!-- Закрываем .row из header.php -->
</div> <!-- Закрываем .container-fluid из header.php -->

<footer class="footer mt-auto py-5 bg-dark text-white-50 border-top border-secondary">
    <div class="container-fluid px-4 px-lg-5">
        <div class="row gy-5 justify-content-between">
            <div class="col-lg-4 col-xl-3">
                <h4 class="text-white mb-4 fw-bold"><i class="fa-solid fa-map-location-dot text-primary"></i> Spravka.kz</h4>
                <p class="small mb-4 text-secondary">
                    Главный городской справочник Туркестана. Мы соединяем людей с лучшими местами и услугами города, делая жизнь комфортнее и интереснее.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 36px; height: 36px;"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 36px; height: 36px;"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 36px; height: 36px;"><i class="fa-brands fa-telegram"></i></a>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white mb-3 fw-bold text-uppercase small ls-1">Навигация</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="index.php" class="text-decoration-none text-white-50 hover-white">Главная</a></li>
                    <li><a href="search.php" class="text-decoration-none text-white-50 hover-white">Поиск</a></li>
                    <li><a href="search.php?sort=rating" class="text-decoration-none text-white-50 hover-white">Рейтинг мест</a></li>
                    <li><a href="search.php?sort=date" class="text-decoration-none text-white-50 hover-white">Новинки</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white mb-3 fw-bold text-uppercase small ls-1">Партнерам</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="add.php" class="text-decoration-none text-white-50 hover-white">Добавить бизнес</a></li>
                    <li><a href="login.php" class="text-decoration-none text-white-50 hover-white">Личный кабинет</a></li>
                    <li><a href="register.php" class="text-decoration-none text-white-50 hover-white">Регистрация</a></li>
                    <li><a href="#" class="text-decoration-none text-white-50 hover-white">Реклама</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-xl-3">
                <h6 class="text-white mb-3 fw-bold text-uppercase small ls-1">Контакты</h6>
                <ul class="list-unstyled small d-flex flex-column gap-3">
                    <li class="d-flex align-items-start"><i class="fa-solid fa-envelope mt-1 me-3 text-primary"></i> <div>support@spravka.kz<br><small class="text-secondary">По вопросам поддержки</small></div></li>
                    <li class="d-flex align-items-start"><i class="fa-solid fa-phone mt-1 me-3 text-primary"></i> <div>+7 (707) 123-45-67<br><small class="text-secondary">Пн-Пт, 09:00 - 18:00</small></div></li>
                    <li class="d-flex align-items-start"><i class="fa-solid fa-location-dot mt-1 me-3 text-primary"></i> г. Туркестан, пр. Тауке хана, 15</li>
                </ul>
            </div>
        </div>
        
        <div class="border-top border-secondary border-opacity-25 mt-5 pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
            <div>&copy; <?= date('Y') ?> <strong>Spravka.kz</strong>. Все права защищены.</div>
            <div class="mt-2 mt-md-0">Сделано с <i class="fa-solid fa-heart text-danger mx-1"></i> в Туркестане</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/script.js"></script>
</body>
</html>