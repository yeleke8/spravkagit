<!-- HERO БЛОК -->
<div class="col-12 mb-5">
    <div class="p-5 rounded-4 position-relative overflow-hidden bg-primary text-white shadow-lg" style="background: linear-gradient(120deg, #2563eb 0%, #1d4ed8 100%);">
        <!-- Декоративные круги -->
        <div class="position-absolute top-0 end-0 bg-white opacity-10 rounded-circle" style="width: 300px; height: 300px; margin-top: -100px; margin-right: -50px;"></div>
        <div class="position-absolute bottom-0 start-0 bg-white opacity-10 rounded-circle" style="width: 200px; height: 200px; margin-bottom: -50px; margin-left: -50px;"></div>
        
        <div class="position-relative z-1 text-center py-3">
            <h1 class="display-5 fw-bold mb-3">Весь Туркестан как на ладони</h1>
            <p class="fs-5 text-white-50 mb-4 mx-auto" style="max-width: 700px;">Найдите лучшие рестораны, магазины, услуги и развлечения. Читайте честные отзывы и выбирайте лучшее.</p>
            
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <form action="search.php" method="GET" class="shadow rounded-pill bg-white p-1 d-flex">
                        <span class="input-group-text bg-white border-0 rounded-pill ps-3"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent py-2" placeholder="Что ищем? Например: плов, отель..." required>
                        <button class="btn btn-primary rounded-pill px-4 m-1 fw-bold" type="submit">Найти</button>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 small d-flex flex-wrap justify-content-center gap-2 align-items-center">
                <span class="text-white-50 me-2">Популярно:</span> 
                <a href="search.php?q=кофе" class="btn btn-sm btn-light bg-opacity-10 border-0 text-white hover-white rounded-pill px-3">☕ Кофе</a>
                <a href="search.php?q=бургер" class="btn btn-sm btn-light bg-opacity-10 border-0 text-white hover-white rounded-pill px-3">🍔 Бургеры</a>
                <a href="category.php?slug=food" class="btn btn-sm btn-light bg-opacity-10 border-0 text-white hover-white rounded-pill px-3">🍽 Еда</a>
                <a href="category.php?slug=hotels" class="btn btn-sm btn-light bg-opacity-10 border-0 text-white hover-white rounded-pill px-3">🏨 Отели</a>
            </div>
        </div>
    </div>
</div>