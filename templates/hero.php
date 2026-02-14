<!-- HERO БЛОК -->
            <div class="p-5 mb-4 bg-light rounded-3 border shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="container-fluid py-2">
                    <h1 class="display-6 fw-bold mb-3">Найди любое место в Туркестане</h1>
                    <p class="col-md-10 fs-5 text-muted mb-4">Рестораны, отели, магазины и услуги. Рейтинги, фото и реальные отзывы жителей города.</p>
                    
                    <form action="search.php" method="GET" class="shadow-sm rounded-pill bg-white p-1 d-flex">
                        <span class="input-group-text bg-white border-0 rounded-pill ps-3"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-0 shadow-none bg-transparent" placeholder="Например: Rixos, плов, сантехник..." required style="height: 50px;">
                        <button class="btn btn-primary rounded-pill px-4 m-1 fw-bold" type="submit">Найти</button>
                    </form>
                    
                    <div class="mt-4 text-muted small">
                        <span class="fw-bold text-dark">Популярные запросы:</span> 
                        <a href="search.php?q=кофе" class="badge bg-white text-dark border text-decoration-none ms-1">Кофе</a>
                        <a href="search.php?q=бургер" class="badge bg-white text-dark border text-decoration-none ms-1">Бургеры</a>
                        <a href="search.php?q=парк" class="badge bg-white text-dark border text-decoration-none ms-1">Парки</a>
                        <a href="category.php?slug=food" class="badge bg-white text-dark border text-decoration-none ms-1">Еда</a>
                    </div>
                </div>
            </div>